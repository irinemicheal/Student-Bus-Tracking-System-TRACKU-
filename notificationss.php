<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

// Get logged-in driver info
$user_id = $_SESSION['user_id'];
$stmt = $mysqli->prepare("SELECT * FROM drivers WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$driver = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$driver) die("❌ Driver not found for this user_id.");
$driver_id = $driver['driver_id'];

// Fetch routes assigned to this driver
$routes_result = $mysqli->query("
    SELECT DISTINCT r.route_id, r.route_name 
    FROM routes r
    JOIN assignments a ON r.route_id = a.route_id
    WHERE a.driver_id = $driver_id
    ORDER BY r.route_name ASC
");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $route_id = $_POST['route_id'];
    $message = trim($_POST['message']);

    if (!empty($role) && !empty($route_id) && !empty($message)) {

        // ✅ Insert one notification per role per route
        // Note: `bus_id` column does not exist in the notifications table in this schema,
        // so do not include it in the INSERT. If you later add bus-level notifications,
        // add the column first and adjust the INSERT/bind accordingly.
        $stmt = $mysqli->prepare("INSERT INTO notifications (driver_id, role, message, route_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $driver_id, $role, $message, $route_id);
        $stmt->execute();
        $stmt->close();

        $success = "✅ Notification sent successfully!";
    } else {
        $error = "⚠️ Please select a role, route, and enter a message.";
    }
}

// Fetch recent notifications (most recent 10)
$notifQuery = "
    SELECT n.*, r.route_name 
    FROM notifications n 
    LEFT JOIN routes r ON n.route_id=r.route_id
    WHERE n.driver_id=? 
    ORDER BY n.created_at DESC 
    LIMIT 10
";
$stmt = $mysqli->prepare($notifQuery);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$notifs = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Notifications</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; background:#19c0d661; margin:0; padding:20px; }
.container { max-width:700px; margin: 40px auto; background: #f5f8f97e; padding: 25px; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,0.1); }
h2 { color:#0d6efd; margin-bottom: 20px; text-align:center; }
.form-group { margin-bottom: 15px; }
label { font-weight:600; display:block; margin-bottom:6px; }
select, textarea, button { width:100%; padding:10px; border-radius:8px; border:1px solid #ccc; font-size:14px; }
button { background:#0d6efd; color:white; border:none; margin-top:10px; cursor:pointer; transition:0.3s; }
button:hover { background:#0a58ca; }
.alert { margin:10px 0; padding:12px; border-radius:6px; font-size:14px; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
.notifications { margin-top:30px; }
.notif-item { background:#f4f8ff; border-left:5px solid #0d6efd; padding:12px; margin-bottom:12px; border-radius:6px; transition:0.2s; }
.notif-item:hover { background:#e8f1ff; }
.notif-item small { display:block; color:#777; margin-top:5px; }
.back-btn { display:inline-block; margin-top:20px; text-decoration:none; background:#1b73caff; color:white; padding:8px 15px; border-radius:6px; transition:0.3s; }
.back-btn:hover { background:#0e314bff; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fa-solid fa-bell"></i> Send Notification</h2>

<?php if (!empty($success)) echo "<div class='alert success'>$success</div>"; ?>
<?php if (!empty($error)) echo "<div class='alert error'>$error</div>"; ?>

<form method="POST">
    <div class="form-group">
        <label for="role">Send To:</label>
        <select name="role" id="role" required>
            <option value="">-- Select Role --</option>
            <option value="student">Students</option>
            <option value="parent">Parents</option>
        </select>
    </div>

    <div class="form-group">
        <label for="route_id">Select Route:</label>
        <select name="route_id" id="route_id" required>
            <option value="">-- Select Route --</option>
            <?php while ($row = $routes_result->fetch_assoc()): ?>
                <option value="<?= $row['route_id'] ?>"><?= htmlspecialchars($row['route_name']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label for="message">Message:</label>
        <textarea name="message" id="message" rows="4" placeholder="Enter your notification..." required></textarea>
    </div>

    <button type="submit"><i class="fa-solid fa-paper-plane"></i> Send Notification</button>
</form>

<div class="notifications">
    <h3>Recent Notifications</h3>
    <?php while ($row = $notifs->fetch_assoc()): ?>
        <div class="notif-item">
            <strong>To <?= ucfirst($row['role']); ?>:</strong> <?= htmlspecialchars($row['message']); ?>
            <small>Route: <?= htmlspecialchars($row['route_name']) ?> | <?= $row['created_at']; ?></small>
        </div>
    <?php endwhile; ?>
</div>

<a href="driverdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>
</body>
</html>
