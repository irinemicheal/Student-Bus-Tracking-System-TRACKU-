<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$username = $_SESSION['username'];

$q = mysqli_query($mysqli, "
    SELECT b.bus_id
    FROM students s
    JOIN users u ON s.user_id=u.user_id
    JOIN assignments a ON s.route_id=a.route_id
    JOIN buses b ON a.bus_id=b.bus_id
    WHERE u.username='$username'
    LIMIT 1
");

$bus = mysqli_fetch_assoc($q);

if(!$bus){
    echo json_encode(["error"=>"No bus assigned"]);
    exit();
}

$bus_id = $bus['bus_id'];

$res = mysqli_query($mysqli, "
    SELECT current_position, remaining_time, is_started
    FROM bus_live WHERE bus_id=$bus_id LIMIT 1
");

$data = mysqli_fetch_assoc($res);
echo json_encode($data ?: ["is_started"=>0]);
