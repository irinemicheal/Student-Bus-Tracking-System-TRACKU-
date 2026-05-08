<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

if (isset($_GET['id'])) {
    $assignment_id = intval($_GET['id']);
    $delete = mysqli_query($conn, "DELETE FROM bus_assignments WHERE assignment_id='$assignment_id'");

    if ($delete) {
        header("Location: assign_buses.php?success=Assignment removed!");
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>
