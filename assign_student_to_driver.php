<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once 'db_connect.php'; 

$success = $error = '';

$bus_id = $driver_id = $route_id = 0;
$bus_number = $driver_name = '';
$assignment_id = intval($_GET['assignment_id'] ?? 0);

if ($assignment_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT a.bus_id, a.driver_id, a.route_id, b.bus_number, d.full_name
        FROM assignments a
        LEFT JOIN buses b ON b.bus_id = a.bus_id
        LEFT JOIN drivers d ON d.driver_id = a.driver_id
        WHERE a.assignment_id = ?
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $assignment_id);
        $stmt->execute();
        $stmt->bind_result($bus_id_res, $driver_id_res, $route_id_res, $bus_number_res, $driver_name_res);
        if ($stmt->fetch()) {
            $bus_id = intval($bus_id_res);
            $driver_id = intval($driver_id_res);
            $route_id = intval($route_id_res);
            $bus_number = $bus_number_res ?? '';
            $driver_name = $driver_name_res ?? '';
        }
        $stmt->close();
    }
}


$bus_id = $bus_id ?: intval($_GET['bus_id'] ?? 0);
$driver_id = $driver_id ?: intval($_GET['driver_id'] ?? 0);
$route_id = $route_id ?: intval($_GET['route_id'] ?? 0);

if ($bus_id && ($bus_number === '' || $driver_name === '')) {
    $q = $mysqli->prepare("SELECT bus_number, route_id, drivers_id FROM buses WHERE bus_id = ? LIMIT 1");
    if ($q) {
        $q->bind_param("i", $bus_id);
        $q->execute();
        $q->bind_result($bus_number_db, $route_id_db, $drivers_id_db);
        if ($q->fetch()) {
            if ($bus_number === '') $bus_number = $bus_number_db ?? '';
            if (!$route_id) $route_id = intval($route_id_db);
            if (!$driver_id && !empty($drivers_id_db)) $driver_id = intval($drivers_id_db);
        }
        $q->close();
    }
}

if ($driver_id && $driver_name === '') {
    $dq = $mysqli->prepare("SELECT full_name FROM drivers WHERE driver_id = ? LIMIT 1");
    if ($dq) {
        $dq->bind_param("i", $driver_id);
        $dq->execute();
        $dq->bind_result($driver_name_db);
        if ($dq->fetch()) {
            $driver_name = $driver_name_db ?? '';
        }
        $dq->close();
    }
}
if (!$bus_id || !$driver_id || !$route_id) {
    $error = "⚠️ Missing bus, driver or route data. Make sure you clicked 'Add Student' from the Assign Buses page with bus_id, driver_id and route_id.";
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id'] ?? 0);

    if (!$bus_id || !$driver_id || !$route_id) {
        $error = "⚠️ Missing context (bus/driver/route). Cannot assign.";
    } elseif (!$student_id) {
        $error = "⚠️ Please select a student.";
    } else {
        $chk = $mysqli->prepare("SELECT 1 FROM assignments WHERE student_id = ? LIMIT 1");
        if ($chk) {
            $chk->bind_param("i", $student_id);
            $chk->execute();
            $chk->store_result();
            if ($chk->num_rows > 0) {
                $chk->close();
                $error = "⚠️ This student has already been assigned earlier and cannot be reassigned.";
            } else {
                $chk->close();
               
                $countQ = $mysqli->prepare("SELECT COUNT(*) FROM assignments WHERE bus_id = ? AND student_id IS NOT NULL");
                $assignedCount = 0;
                if ($countQ) {
                    $countQ->bind_param("i", $bus_id);
                    $countQ->execute();
                    $countQ->bind_result($assignedCount);
                    $countQ->fetch();
                    $countQ->close();
                }
              
                $cap = 0;
                $capQ = $mysqli->prepare("SELECT capacity FROM buses WHERE bus_id = ? LIMIT 1");
                if ($capQ) {
                    $capQ->bind_param("i", $bus_id);
                    $capQ->execute();
                    $capQ->bind_result($cap);
                    $capQ->fetch();
                    $capQ->close();
                }
                if ($cap <= 0) {
                    $error = "⚠️ Bus capacity invalid. Check bus data.";
                } elseif ($assignedCount >= $cap) {
                    $error = "⚠️ Bus is full. Capacity: {$cap} — Assigned: {$assignedCount}";
                } else {
                    $ins = $mysqli->prepare("INSERT INTO assignments (bus_id, route_id, driver_id, student_id) VALUES (?, ?, ?, ?)");
                    if ($ins) {
                        $ins->bind_param("iiii", $bus_id, $route_id, $driver_id, $student_id);
                        if ($ins->execute()) {
                            $success = "✅ Student assigned to bus successfully!";
                        } else {
                            $error = "❌ Database error: " . $ins->error;
                        }
                        $ins->close();
                    } else {
                        $error = "❌ DB error (prepare): " . $mysqli->error;
                    }
                }
            }
        } else {
            $error = "❌ DB error (student check): " . $mysqli->error;
        }
    }
}

$ever_assigned_students = [];
$res_ever = $mysqli->query("SELECT DISTINCT student_id FROM assignments WHERE student_id IS NOT NULL");
if ($res_ever instanceof mysqli_result) {
    while ($r = mysqli_fetch_assoc($res_ever)) {
        $ever_assigned_students[] = intval($r['student_id']);
    }
    $res_ever->free();
}

