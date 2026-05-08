<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$stmt = $mysqli->prepare("
    SELECT d.driver_id, b.bus_id, b.bus_number, r.route_id, r.route_name
    FROM drivers d
    JOIN users u ON d.user_id = u.user_id
    JOIN assignments a ON d.driver_id = a.driver_id
    JOIN buses b ON a.bus_id = b.bus_id
    JOIN routes r ON a.route_id = r.route_id
    WHERE u.username = ? LIMIT 1
");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$driverData = $result->fetch_assoc();
$stmt->close();

if (!$driverData) {
    die("No bus or route assigned to this driver.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_trip'])) {
    $trip_date = $_POST['trip_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $status = $_POST['status'];

    $stmt = $mysqli->prepare("INSERT INTO trips (bus_id, route_id, trip_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $driverData['bus_id'], $driverData['route_id'], $trip_date, $start_time, $end_time, $status);
    if($stmt->execute()) {
        $success = "Trip added successfully!";
    } else {
        $error = "Database error: " . $mysqli->error;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_trip'])) {
    $trip_id = intval($_POST['trip_id']);
    $stmt = $mysqli->prepare("DELETE FROM trips WHERE trip_id = ? AND bus_id = ? AND route_id = ?");
    $stmt->bind_param("iii", $trip_id, $driverData['bus_id'], $driverData['route_id']);
    if($stmt->execute()) {
        $success = "Trip deleted successfully!";
    } else {
        $error = "Database error: " . $mysqli->error;
    }
    $stmt->close();
}

// Fetch trips only for this driver’s assigned bus & route
$trips = $mysqli->query("
    SELECT * FROM trips
    WHERE bus_id = {$driverData['bus_id']} AND route_id = {$driverData['route_id']}
    ORDER BY trip_date DESC, start_time DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Past Trips Details</title>
<style>
body { font-family: Arial,sans-serif; background:#19c0d661; margin:0; padding:0; }
.container { max-width:900px; margin:40px auto; padding:20px; background:white; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#0d6efd; }
form { display:flex; flex-direction:column; gap:10px; margin-bottom:20px; }
input, select { padding:8px 12px; border:1px solid #ccc; border-radius:6px; font-size:14px; }
input[readonly] { background:#e9ecef; cursor:not-allowed; }
button { padding:8px 16px; background:#0d6efd; color:white; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
button:hover { background:#0056b3; }
.delete-btn { background:#dc3545; }
.delete-btn:hover { background:#a71d2a; }
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:12px; border:1px solid #ddd; text-align:center; }
th { background:#0d6efd; color:white; }
.success { color:green; text-align:center; margin-bottom:10px; }
.error { color:red; text-align:center; margin-bottom:10px; }
.back-btn { display:inline-block; margin-top:20px; text-decoration:none; background:#0d6efd; color:white; padding:10px 20px; border-radius:6px; }
.back-btn:hover { background:#0056b3; }
</style>
</head>
<body>

<div class="container">
<h2>Manage Past Trips Details</h2>

<?php if(isset($success)) echo "<p class='success'>$success</p>"; ?>
<?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST">
    <label>Bus:</label>
    <input type="text" value="<?= htmlspecialchars($driverData['bus_number']) ?>" readonly>

    <label>Route:</label>
    <input type="text" value="<?= htmlspecialchars($driverData['route_name']) ?>" readonly>

    <label>Trip Date:</label>
    <input type="date" name="trip_date" required max="<?= date('Y-m-d') ?>">

    <label>Start Time:</label>
    <input type="time" name="start_time" required>

    <label>End Time:</label>
    <input type="time" name="end_time" required>

    <label>Status:</label>
    <select name="status" required>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
        <option value="delayed">Delayed</option>
        <option value="breakdown">Breakdown</option>
    </select>

    <button type="submit" name="add_trip">Add Trip</button>
</form>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Bus</th>
            <th>Route</th>
            <th>Start Time</th>
            <th>End Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if($trips->num_rows > 0): ?>
            <?php while($t = $trips->fetch_assoc()): ?>
            <tr>
                <td><?= $t['trip_date'] ?></td>
                <td><?= htmlspecialchars($driverData['bus_number']) ?></td>
                <td><?= htmlspecialchars($driverData['route_name']) ?></td>
                <td><?= $t['start_time'] ?></td>
                <td><?= $t['end_time'] ?></td>
                <td><?= ucfirst($t['status']) ?></td>
                <td>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="trip_id" value="<?= $t['trip_id'] ?>">
                        <button type="submit" name="delete_trip" class="delete-btn" onclick="return confirm('Are you sure you want to delete this trip?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7">No trips found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="driverdash.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

</body>
</html>
