<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_number = mysqli_real_escape_string($mysqli, $_POST['bus_number']);
    $capacity = (int)$_POST['capacity'];

    $query = "INSERT INTO buses (bus_number, capacity) VALUES ('$bus_number', '$capacity')";
    if (mysqli_query($mysqli, $query)) {
        header("Location: manage_buses.php?msg=Bus Added Successfully");
        exit();
    } else {
        echo "Error: " . mysqli_error($mysli);
    }
}
?>
