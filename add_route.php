<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_POST['route_name'], $_POST['start_point'], $_POST['end_point'], $_POST['stops'])) {
    die("All fields are required.");
}

$route_name = trim($_POST['route_name']);
$start_point = trim($_POST['start_point']);
$end_point = trim($_POST['end_point']);
$stops = $_POST['stops']; 
try {
    $mysqli->begin_transaction();
    $stmtRoute = $mysqli->prepare("INSERT INTO routes (route_name, start_point, end_point) VALUES (?, ?, ?)");
    $stmtRoute->bind_param("sss", $route_name, $start_point, $end_point);
    $stmtRoute->execute();
    $route_id = $stmtRoute->insert_id; 
    $stmtRoute->close();
    $stmtStop = $mysqli->prepare("INSERT INTO stops (route_id, stop_name) VALUES (?, ?)");
    foreach ($stops as $stop_name) {
        $stop_name = trim($stop_name);
        if ($stop_name !== "") {
            $stmtStop->bind_param("is", $route_id, $stop_name);
            $stmtStop->execute();
        }
    }
    $stmtStop->close();
    $mysqli->commit();
    header("Location: manage_routes.php?msg=Route+added+successfully");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    die("Error adding route: " . $e->getMessage());
}
?>
