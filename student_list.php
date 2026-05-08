<?php  
session_start();
include 'db_connect.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id']; 

$stmt = $mysqli->prepare("SELECT driver_id, full_name, phone FROM drivers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$driver) {
    die("❌ Driver not found!");
}

$driver_id = $driver['driver_id'];

$stmt2 = $mysqli->prepare("
    SELECT s.student_id, s.full_name, s.email, s.phone, s.class, s.roll_number, b.bus_number
    FROM assignments a
    JOIN students s ON a.student_id = s.student_id
    JOIN buses b ON a.bus_id = b.bus_id
    WHERE a.driver_id = ?
    ORDER BY s.full_name ASC
");
$stmt2->bind_param("i", $driver_id);
$stmt2->execute();
$studentsResult = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assigned Students | Driver Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #19c0d661;
    margin: 0; 
    padding: 20px;
    color: #333;
}
.container {
    max-width: 1100px;
    margin: auto;
    background: #ffffffff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}
h2 {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0d6efd;
    margin-bottom: 20px;
}
.info {
    font-size: 15px;
    margin-bottom: 15px;
}
.info i { margin-right: 6px; color: #0d6efd; }
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
    border-radius: 10px;
    overflow: hidden;
}
th, td {
    padding: 12px;
    text-align: left;
}
th {
    background: #007bff;
    color: white;
}
tr:nth-child(even) { background: #f5f8ff; }
tr:hover { background: #e1f0ff; }
.back-btn {
    display: inline-block;
    margin-top: 20px;
    padding: 10px 18px;
    background: #0d6efd;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.3s;
}
.back-btn:hover { background: #0a58ca; }
</style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-users"></i> Students Assigned to You</h2>
    <p class="info"><i class="fa-solid fa-user-tie"></i> Driver: <?= htmlspecialchars($driver['full_name']) ?></p>
    <p class="info"><i class="fa-solid fa-phone"></i> Phone: <?= htmlspecialchars($driver['phone']) ?></p>

    <?php if ($studentsResult->num_rows > 0): ?>
        <table>
            <tr>
                <th><i class="fa-solid fa-id-badge"></i> Student ID</th>
                <th><i class="fa-solid fa-user"></i> Name</th>
                <th><i class="fa-solid fa-envelope"></i> Email</th>
                <th><i class="fa-solid fa-phone"></i> Phone</th>
                <th><i class="fa-solid fa-graduation-cap"></i> Class</th>
                <th><i class="fa-solid fa-hashtag"></i> Roll No.</th>
                <th><i class="fa-solid fa-bus"></i> Bus</th>
            </tr>
            <?php while ($row = $studentsResult->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['class']) ?></td>
                    <td><?= htmlspecialchars($row['roll_number']) ?></td>
                    <td><?= htmlspecialchars($row['bus_number']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>⚠️ No students are currently assigned to you.</p>
    <?php endif; ?>

    <a href="driverdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
