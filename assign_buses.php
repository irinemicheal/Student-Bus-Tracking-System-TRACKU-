<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db.php';

function safe_fetch_all($result) {
    $rows = [];
    if ($result instanceof mysqli_result) {
        while ($r = mysqli_fetch_assoc($result)) $rows[] = $r;
    }
    return $rows;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_delete'])) {
    $assignment_id = intval($_POST['ajax_delete']);
    $response = ['success' => false];

    $q = $mysqli->prepare("SELECT assignment_id, bus_id, student_id FROM assignments WHERE assignment_id=? AND student_id IS NOT NULL LIMIT 1");
    $q->bind_param("i",$assignment_id);
    $q->execute();
    $res = $q->get_result();
    if (!$res || $res->num_rows !== 1) {
        $response['error']="Assignment not found";
        header('Content-Type: application/json');
        echo json_encode($response);
        // log debug
        @file_put_contents(__DIR__ . '/assign_delete.log', date('c') . " - not found - id={$assignment_id}\n", FILE_APPEND);
        exit;
    }
    $row = $res->fetch_assoc();
    $bus_id = intval($row['bus_id']);
    $student_id = intval($row['student_id']);
    $q->close();

    $del = $mysqli->prepare("DELETE FROM assignments WHERE assignment_id=? AND student_id IS NOT NULL");
    $del->bind_param("i",$assignment_id);
    $del->execute();
    if ($del->affected_rows === 0) {
        $del->close();
        $response['error'] = 'Delete failed or already removed';
        header('Content-Type: application/json');
        echo json_encode($response);
        @file_put_contents(__DIR__ . '/assign_delete.log', date('c') . " - delete affected_rows=0 - id={$assignment_id}\n", FILE_APPEND);
        exit();
    }
    $del->close();

    $assignedCount=0;
    $cstmt=$mysqli->prepare("SELECT COUNT(*) FROM assignments WHERE bus_id=? AND student_id IS NOT NULL");
    $cstmt->bind_param("i",$bus_id);
    $cstmt->execute();
    $cstmt->bind_result($assignedCount);
    $cstmt->fetch();
    $cstmt->close();

    $cap=0;
    $capQ=$mysqli->prepare("SELECT capacity FROM buses WHERE bus_id=?");
    $capQ->bind_param("i",$bus_id);
    $capQ->execute();
    $capQ->bind_result($cap);
    $capQ->fetch();
    $capQ->close();

    $remaining=max(0,intval($cap)-intval($assignedCount));
    // Get bus number for client-side UI update
    $bn = '';
    $bnQ = $mysqli->prepare("SELECT bus_number FROM buses WHERE bus_id=? LIMIT 1");
    if ($bnQ) {
        $bnQ->bind_param("i", $bus_id);
        $bnQ->execute();
        $bnQ->bind_result($bn);
        $bnQ->fetch();
        $bnQ->close();
    }

    $response=['success'=>true,'assignment_id'=>$assignment_id,'bus_id'=>$bus_id,'bus_number'=>$bn,'assignedCount'=>$assignedCount,'capacity'=>$cap,'remaining'=>$remaining];
    // include student details so client can re-add to dropdown
    if ($student_id) {
        $sname=''; $sclass=''; $sroll='';
        $sQ = $mysqli->prepare("SELECT full_name,class,roll_number FROM students WHERE student_id=? LIMIT 1");
        if ($sQ) {
            $sQ->bind_param("i", $student_id);
            $sQ->execute();
            $sQ->bind_result($sname, $sclass, $sroll);
            $sQ->fetch();
            $sQ->close();
        }
        $response['student_id'] = $student_id;
        $response['student_name'] = $sname;
        $response['student_class'] = $sclass;
        $response['student_roll'] = $sroll;
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    @file_put_contents(__DIR__ . '/assign_delete.log', date('c') . " - deleted assignment={$assignment_id} bus={$bus_id} remaining={$remaining}\n", FILE_APPEND);
    exit();
}
$success=$error='';
if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['ajax_delete'])) {
    $bus_id=intval($_POST['bus_id'] ?? 0);
    $student_id=intval($_POST['student_id'] ?? 0);

    if (!($bus_id && $student_id)) $error="⚠️ Please select both Bus and Student.";
    else {
        $permStmt=$mysqli->prepare("SELECT route_id, driver_id FROM assignments WHERE bus_id=? AND student_id IS NULL LIMIT 1");
        $permStmt->bind_param("i",$bus_id);
        $permStmt->execute();
        $permStmt->store_result();
        if ($permStmt->num_rows===0) $error="⚠️ Bus has no permanent route+driver mapping.";
        else { $permStmt->bind_result($perm_route_id,$perm_driver_id); $permStmt->fetch(); }
        $permStmt->close();

        if (!$error) {
            $cap=0;
            $capQ=$mysqli->prepare("SELECT capacity FROM buses WHERE bus_id=?");
            $capQ->bind_param("i",$bus_id); $capQ->execute(); $capQ->bind_result($cap); $capQ->fetch(); $capQ->close();
            $countQ=$mysqli->prepare("SELECT COUNT(*) FROM assignments WHERE bus_id=? AND student_id IS NOT NULL");
            $countQ->bind_param("i",$bus_id); $countQ->execute(); $countQ->bind_result($assignedCount); $countQ->fetch(); $countQ->close();
            if ($cap<=0) $error="⚠️ Bus capacity invalid.";
            elseif ($assignedCount>=$cap) $error="⚠️ Bus is full. Capacity: $cap — Assigned: $assignedCount";
        }
        if (!$error) {
            $everQ=$mysqli->prepare("SELECT 1 FROM assignments WHERE student_id=? LIMIT 1");
            $everQ->bind_param("i",$student_id); $everQ->execute(); $everQ->store_result();
            if ($everQ->num_rows>0) $error="⚠️ Student already assigned before.";
            $everQ->close();
        }
        if (!$error) {
            $ins=$mysqli->prepare("INSERT INTO assignments (bus_id, route_id, driver_id, student_id) VALUES (?,?,?,?)");
            $ins->bind_param("iiii",$bus_id,$perm_route_id,$perm_driver_id,$student_id);
            if ($ins->execute()) $success="✅ Student assigned successfully!";
            else $error="❌ Insert failed: ".$ins->error;
            $ins->close();
        }
    }
}
$perm_map=[]; $permRes=$mysqli->query("SELECT assignment_id,bus_id,route_id,driver_id FROM assignments WHERE student_id IS NULL");
while ($r=$permRes->fetch_assoc()) $perm_map[intval($r['bus_id'])]=['route_id'=>$r['route_id'],'driver_id'=>$r['driver_id']];
$permRes->free();

