<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT driver_id FROM drivers WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();
$driver_id = $driver['driver_id'] ?? null;
$stmt->close();

if (!$driver_id) {
    die("Driver not found.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'], $_POST['status'])) {
    $complaint_id = (int)$_POST['complaint_id'];
    $status = $_POST['status'] === 'Resolved' ? 'Resolved' : 'Pending';

    $stmt = $mysqli->prepare("UPDATE complaints SET status=? WHERE complaint_id=?");
    $stmt->bind_param("si", $status, $complaint_id);
    $stmt->execute();
    $stmt->close();
}
$complaints = [];
$sql = "
    SELECT c.complaint_id, c.complaint, c.status, c.created_at, s.full_name AS student_name, p.full_name AS parent_name, b.bus_number
    FROM complaints c
    JOIN students s ON c.user_id = s.parent_id
    JOIN parents p ON s.parent_id = p.parent_id
    JOIN buses b ON s.bus_id = b.bus_id
    WHERE b.driver_id = ?
    ORDER BY c.created_at DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $complaints[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver - Complaints</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background: #f1f5ff; margin: 0; padding: 0; }
.container { max-width: 900px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.1); }
h2 { text-align: center; color: #0d6efd; margin-bottom: 20px; }
table { width: 100%; border-collapse: collapse; margin-top: 25px; }
table th, table td { padding: 12px; border: 1px solid #ddd; text-align: center; }
table th { background: #0d6efd; color: white; }
table tr:hover { background: #f1f9ff; }
select, button { padding: 6px 10px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px; }
button { background: #0d6efd; color: white; cursor: pointer; transition: 0.3s; border: none; }
button:hover { background: #0a58ca; }
.back-btn { display: inline-block; margin-top: 20px; text-decoration: none; background: #6c757d; color: white; padding: 10px 18px; border-radius: 8px; }
.back-btn:hover { background: #5a6268; }
form.inline { display: flex; justify-content: center; gap: 10px; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fa-solid fa-exclamation-circle"></i> Complaints from Parents/Students</h2>

<?php if (!empty($complaints)): ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Parent Name</th>
            <th>Bus Number</th>
            <th>Complaint</th>
            <th>Status</th>
            <th>Submitted On</th>
            <th>Update Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($complaints as $index => $c): ?>
        <tr>
            <td><?= $index + 1 ?></td>
            <td><?= htmlspecialchars($c['student_name']) ?></td>
            <td><?= htmlspecialchars($c['parent_name']) ?></td>
            <td><?= htmlspecialchars($c['bus_number']) ?></td>
            <td><?= htmlspecialchars($c['complaint']) ?></td>
            <td><?= htmlspecialchars($c['status']) ?></td>
            <td><?= $c['created_at'] ?></td>
            <td>
                <form method="POST" class="inline">
                    <input type="hidden" name="complaint_id" value="<?= $c['complaint_id'] ?>">
                    <select name="status">
                        <option value="Pending" <?= $c['status']=='Pending'?'selected':'' ?>>Pending</option>
                        <option value="Resolved" <?= $c['status']=='Resolved'?'selected':'' ?>>Resolved</option>
                    </select>
                    <button type="submit"><i class="fa-solid fa-check"></i> Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="text-align:center; margin-top:20px; color:#777;">No complaints submitted yet.</p>
<?php endif; ?>

<a href="driverdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
