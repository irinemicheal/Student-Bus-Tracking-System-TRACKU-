<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

include 'db_connect.php';

if (isset($_POST['feedback_id'])) {
    $feedback_id = intval($_POST['feedback_id']);

    $stmt = $mysqli->prepare("UPDATE feedback SET status='Marked' WHERE feedback_id=?");
    $stmt->bind_param("i", $feedback_id);
    $success = $stmt->execute();
    $stmt->close();

    echo json_encode(["success" => $success]);
    exit();
}

echo json_encode(["success" => false, "message" => "Invalid Request"]);
exit();
?>
