<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
require_once "db.php";

$success = $error = "";

// Handle Permanent Assignment
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['bus_id'], $_POST['route_id'], $_POST['driver_id'])) {
    $bus_id = intval($_POST['bus_id']);
    $route_id = intval($_POST['route_id']);
    $driver_id = intval($_POST['driver_id']);

    if (!$bus_id || !$route_id || !$driver_id) {
        $error = "⚠️ Please select all fields!";
    } else {
        $check = $mysqli->query("
            SELECT 1 FROM assignments 
            WHERE (bus_id=$bus_id OR route_id=$route_id OR driver_id=$driver_id)
            AND student_id IS NULL LIMIT 1
        ");
        if ($check && $check->num_rows > 0) {
            $error = "⚠️ This Bus / Route / Driver is already permanently assigned!";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO assignments (bus_id, route_id, driver_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $bus_id, $route_id, $driver_id);
            if ($stmt->execute()) $success = "✅ Permanent assignment added!";
            else $error = "❌ Insert Error: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Delete a permanent assignment
if (isset($_GET['delete'])) {
    $assignment_id = intval($_GET['delete']);
    $mysqli->query("DELETE FROM assignments WHERE assignment_id=$assignment_id AND student_id IS NULL");
    $success = "✅ Assignment removed!";
}

// Fetch currently locked assignments
$assignedData = $mysqli->query("SELECT bus_id, route_id, driver_id FROM assignments WHERE student_id IS NULL");
$assignedBuses = $assignedRoutes = $assignedDrivers = [];
while ($row = $assignedData->fetch_assoc()) {
    $assignedBuses[] = $row['bus_id'];
    $assignedRoutes[] = $row['route_id'];
    $assignedDrivers[] = $row['driver_id'];
}

// Dropdown Data
$buses = $mysqli->query("SELECT bus_id, bus_number FROM buses");
$routes = $mysqli->query("SELECT route_id, route_name FROM routes");
$drivers = $mysqli->query("SELECT driver_id, full_name FROM drivers");

// Fetch assigned list to display
$assignedList = $mysqli->query("
    SELECT a.assignment_id, b.bus_number, r.route_name, d.full_name
    FROM assignments a
    JOIN buses b ON b.bus_id=a.bus_id
    JOIN routes r ON r.route_id=a.route_id
    JOIN drivers d ON d.driver_id=a.driver_id
    WHERE a.student_id IS NULL
    ORDER BY a.assignment_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Route Assignment Lock</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg,#a1c4fd,#c2e9fb); margin:0; padding:0; color:#333; }
.container { max-width:1000px; margin:30px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.15);}
h2 { text-align:center; color:#2c3e50; margin-bottom:20px;}
.back-btn { display:inline-block; margin-bottom:15px; background:#00c6ff; color:#fff; padding:8px 14px; border-radius:8px; text-decoration:none; transition:0.3s;}
.back-btn:hover { background:#0096d6; }

label { font-weight:600; margin-top:10px; display:block;}
select, button { width:100%; padding:12px; margin-top:6px; border-radius:8px; border:1px solid #ccc; font-size:14px;}
button { background:#2c3e50; color:#fff; font-size:16px; border:none; cursor:pointer; transition:0.3s;}
button:hover { background:#1a252f;}

.success, .error { padding:12px; border-radius:6px; margin-bottom:15px; font-weight:600;}
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb;}
.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }

/* Table */
table { width:100%; border-collapse:collapse; margin-top:25px; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05);}
th { background:#2c3e50; color:#fff; padding:12px; text-align:center;}
td { padding:12px; border-bottom:1px solid #ddd; text-align:center;}
tr:nth-child(even){ background:#f9f9f9; }

.delete { background:#ff4444; color:white; padding:6px 10px; border-radius:6px; text-decoration:none; transition:0.3s;}
.delete:hover { background:#cc0000; }

.select2-container--default .select2-selection--single { height:42px; border-radius:6px; }
</style>
</head>

<body>
<div class="container">
<a href="admin.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to dashboard</a>

<h2><i class="fas fa-lock"></i> Assign Bus & Route to Driver</h2>

<?php if ($error) echo "<div class='error'>$error</div>"; ?>
<?php if ($success) echo "<div class='success'>$success</div>"; ?>

<form method="POST">
    <label><i class="fa-solid fa-bus"></i> Select Bus</label>
    <select name="bus_id" required>
        <option value="">-- Select Bus --</option>
        <?php while($b = $buses->fetch_assoc()): ?>
        <option value="<?= $b['bus_id'] ?>" <?= in_array($b['bus_id'], $assignedBuses) ? 'disabled class="disabled"' : '' ?>>
            <?= $b['bus_number'] ?><?= in_array($b['bus_id'], $assignedBuses) ? ' (Assigned)' : '' ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label><i class="fa-solid fa-map"></i> Select Route</label>
    <select name="route_id" required>
        <option value="">-- Select Route --</option>
        <?php while($r = $routes->fetch_assoc()): ?>
        <option value="<?= $r['route_id'] ?>" <?= in_array($r['route_id'], $assignedRoutes) ? 'disabled class="disabled"' : '' ?>>
            <?= $r['route_name'] ?><?= in_array($r['route_id'], $assignedRoutes) ? ' (Assigned)' : '' ?>
        </option>
        <?php endwhile; ?>
    </select>

    <label><i class="fa-solid fa-user-tie"></i> Select Driver</label>
    <select name="driver_id" required>
        <option value="">-- Select Driver --</option>
        <?php while($d = $drivers->fetch_assoc()): ?>
        <option value="<?= $d['driver_id'] ?>" <?= in_array($d['driver_id'], $assignedDrivers) ? 'disabled class="disabled"' : '' ?>>
            <?= $d['full_name'] ?><?= in_array($d['driver_id'], $assignedDrivers) ? ' (Assigned)' : '' ?>
        </option>
        <?php endwhile; ?>
    </select>

    <button type="submit"><i class="fas fa-check"></i> Lock Assignment</button>
</form>

<h3 style="text-align:center; margin-top:25px;">Locked Assignments</h3>
<table>
<tr>
    <th>Bus</th>
    <th>Route</th>
    <th>Driver</th>
    <th>Action</th>
</tr>

<?php if ($assignedList->num_rows > 0): ?>
<?php while($a = $assignedList->fetch_assoc()): ?>
<tr>
    <td><?= $a['bus_number'] ?></td>
    <td><?= $a['route_name'] ?></td>
    <td><?= $a['full_name'] ?></td>
    <td>
        <a class="delete" href="?delete=<?= $a['assignment_id'] ?>" onclick="return confirm('Remove this permanent assignment?')">
            <i class="fa-solid fa-trash"></i> Remove
        </a>
    </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="4">No locked assignments yet.</td></tr>
<?php endif; ?>
</table>
</div>
</body>
</html>
