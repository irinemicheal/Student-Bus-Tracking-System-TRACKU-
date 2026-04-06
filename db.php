<?php
$host = "localhost";
$user = "root";       
$pass = "";         
$db   = "studentbustracking";  

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error);
}
?>

