<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $stmt = $mysqli->prepare("SELECT bus_id, driver_id FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $stmt->bind_result($bus_id, $driver_id);
    $stmt->fetch();
    $stmt->close();
    $stmt = $mysqli->prepare("DELETE FROM assignments WHERE assignment_id = ?");
    $stmt->bind_param("i", $assignment_id);
    if ($stmt->execute()) {
        $stmt->close();
        if($driver_id) {
            header("Location: assign_student_to_driver.php?assignment_id=$assignment_id");
        } else {
            
            header("Location: assign_buses.php");
        }
        exit();
    } else {
        echo "❌ Error deleting assignment: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "⚠️ No assignment specified!";
}
?>
