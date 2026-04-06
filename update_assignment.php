<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $assignment_id = intval($_POST['assignment_id']);
    $bus_id = intval($_POST['bus_id']);
    $driver_id = intval($_POST['driver_id']);
    $check = mysqli_query($conn, "SELECT * FROM bus_assignments 
        WHERE (bus_id='$bus_id' OR driver_id='$driver_id') 
        AND assignment_id != '$assignment_id'");
    if (mysqli_num_rows($check) > 0) {
        header("Location: assign_buses.php?error=Bus or Driver already assigned!");
        exit();
    }

    $query = "UPDATE bus_assignments SET bus_id='$bus_id', driver_id='$driver_id' WHERE assignment_id='$assignment_id'";
    if (mysqli_query($conn, $query)) {
        header("Location: assign_buses.php?success=Assignment updated!");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
