<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$query = "SELECT d.driver_id, a.bus_id
          FROM drivers d
          JOIN users u ON d.user_id = u.user_id
          JOIN assignments a ON d.driver_id = a.driver_id
          WHERE u.username = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
$driver = $res->fetch_assoc();
$stmt->close();

if (!$driver) {
    die("Driver not found or not assigned to a bus. Please contact admin.");
}

$driver_id = $driver['driver_id'];
$bus_id    = $driver['bus_id'];
$stop_id = $_POST['stop_id'] ?? null;
$status  = $_POST['status'] ?? null;

if (!$stop_id || !$status) {
    die("Please select both stop and status.");
}

$query = "INSERT INTO bus_status (bus_id, driver_id, current_stop_id, status, updated_at)
          VALUES (?, ?, ?, ?, NOW())
          ON DUPLICATE KEY UPDATE 
              current_stop_id = VALUES(current_stop_id),
              status = VALUES(status),
              updated_at = NOW()";

$stmt = $mysqli->prepare($query);
$stmt->bind_param("iiis", $bus_id, $driver_id, $stop_id, $status);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: update_status.php?success=1");
    exit();
} else {
    die("Error updating status: " . $stmt->error);
}
?>
