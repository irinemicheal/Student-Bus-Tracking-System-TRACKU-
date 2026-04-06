<?php
session_start();
include 'db.php';

$username = $_SESSION['username'] ?? null;

if (!$username || $_SESSION['role'] !== 'student') {
    die("Unauthorized access. Please login as student.");
}
$stmt = $mysqli->prepare("SELECT student_id FROM students WHERE full_name = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    die("Student not found.");
}

$student_id = $student['student_id'];
$sql = "SELECT s.student_id, s.full_name, b.bus_number, 
               ps.stop_name AS pickup_stop, ds.stop_name AS drop_stop,
               r.route_name, d.full_name AS driver_name,
               b.bus_id, r.route_id, s.pickup_stop_id
        FROM students s
        JOIN buses b ON s.bus_id = b.bus_id
        JOIN routes r ON s.route_id = r.route_id
        JOIN drivers d ON b.drivers_id = d.driver_id
        LEFT JOIN stops ps ON s.pickup_stop_id = ps.stop_id
        LEFT JOIN stops ds ON s.drop_stop_id = ds.stop_id
        WHERE s.student_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$studentData = $stmt->get_result()->fetch_assoc();
$sql2 = "SELECT l.current_stop_id, l.estimated_time, st.stop_name AS current_stop
         FROM live_location l
         JOIN stops st ON l.current_stop_id = st.stop_id
         WHERE l.bus_id = ? 
         ORDER BY l.updated_at DESC LIMIT 1";
$stmt2 = $mysqli->prepare($sql2);
$stmt2->bind_param("i", $studentData['bus_id']);
$stmt2->execute();
$locationData = $stmt2->get_result()->fetch_assoc();
$eta = $locationData ? $locationData['estimated_time'] : "Not Available";
?>
<!DOCTYPE html>
<html>
<head>
    <title>ETA</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            text-align: center;
            background: linear-gradient(to right, #e3f2fd, #f4f8ff);
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 60px auto;
            max-width: 650px;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        h2 {
            color: #0d6efd;
            margin-bottom: 15px;
        }
        .eta-box {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 20px 0;
            padding: 15px;
            border-radius: 10px;
            background: #e9f2ff;
        }
        .progress {
            position: relative;
            height: 20px;
            width: 100%;
            background: #ddd;
            border-radius: 10px;
            overflow: hidden;
            margin: 25px 0;
        }
        .progress-bar {
            height: 100%;
            width: 50%; /* can be dynamic */
            background: linear-gradient(to right, #0d6efd, #00c6ff);
            transition: width 0.5s ease;
        }
        .stop-info {
            text-align: left;
            margin-top: 20px;
        }
        .stop-info p {
            margin: 8px 0;
            font-size: 16px;
        }
        .label {
            font-weight: bold;
            color: #0a58ca;
        }
        a.back {
            display: inline-block;
            margin-top: 30px;
            text-decoration: none;
            padding: 12px 25px;
            background: #0d6efd;
            color: white;
            font-weight: bold;
            border-radius: 8px;
            transition: 0.3s;
        }
        a.back:hover {
            background: #0a58ca;
            transform: scale(1.05);
        }
    </style>
    <meta http-equiv="refresh" content="30">
</head>
<body>
    <div class="container">
        <h2>🚍 Estimated Time of Arrival</h2>
        
        <?php if ($studentData && $locationData): ?>
            <div class="eta-box">⏳ Your bus will arrive in 
                <span style="color:#0d6efd;"><?= htmlspecialchars($eta) ?></span>
            </div>
            <div class="progress">
                <div class="progress-bar" style="width: <?= rand(30,80) ?>%;"></div>
            </div>
            <div class="stop-info">
                <p><span class="label">Bus Number:</span> <?= htmlspecialchars($studentData['bus_number']) ?></p>
                <p><span class="label">Driver:</span> <?= htmlspecialchars($studentData['driver_name']) ?></p>
                <p><span class="label">Current Stop:</span> <?= htmlspecialchars($locationData['current_stop']) ?></p>
                <p><span class="label">Your Stop:</span> <?= htmlspecialchars($studentData['pickup_stop']) ?></p>
            </div>
        <?php else: ?>
            <p style="color:red;">No live tracking data available.</p>
        <?php endif; ?>

        <a class="back" href="studentdash.php">⬅ Back to Dashboard</a>
    </div>
</body>
</html>



