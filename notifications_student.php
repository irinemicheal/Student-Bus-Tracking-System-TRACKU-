<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("\n    SELECT s.student_id, s.full_name, s.bus_id, s.pickup_stop_id\n    FROM students s\n    WHERE s.user_id = ? LIMIT 1\n");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id, $student_name, $bus_id, $pickup_stop_id);
$stmt->fetch();
$stmt->close();

$driver_ids = [];
if ($student_id) {
    $stmt = $mysqli->prepare("
        SELECT DISTINCT driver_id 
        FROM assignments 
        WHERE student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $driver_ids[] = $row['driver_id'];
    }
    $stmt->close();
}

$notifications = [];
if (!empty($driver_ids)) {
    $placeholders = implode(',', array_fill(0, count($driver_ids), '?'));
    $types = str_repeat('i', count($driver_ids));

    $sql = "
        SELECT message, created_at 
        FROM notifications 
        WHERE role='student' 
        AND driver_id IN ($placeholders)
        ORDER BY created_at DESC
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param($types, ...$driver_ids);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Notifications</title>
    <style>
        body { font-family: Arial, sans-serif; background:#19c0d661; margin:0; padding:0; }
        .container { max-width:800px; margin:40px auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); }
        h2 { text-align:center; color:#333; margin-bottom:20px; }
        .notification { border-bottom:1px solid #ddd; padding:12px 0; }
        .notification:last-child { border-bottom:none; }
        .message { font-size:15px; color:#444; }
        .time { font-size:12px; color:#888; }
        .empty { text-align:center; padding:20px; color:#777; }
        .back-btn { display:block; margin:20px auto 0; text-align:center; text-decoration:none; background:#0d6efd; color:white; padding:10px 20px; border-radius:5px; font-weight:bold; }
        .back-btn:hover { background:#0056b3; }
    </style>
</head>
<body>
<div class="container">
    <h2>Notifications for <?= htmlspecialchars($student_name) ?></h2>

    <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $note): ?>
            <div class="notification">
                <div class="message"><?= htmlspecialchars($note['message']) ?></div>
                <div class="time">📅 <?= date("d M Y, h:i A", strtotime($note['created_at'])) ?></div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="empty">No notifications yet from your assigned driver(s).</p>
    <?php endif; ?>

    <a href="studentdash.php" class="back-btn">⬅ Back to Dashboard</a>
</div>
</body>
</html>