$busCapacityMap=[]; $res=$mysqli->query("SELECT bus_id,COUNT(*) AS assigned FROM assignments WHERE student_id IS NOT NULL GROUP BY bus_id");
while($r=$res->fetch_assoc()) $busCapacityMap[intval($r['bus_id'])]=intval($r['assigned']); $res->free();

$everAssignedStudents=[]; $everRes=$mysqli->query("SELECT DISTINCT student_id FROM assignments WHERE student_id IS NOT NULL");
while($r=$everRes->fetch_assoc()) $everAssignedStudents[]=$r['student_id']; $everRes->free();

$buses=safe_fetch_all($mysqli->query("SELECT bus_id,bus_number,capacity,status FROM buses"));
$students_all=safe_fetch_all($mysqli->query("SELECT student_id,full_name,roll_number,class FROM students ORDER BY full_name ASC"));

$assigned=$mysqli->query("
        SELECT a.assignment_id,a.bus_id,a.route_id,a.driver_id,a.student_id,
            b.bus_number,b.capacity,
            r.route_name,
            d.full_name AS driver_name,
            s.full_name AS student_name, s.class, s.roll_number AS student_roll
    FROM assignments a
    LEFT JOIN buses b ON b.bus_id=a.bus_id
    LEFT JOIN routes r ON r.route_id=a.route_id
    LEFT JOIN drivers d ON d.driver_id=a.driver_id
    LEFT JOIN students s ON s.student_id=a.student_id
    WHERE a.student_id IS NOT NULL
    ORDER BY a.assignment_id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Students | Student Bus Tracking</title>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

<style>
body { font-family:"Segoe UI",Tahoma,Geneva,Verdana,sans-serif; background: linear-gradient(135deg,#a1c4fd,#c2e9fb); margin:0; padding:0; color:#333;}
.container { max-width:1100px; margin:30px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.15);}
h2 { text-align:center; margin-bottom:14px; color:#2c3e50;}
.back-btn { text-decoration:none; color:#fff; background:#00c6ff; padding:8px 15px; border-radius:5px; transition:0.3s; display:inline-block; margin-bottom:14px;}
.back-btn:hover { background:#0096d6;}
.form-box { background:#f9f9f9; padding:20px; border-radius:10px; margin-bottom:30px; box-shadow:0 4px 12px rgba(0,0,0,0.08);}
.form-box label { font-weight:600; margin-top:10px; display:block;}
select,button { width:100%; padding:10px; margin-top:8px; border:1px solid #ccc; border-radius:6px; font-size:14px; }
button { background:#2c3e50; color:#fff; cursor:pointer; transition:0.3s; }
button:hover { background:#1a252f;}
.alert { padding:10px; border-radius:6px; margin-bottom:12px;}
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb;}
.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;}
table { width:100%; border-collapse:collapse; border-radius:10px; overflow:hidden; margin-top:18px;}
th,td { text-align:center; padding:12px; border-bottom:1px solid #ddd;}
th { background:#2c3e50; color:#fff;}
tr:nth-child(even){ background:#f9f9f9;}
.delete-btn, .add-btn { padding:6px 12px; border-radius:6px; font-size:14px; margin:2px; cursor:pointer; color:#fff; border:none; }
.delete-btn { background:#ff4444; } .delete-btn:hover { background:#cc0000; }
.add-btn { background:#00c851; } .add-btn:hover { background:#007e33; }
.info-box { margin-top:8px; padding:8px 10px; border-radius:6px; background:#fff3cd; color:#856404; border:1px solid #ffeeba; }
.small { font-size:13px; color:#555; margin-top:6px; display:block; }
.select2-container--default .select2-selection--single { height:42px; border-radius:6px; }
</style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-users"></i> Assign Student to Bus</h2>
    <a href="admin.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>

    <form method="POST" class="form-box" id="assignForm">
        <?php if ($error): ?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert success"><?=htmlspecialchars($success)?></div><?php endif; ?>

        <label><i class="fa-solid fa-bus"></i> Select Bus</label>
        <select name="bus_id" id="busSelect" required>
            <option value="">-- Select Bus --</option>
            <?php foreach($buses as $bus):
                if($bus['status']!=='active') continue;
                $assignedCount=$busCapacityMap[$bus['bus_id']] ?? 0;
                $remaining=intval($bus['capacity'])-$assignedCount;
                $hasPerm=array_key_exists($bus['bus_id'],$perm_map);
                $disabled=(!$hasPerm || $remaining<=0)?'disabled':'';
            ?>
            <option value="<?= $bus['bus_id'] ?>" <?= $disabled ?> data-remaining="<?=$remaining?>"><?=htmlspecialchars($bus['bus_number'])?> — Seats left: <?=$remaining?> <?=!$hasPerm?'(No mapping)':''?></option>
            <?php endforeach; ?>
        </select>

        <label><i class="fa-solid fa-user-graduate"></i> Select Student</label>
        <select name="student_id" id="studentSelect" required>
            <option value="">-- Select Student --</option>
            <?php foreach($students_all as $s):
                if(in_array($s['student_id'],$everAssignedStudents,true)) continue;
            ?>
            <option value="<?=$s['student_id']?>"><?=htmlspecialchars($s['full_name'])?> | Roll: <?=$s['roll_number']?> | Class: <?=$s['class']?></option>
            <?php endforeach; ?>
        </select>

    

        <button type="submit"><i class="fa-solid fa-check"></i> Assign Student</button>
    </form>

    <h3 style="margin-top:20px;"><i class="fa-solid fa-table"></i> Current Assignments</h3>
    <table id="assignTable">
        <thead><tr><th>Bus</th><th>Route</th><th>Driver</th><th>Student</th><th>Action</th></tr></thead>
        <tbody>
        <?php if($assigned instanceof mysqli_result && $assigned->num_rows>0):
            while($row=$assigned->fetch_assoc()): ?>
            <tr data-assignment-id="<?=$row['assignment_id']?>">
                <td><?=htmlspecialchars($row['bus_number'])?></td>
                <td><?=htmlspecialchars($row['route_name'])?></td>
                <td><?=htmlspecialchars($row['driver_name'])?></td>
                <td><?=htmlspecialchars($row['student_name'])?> | Class: <?=htmlspecialchars($row['class'])?> | Roll: <?=htmlspecialchars($row['student_roll'])?></td>
                <td>
                    <button type="button" class="delete-btn" data-assignment-id="<?=$row['assignment_id']?>"><i class="fa-solid fa-trash"></i></button>
                </td>
            </tr>
        <?php endwhile; else: ?>
            <tr><td colspan="5">No assignments yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function(){
    // Student select uses native select (no Select2) so chosen value displays inside the control like the bus.
    // Use delegated handler and robustly read the data attribute
    $(document).on('click', '.delete-btn', function(e){
        e.preventDefault();
        if(!confirm('Are you sure you want to delete this student assignment?')) return;
        let btn = $(this);
        // Prefer jQuery's camelCase data key, fallback to attribute
        let aid = btn.data('assignmentId') || btn.attr('data-assignment-id');
        aid = parseInt(aid, 10) || 0;
        if (!aid) { alert('Invalid assignment id'); return; }
        btn.prop('disabled',true).html('<i class="fa-solid fa-spinner fa-spin"></i>');
        $.post(window.location.href, {ajax_delete:aid}, function(json){
            console.log('ajax delete response:', json);
            if(json && json.success){
                $('tr[data-assignment-id="'+aid+'"]').remove();

                // Update bus option seats left and disabled state
                try {
                    var busId = json.bus_id;
                    var remaining = json.remaining;
                    var busNumber = json.bus_number || '';
                    var opt = $('#busSelect option[value="'+busId+'"]');
                    if (opt.length) {
                        opt.attr('data-remaining', remaining);
                        var t = opt.text();
                        if (/Seats left:\s*\d+/.test(t)) {
                            t = t.replace(/Seats left:\s*\d+/, 'Seats left: ' + remaining);
                        } else if (busNumber) {
                            t = busNumber + ' — Seats left: ' + remaining;
                        }
                        opt.text(t);
                        opt.prop('disabled', remaining <= 0);
                    }
                } catch (e) {
                    console.warn('Failed to update bus option text', e);
                }

                // Re-add student back to the student select if info provided
                try {
                    if (json.student_id) {
                        var sid = json.student_id;
                        // if option already exists, do nothing
                        if ($('#studentSelect option[value="'+sid+'"]').length === 0) {
                            var stext = (json.student_name || 'Student') + ' | Roll: ' + (json.student_roll || '') + ' | Class: ' + (json.student_class || '');
                            var $opt = $('<option/>').val(sid).text(stext);
                            $('#studentSelect').append($opt).trigger('change');
                        }
                    }
                } catch (e) {
                    console.warn('Failed to re-add student to select', e);
                }
            } else {
                alert('Delete failed: '+(json && json.error?json.error:'unknown'));
                btn.prop('disabled',false).html('<i class="fa-solid fa-trash"></i>');
            }
        },'json').fail(function(xhr, status, err){
            alert('Request failed: '+status+' - '+err);
            btn.prop('disabled',false).html('<i class="fa-solid fa-trash"></i>');
        });
    });
});
</script>
</body>
</html>
