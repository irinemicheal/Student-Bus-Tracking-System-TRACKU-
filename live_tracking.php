<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}
$parent_id = $_SESSION['user_id'] ?? null;
if (!$parent_id) die("❌ Parent not found.");

$stmt = $mysqli->prepare("SELECT student_id, full_name FROM students WHERE parent_id = ?");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$student_result = $stmt->get_result();
$student = $student_result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("❌ No child found for this parent.");
}
$student_name = $student['full_name'];
$student_id = $student['student_id'];

$stmt = $mysqli->prepare("
    SELECT a.bus_id, b.bus_number, a.route_id, r.route_name, d.driver_id, u.full_name AS driver_name
    FROM assignments a
    JOIN buses b ON a.bus_id = b.bus_id
    JOIN routes r ON a.route_id = r.route_id
    JOIN drivers d ON a.driver_id = d.driver_id
    JOIN users u ON d.user_id = u.user_id
    WHERE b.bus_id = (SELECT bus_id FROM students WHERE student_id = ?)
    LIMIT 1
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$assignment_result = $stmt->get_result();
$assignment = $assignment_result->fetch_assoc();
$stmt->close();

if (!$assignment) {
    die("❌ Your child is not assigned to any bus or route.");
}
$route_id = $assignment['route_id'];
$stmt = $mysqli->prepare("SELECT stop_name FROM stops WHERE route_id = ? ORDER BY stop_id ASC");
$stmt->bind_param("i", $route_id);
$stmt->execute();
$stops_result = $stmt->get_result();
$stops = [];
while ($row = $stops_result->fetch_assoc()) {
    $stops[] = $row['stop_name'];
}
$stmt->close();
$driver_id = $assignment['driver_id'];
$bus_id = $assignment['bus_id'];
$stmt = $mysqli->prepare("SELECT status, updated_at FROM bus_status WHERE bus_id=? AND driver_id=? ORDER BY updated_at DESC LIMIT 1");
$stmt->bind_param("ii", $bus_id, $driver_id);
$stmt->execute();
$status_result = $stmt->get_result();
$latest_status = $status_result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Tracking | Parent Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background: #f4f7fe; margin:0; padding:0; }
.header { background:#1a73e8; color:white; padding:20px; display:flex; justify-content:space-between; align-items:center; }
.header h2 { margin:0; }
.logout-btn { background:#ff4757; padding:10px 18px; color:white; text-decoration:none; border-radius:6px; }
.logout-btn:hover { background:#e84118; }
.container { max-width:700px; margin:40px auto; background:white; padding:30px; border-radius:10px; box-shadow:0 6px 20px rgba(0,0,0,0.1); }
.info { background:#eaf4ff; padding:15px; border-left:6px solid #0d6efd; margin-bottom:15px; border-radius:8px; }
.info strong { color:#0d6efd; }
.stops-list { margin-top:20px; }
.stop-item { background:#eef5ff; padding:10px; margin:8px 0; border-radius:6px; border-left:4px solid #0d6efd; }
.status { background:#fff3cd; color:#856404; padding:12px; border-radius:6px; margin-top:15px; border-left:6px solid #ffc107; }
.back-btn { display:inline-block; margin-top:20px; padding:10px 18px; background:#0d6efd; color:white; text-decoration:none; border-radius:6px; }
.back-btn:hover { background:#0a58ca; }
</style>
</head>
<body>

<div class="header">
    <h2>Parent Dashboard | <?= htmlspecialchars($student_name) ?></h2>
    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="container">
    <div class="info"><strong>Bus No:</strong> <?= htmlspecialchars($assignment['bus_number']) ?></div>
    <div class="info"><strong>Route:</strong> <?= htmlspecialchars($assignment['route_name']) ?></div>
    <div class="info"><strong>Driver:</strong> <?= htmlspecialchars($assignment['driver_name']) ?></div>

    <div class="stops-list">
        <h3>Stops:</h3>
        <?php if(!empty($stops)): ?>
            <?php foreach($stops as $index => $stop): ?>
                <div class="stop-item"><?= ($index+1) . ". " . htmlspecialchars($stop) ?></div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No stops assigned yet.</p>
        <?php endif; ?>
    </div>

    <div class="status">
        <strong>Latest Status:</strong>
        <?= $latest_status ? htmlspecialchars($latest_status['status']) . " (Updated: " . $latest_status['updated_at'] . ")" : "No updates yet." ?>
    </div>

    <a class="back-btn" href="parent_dashboard.php">⬅ Back to Dashboard</a>
</div>

</body>
</html>