$assigned_students_ids = [];
if ($driver_id) {
    $aq = $mysqli->prepare("SELECT student_id FROM assignments WHERE driver_id = ? AND student_id IS NOT NULL");
    if ($aq) {
        $aq->bind_param("i", $driver_id);
        $aq->execute();
        $aq->bind_result($sid);
        while ($aq->fetch()) {
            $assigned_students_ids[] = intval($sid);
        }
        $aq->close();
    }
}

$students = [];
$sr = $mysqli->query("SELECT student_id, full_name, class FROM students ORDER BY full_name ASC");
if ($sr instanceof mysqli_result) {
    while ($r = mysqli_fetch_assoc($sr)) $students[] = $r;
    $sr->free();
}
$assigned_table_rows = [];
if ($driver_id) {
    $qr = $mysqli->prepare("
        SELECT a.assignment_id, s.full_name, s.class
        FROM assignments a
        JOIN students s ON s.student_id = a.student_id
        WHERE a.driver_id = ? AND a.student_id IS NOT NULL
        ORDER BY a.assignment_id DESC
    ");
    if ($qr) {
        $qr->bind_param("i", $driver_id);
        $qr->execute();
        $res = $qr->get_result();
        while ($row = $res->fetch_assoc()) $assigned_table_rows[] = $row;
        $qr->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<title>Assign Students to Driver</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg,#a1c4fd,#c2e9fb); margin:0; padding:0; }
.container { width:90%; max-width:1000px; margin:36px auto; background:#fff; padding:22px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.12); }
h2 { text-align:center; color:#222; }
.back-btn { display:inline-block; margin-bottom:14px; padding:8px 12px; background:#1e90ff; color:#fff; text-decoration:none; border-radius:8px; }
.form-box { background:#f7f9fc; padding:18px; border-radius:10px; margin-bottom:20px; }
label { font-weight:600; display:block; margin-top:12px; color:#333; }
select, button, input[type="text"] { width:100%; padding:10px; margin-top:8px; border:1px solid #ddd; border-radius:8px; font-size:14px; box-sizing:border-box; }
button { background:#4cafef; color:white; border:none; cursor:pointer; }
.alert { padding:10px; border-radius:6px; margin-bottom:12px; }
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
table { width:100%; border-collapse:collapse; margin-top:18px; }
th, td { padding:10px; text-align:center; border-bottom:1px solid #eee; }
th { background:#4cafef; color:#fff; }
tr:nth-child(even){ background:#f9fcff; }
.delete-btn { background:#ff4d4f; color:white; padding:6px 10px; border:none; border-radius:6px; cursor:pointer; }
.add-btn { background:#28a745; color:#fff; padding:6px 10px; border:none; border-radius:6px; cursor:pointer; }
.small { font-size:13px; color:#555; margin-top:6px; display:block; }
.search { padding:8px; width:100%; box-sizing:border-box; border-radius:6px; border:1px solid #ccc; margin-top:8px; }
.note { font-size:13px; color:#666; margin-top:6px; }
</style>
</head>
<body>
<div class="container">
    <a href="assign_buses.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Bus Assignments</a>
    <h2><i class="fa-solid fa-user-plus"></i> Assign Students to Driver</h2>

    <div class="form-box">
        <p><strong>Bus:</strong> <?= htmlspecialchars($bus_number ?: '-') ?></p>
        <p><strong>Driver:</strong> <?= htmlspecialchars($driver_name ?: '-') ?></p>
        
    </div>

    <?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form method="POST" class="form-box">
        
        <label><i class="fa-solid fa-user-graduate"></i> Select Student:</label>
        <select name="student_id" id="student_select" required>
            <option value="">-- Select Student --</option>
            <?php foreach ($students as $s):
                $sid = intval($s['student_id']);
                
                if (in_array($sid, $ever_assigned_students, true)) continue;
                
                if (in_array($sid, $assigned_students_ids, true)) continue;
            ?>
                <option value="<?= $sid ?>" data-label="<?= htmlspecialchars(strtolower($s['full_name'] . ' ' . ($s['class'] ?? ''))) ?>">
                    <?= htmlspecialchars($s['full_name']) ?><?= !empty($s['class']) ? " | Class: ".htmlspecialchars($s['class']) : '' ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="bus_id" value="<?= (int)$bus_id ?>">
        <input type="hidden" name="driver_id" value="<?= (int)$driver_id ?>">
        <input type="hidden" name="route_id" value="<?= (int)$route_id ?>">

        <button type="submit" class="add-btn" style="margin-top:12px;"><i class="fa-solid fa-check"></i> Assign Student</button>
    </form>

    <h3>Students Assigned to this Driver</h3>
    <table>
        <thead>
            <tr><th>Student</th><th>Class</th><th>Action</th></tr>
        </thead>
        <tbody id="assigned_tbody">
            <?php if (!empty($assigned_table_rows)): ?>
                <?php foreach ($assigned_table_rows as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['full_name']) ?></td>
                        <td><?= htmlspecialchars($r['class']) ?></td>
                        <td>
                            <form method="POST" action="delete_assignment.php" onsubmit="return confirm('Are you sure you want to delete this assignment?')">
                                <input type="hidden" name="assignment_id" value="<?= (int)$r['assignment_id'] ?>">
                                <button type="submit" class="delete-btn"><i class="fa-solid fa-trash"></i> Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="3">No students assigned yet</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
document.getElementById('student_search').addEventListener('input', function() {
    const q = this.value.trim().toLowerCase();
    const opts = document.querySelectorAll('#student_select option');
    opts.forEach(opt => {
        if (!opt.value) return; 
        const label = (opt.dataset.label || opt.textContent).toLowerCase();
        opt.style.display = q === '' || label.includes(q) ? '' : 'none';
    });
});
</script>
</body>
</html>
