<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Manage Users | Student Bus Tracking</title>
<link rel="stylesheet" href="assets/css/style_subpages.css">
<style>
    body { font-family: 'Segoe UI', sans-serif;background: #19c0d661;margin:0; padding:0; }
    .container { width:90%; max-width:1100px; margin:40px auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 8px 20px rgba(0,0,0,0.15); }
    h2{ text-align:center; color:#333; margin-bottom:20px; }
    .back-btn{ display:inline-block; margin-bottom:20px; padding:8px 14px; background:#1e90ff; color:#fff; text-decoration:none; border-radius:8px; }
    .tabs{ display:flex; justify-content:center; gap:15px; margin-bottom:25px; }
    .tabs .btn{ padding:12px 18px; background:#4cafef; color:#fff; border-radius:8px; text-decoration:none; font-weight:bold; }
    table{ width:100%; border-collapse:collapse; border-radius:10px; overflow:hidden; }
    th, td{ padding:14px; text-align:center; border-bottom:1px solid #eee; }
    th{ background:#4cafef; color:#fff; font-size:16px; }
    tr:nth-child(even){ background:#f9fcff; }
    tr:hover{ background:#e9f3ff; }
    .role-student{ color:#1e90ff; font-weight:bold; }
    .role-parent{ color:#28a745; font-weight:bold; }
    .role-driver{ color:#ff9800; font-weight:bold; }
    .action-btn { padding:6px 10px; border-radius:5px; text-decoration:none; font-size:13px; }
    .edit-btn{ background:#008b8b; color:#000; }
    .delete-btn{ background:#DC3545; color:#fff; }
</style>
</head>
<body>
<div class="container">
    <h2>👥 Manage Students, Parents & Drivers</h2>
    <a href="admin.php" class="back-btn">⬅ Back to Dashboard</a>

    <div class="tabs">
        <a href="add_student.php" class="btn">➕ Add Student</a>
        <a href="add_parent.php" class="btn">➕ Add Parent</a>
        <a href="add_driver.php" class="btn">➕ Add Driver</a>
    </div>

    <h3>📋 All Registered Users</h3>

    <table>
        <thead>
            <tr>
                <th>🆔 User ID</th>
                <th>👤 Username</th>
                <th>🎭 Role</th>
                <th>⚙ Actions</th>
            </tr>
        </thead>
        <tbody>
<?php
$query = "SELECT user_id, username, role FROM users ORDER BY role, username";
$result = mysqli_query($mysqli, $query);

if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $uid = intval($row['user_id']);
        $username = htmlspecialchars($row['username'] ?? '');
        $role = htmlspecialchars($row['role'] ?? '');
        $roleClass = 'role-' . strtolower($row['role'] ?? '');

        echo '<tr>';
        echo '<td>'.$uid.'</td>';
        echo '<td>'.$username.'</td>';
        echo '<td class="'.$roleClass.'">'.$role.'</td>';
        echo '<td>
                <a href="edit_user.php?id='.$uid.'" class="action-btn edit-btn">✏ Edit</a>
                <a href="delete_user.php?id='.$uid.'" class="action-btn delete-btn" onclick="return confirm(\'Are you sure?\');">🗑 Delete</a>
              </td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="4" class="empty-msg">🚫 No users registered yet</td></tr>';
}
?>
        </tbody>
    </table>
</div>
</body>
</html>
