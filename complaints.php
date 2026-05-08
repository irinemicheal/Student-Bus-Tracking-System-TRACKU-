<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}

$parent_user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT parent_id, full_name FROM parents WHERE user_id=?");
$stmt->bind_param("i", $parent_user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$parent) {
    die("Parent not found.");
}

$driver_id = null;
$driver_stmt = $mysqli->prepare("
    SELECT a.driver_id 
    FROM assignments a
    JOIN students s ON a.student_id = s.student_id
    WHERE s.parent_id = ?
    LIMIT 1
");
$driver_stmt->bind_param("i", $parent['parent_id']);
$driver_stmt->execute();
$driver_row = $driver_stmt->get_result()->fetch_assoc();
$driver_stmt->close();

if ($driver_row) {
    $driver_id = $driver_row['driver_id'];
}

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_text = trim($_POST['complaint'] ?? '');

    if ($complaint_text && $driver_id) {
        $stmt = $mysqli->prepare("INSERT INTO complaints (parent_id, driver_id, complaint, status, timestamp) VALUES (?, ?, ?, 'Pending', NOW())");
        $stmt->bind_param("iis", $parent['parent_id'], $driver_id, $complaint_text);
        if ($stmt->execute()) {
            $success = "✅ Complaint submitted successfully!";
        } else {
            $error = "⚠️ Error submitting complaint: " . $mysqli->error;
        }
        $stmt->close();
    } else {
        $error = "⚠️ No driver found or complaint text missing.";
    }
}


$complaints_stmt = $mysqli->prepare("
    SELECT c.complaint_id, c.complaint, c.status, c.timestamp, u.username AS driver_name
    FROM complaints c
    LEFT JOIN drivers d ON c.driver_id = d.driver_id
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE c.parent_id = ?
    ORDER BY c.timestamp DESC
");
$complaints_stmt->bind_param("i", $parent['parent_id']);
$complaints_stmt->execute();
$complaints = $complaints_stmt->get_result();
$complaints_stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Complaint & History</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background: #19c0d661; margin:0; padding:0; }
.container { max-width: 900px; margin: 30px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
h2 { text-align:center; color:#0d6efd; margin-bottom:20px; }
form { display:flex; flex-direction: column; gap:15px; margin-bottom: 40px; }
textarea, button { padding: 10px; font-size:14px; border-radius:6px; border:1px solid #ccc; width:100%; }
button { background:#0d6efd; color:white; border:none; cursor:pointer; transition:0.3s; }
button:hover { background:#0a58ca; }
.alert { padding:12px; border-radius:6px; font-size:14px; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
.back-btn { display:inline-block; margin-top:20px; text-decoration:none; background: #1a73e8;color:white; padding:10px 18px; border-radius:6px; }
.back-btn:hover { background:#0a58ca; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { padding: 12px; border: 1px solid #ddd; text-align:center; }
th { background:#f1f1f1; color:#0d6efd; }
td { font-size: 14px; }
</style>
</head>
<body>
<div class="container">
    <h2><i class="fa-solid fa-comment-dots"></i> Submit Complaint</h2>

    <?php if($success) echo "<div class='alert success'>$success</div>"; ?>
    <?php if($error) echo "<div class='alert error'>$error</div>"; ?>

    <form method="POST">
        <label for="complaint">Complaint</label>
        <textarea name="complaint" id="complaint" rows="5" placeholder="Enter your complaint here..." required></textarea>
        <button type="submit"><i class="fa-solid fa-paper-plane"></i> Submit Complaint</button>
    </form>

    <h2><i class="fa-solid fa-list"></i> Complaint History</h2>
    <table>
        <thead>
            <tr>
                <th>Complaint</th>
                <th>Driver</th>
                <th>Status</th>
                <th>Submitted At</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($complaints->num_rows > 0): ?>
                <?php while($row = $complaints->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['complaint']); ?></td>
                    <td><?php echo htmlspecialchars($row['driver_name'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                    <td><?php echo $row['timestamp']; ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="4">No complaints submitted yet.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <a href="parentdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
