<?php
include 'db_connect.php'; 
session_start();
if (!isset($_GET['driver_id'])) {
    die("❌ Missing driver ID!");
}

$driver_id = intval($_GET['driver_id']); 

$query = $mysqli->prepare("SELECT * FROM drivers WHERE driver_id = ?");
$query->bind_param("i", $driver_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    die("❌ Driver not found!");
}

$driver = $result->fetch_assoc();
$query->close();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $bus_id         = intval($_POST['bus_id'] ?? 0);
    $assigned_route = trim($_POST['assigned_route'] ?? 'Not Assigned');
    $stops          = trim($_POST['stops'] ?? '[]');

    $update = $mysqli->prepare("UPDATE drivers 
                                SET bus_id = ?, assigned_route = ?, stops = ? 
                                WHERE driver_id = ?");
    $update->bind_param("issi", $bus_id, $assigned_route, $stops, $driver_id);

    if ($update->execute()) {
        header("Location: manage_users.php?update_success=1");
        exit();
    } else {
        echo "❌ Error updating driver: " . $update->error;
    }
    $update->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Driver</title>
</head>
<body>
    <h2>Update Driver - <?= htmlspecialchars($driver['full_name']) ?></h2>

    <form method="POST">
        <label>Bus ID</label>
        <input type="number" name="bus_id" value="<?= htmlspecialchars($driver['bus_id']) ?>" required><br><br>

        <label>Assigned Route</label>
        <input type="text" name="assigned_route" value="<?= htmlspecialchars($driver['assigned_route']) ?>" required><br><br>

        <label>Stops (JSON/Text)</label>
        <textarea name="stops" rows="4" cols="40"><?= htmlspecialchars($driver['stops']) ?></textarea><br><br>

        <button type="submit">Update Driver</button>
    </form>

    <a href="manage_users.php">⬅ Back</a>
</body>
</html>
