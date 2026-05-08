<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$uid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($uid <= 0) {
    header("Location: manage_users.php?msg=invalid_id");
    exit();
}
$stmt = $mysqli->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    header("Location: manage_users.php?msg=user_not_found");
    exit();
}
$row = $res->fetch_assoc();
$role = $row['role'];
$stmt->close();

$mysqli->begin_transaction();
try {
    if ($role === 'student') {
        $del = $mysqli->prepare("DELETE FROM students WHERE user_id = ?");
        $del->bind_param("i", $uid);
        $del->execute();
        $del->close();
    } elseif ($role === 'parent') {
        $del = $mysqli->prepare("DELETE FROM parents WHERE user_id = ?");
        $del->bind_param("i", $uid);
        $del->execute();
        $del->close();
    } elseif ($role === 'driver') {
        $del = $mysqli->prepare("DELETE FROM drivers WHERE user_id = ?");
        $del->bind_param("i", $uid);
        $del->execute();
        $del->close();
    }

    $del2 = $mysqli->prepare("DELETE FROM users WHERE user_id = ?");
    $del2->bind_param("i", $uid);
    $del2->execute();
    $del2->close();

    $mysqli->commit();
    header("Location: manage_users.php?msg=deleted");
    exit();

} catch (Exception $e) {
    $mysqli->rollback();
    header("Location: manage_users.php?msg=delete_failed");
    exit();
}
