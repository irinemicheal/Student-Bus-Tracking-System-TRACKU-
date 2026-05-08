<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
$user_id = $_SESSION['user_id'];
$parent_name = '';
$assigned_student_id = null;
$stmt = $mysqli->prepare("SELECT full_name, student_id FROM parents WHERE user_id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($parent_name, $assigned_student_id);
$stmt->fetch();
$stmt->close();

$route_id = null; $driver_id = null;
if (!empty($assigned_student_id)) {
    $ar = $mysqli->query("SELECT route_id, driver_id FROM assignments WHERE student_id = " . intval($assigned_student_id) . " LIMIT 1")->fetch_assoc();
    if ($ar) {
        $route_id = $ar['route_id'] ?? null;
        $driver_id = $ar['driver_id'] ?? null;
    }
}
$notifications = [];
if ($route_id || $driver_id) {
    $conds = [];
    $types = '';
    $binds = [];
    if ($route_id) { $conds[] = 'n.route_id = ?'; $types .= 'i'; $binds[] = $route_id; }
    if ($driver_id) { $conds[] = 'n.driver_id = ?'; $types .= 'i'; $binds[] = $driver_id; }

    $q = "SELECT n.message, n.created_at, r.route_name, n.driver_id FROM notifications n LEFT JOIN routes r ON n.route_id = r.route_id WHERE n.role='parent' AND (" . implode(' OR ', $conds) . ") ORDER BY n.created_at DESC";
    $stmt = $mysqli->prepare($q);
    if ($stmt) {
        if (!empty($binds)) {
            $refs = [];
            $refs[] = & $types;
            for ($i = 0; $i < count($binds); $i++) { $refs[] = & $binds[$i]; }
            call_user_func_array([$stmt, 'bind_param'], $refs);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) $notifications[] = $row;
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Parent Notifications</title>
<style>
body { font-family: Arial, sans-serif; background:#19c0d661; margin:0; padding:0; }
.container { max-width:800px; margin:40px auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.15); }
h2 { text-align:center; color:#333; margin-bottom:20px; }
.notification { border-bottom:1px solid #ddd; padding:12px 0; }
.notification:last-child { border-bottom:none; }
.message { font-size:15px; color:#444; }
.route { font-size:13px; color:#555; margin-top:3px; }
.time { font-size:12px; color:#888; margin-top:2px; }
.empty { text-align:center; padding:20px; color:#777; }
.back-btn { display:block; margin:20px auto 0; text-align:center; text-decoration:none; background:#0d6efd; color:white; padding:10px 20px; border-radius:5px; font-weight:bold; }
.back-btn:hover { background:#0056b3; }
</style>
</head>
<body>
<div class="container">
<h2>Notifications for <?= htmlspecialchars($parent_name ?: 'Parent') ?></h2>

<?php if (!empty($notifications)): ?>
    <?php foreach ($notifications as $note): ?>
        <div class="notification">
            <div class="message"><?= htmlspecialchars($note['message']) ?></div>
            <?php if (!empty($note['route_name'])): ?>
                <div class="route">🛣 Route: <?= htmlspecialchars($note['route_name']) ?></div>
            <?php endif; ?>
            <div class="time">📅 <?= date("d M Y, h:i A", strtotime($note['created_at'])) ?></div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p class="empty">No notifications yet for parents.</p>
<?php endif; ?>

<a href="parentdash.php" class="back-btn">⬅ Back to Dashboard</a>
</div>
</body>
</html>
