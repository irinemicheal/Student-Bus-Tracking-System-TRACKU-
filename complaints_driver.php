<?php
session_start();
include 'db.php';

// ✅ Check driver login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

$driver_user_id = $_SESSION['user_id'];

// ✅ Get driver_id
$stmt = $mysqli->prepare("SELECT driver_id FROM drivers WHERE user_id=?");
$stmt->bind_param("i", $driver_user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();
$stmt->close();

$driver_id = $driver['driver_id'] ?? null;
if (!$driver_id) die("Driver not found.");

// ✅ Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $complaint_id = intval($_POST['complaint_id']);
    $status = $_POST['status'];

    $update = $mysqli->prepare("UPDATE complaints SET status=? WHERE complaint_id=? AND driver_id=?");
    $update->bind_param("sii", $status, $complaint_id, $driver_id);
    $update->execute();
    $update->close();
}

$stmt = $mysqli->prepare(
    "SELECT c.complaint_id, c.complaint, c.status, c.timestamp, p.full_name AS parent_name,
           s.full_name AS student_name, s.class AS student_class, s.roll_number AS student_roll
    FROM complaints c
    JOIN parents p ON c.parent_id = p.parent_id
    LEFT JOIN students s ON p.student_id = s.student_id
    WHERE c.driver_id = ?
    ORDER BY c.timestamp DESC"
);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$complaints = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Complaints</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #19c0d661;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 1000px;
    margin: auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}
h2 {
    color: #0d6efd;
    text-align: center;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}
th {
    background: #0d6efd;
    color: white;
}
tr:nth-child(even) {
    background: #f9fbff;
}
tr:hover {
    background: #e8f1ff;
}
form {
    margin: 0;
}
select {
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
}
button {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
    margin-left: 5px;
    transition: 0.3s;
}
button:hover {
    background: #218838;
}
.back-btn {
    display: inline-block;
    margin-top: 20px;
    text-decoration: none;
    background: #1a73e8;
    color: white;
    padding: 10px 18px;
    border-radius: 6px;
    transition: 0.3s;
}
.back-btn:hover {
    background: #0a58ca;
}
.status-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: bold;
    color: black;
    display: inline-block;
}
.status-Pending { background: #ffc107; }
.status-In\ Progress { background: #0bb8e3ff; }
.status-Resolved { background: #28a745; }
</style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-comment-dots"></i> Complaints from Parents</h2>
    <table>
        <thead>
            <tr>
                <th>Parent</th>
                <th>Complaint</th>
                <th>Status</th>
                <th>Update</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $complaints->fetch_assoc()): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($row['parent_name']) ?>
                    <?php if (!empty($row['student_name'])): ?>
                        <div style="font-size:12px;color:#555;margin-top:6px;">Student: <?= htmlspecialchars($row['student_name']) ?> | Class: <?= htmlspecialchars($row['student_class'] ?: '-') ?> | Roll: <?= htmlspecialchars($row['student_roll'] ?: '-') ?></div>
                    <?php else: ?>
                        <div style="font-size:12px;color:#777;margin-top:6px;">Student: -</div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['complaint']) ?></td>
                <td><span class="status-badge status-<?= str_replace(' ','\\ ',$row['status']) ?>"><?= $row['status'] ?></span></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="complaint_id" value="<?= $row['complaint_id'] ?>">
                        <select name="status">
                            <option value="Pending" <?= $row['status']=="Pending"?"selected":"" ?>>Pending</option>
                            <option value="In Progress" <?= $row['status']=="In Progress"?"selected":"" ?>>In Progress</option>
                            <option value="Resolved" <?= $row['status']=="Resolved"?"selected":"" ?>>Resolved</option>
                        </select>
                        <button type="submit"><i class="fa-solid fa-check"></i></button>
                    </form>
                </td>
                <td><?= $row['timestamp'] ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="driverdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
