<?php
declare(strict_types=1);
header('Content-Type: application/json');
include 'db.php';

$type = $_GET['type'] ?? '';
$value = trim($_GET['value'] ?? '');

function send($status) {
    echo json_encode(['status' => $status]);
    exit;
}
if ($type === 'username') {
    if (strlen($value) < 3) send('short');
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
if ($type === 'email') {
    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) send('invalid');
    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
if ($type === 'phone') {
    $phone = preg_replace('/\D+/', '', $value);
    if (!preg_match('/^[6-9][0-9]{9}$/', $phone)) send('invalid_format');

    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE phone_no = ? LIMIT 1");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
if ($type === 'roll_number') {
    $class = $_GET['class'] ?? '';
    if (!$class) send('required_class');
    $roll = $value;
    $stmt = $mysqli->prepare("SELECT student_id FROM students WHERE class = ? AND roll_number = ? LIMIT 1");
    $stmt->bind_param("ss", $class, $roll);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
if ($type === 'student_unique_id') {
    $stmt = $mysqli->prepare("SELECT student_id FROM students WHERE student_unique_id = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
if ($type === 'admin_code') {
    $stmt = $mysqli->prepare("SELECT code_id FROM admin_codes WHERE code = ? AND used = 0 LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'ok' : 'taken'); 
}

if ($type === 'driving_licence') {
    $stmt = $mysqli->prepare("SELECT driver_id FROM drivers WHERE driving_licence = ? LIMIT 1");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $r = $stmt->get_result();
    send($r && $r->num_rows ? 'taken' : 'ok');
}
send('invalid');
?>
