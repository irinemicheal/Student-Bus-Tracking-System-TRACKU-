<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bus_id = intval($_POST['bus_id']);
    $driver_id = intval($_POST['driver_id']);

    
    $check = mysqli_query($conn, "SELECT * FROM bus_assignments WHERE bus_id='$bus_id' OR driver_id='$driver_id'");
    if (mysqli_num_rows($check) > 0) {
        header("Location: assign_buses.php?error=Bus or Driver already assigned!");
        exit();
    }

    $query = "INSERT INTO bus_assignments (bus_id, driver_id) VALUES ('$bus_id', '$driver_id')";
    if (mysqli_query($conn, $query)) {
        header("Location: assign_buses.php?success=Bus assigned successfully!");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
