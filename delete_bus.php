<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

if (isset($_GET['id'])) {
    $bus_id = $_GET['id'];

    $query = "DELETE FROM buses WHERE bus_id=$bus_id";
    if (mysqli_query($mysqli, $query)) {
        header("Location: manage_buses.php?msg=Bus Deleted Successfully");
        exit();
    } else {
        echo "Error: " . mysqli_error($mysqli);
    }
}
?>
