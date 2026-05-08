<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
$username = $_SESSION['username'];
$query = "SELECT * FROM drivers WHERE full_name=?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();
$driver_id = $driver['driver_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $message = trim($_POST['message']);

    if (!empty($role) && !empty($message)) {
        $stmt = $mysqli->prepare("INSERT INTO notifications (driver_id, role, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $driver_id, $role, $message);
        $stmt->execute();
        $success = "✅ Notification sent successfully!";
    } else {
        $error = "⚠️ Please select a role and enter a message.";
    }
}
$notifQuery = "SELECT * FROM notifications WHERE driver_id=? ORDER BY created_at DESC LIMIT 10";
$stmt = $mysqli->prepare($notifQuery);
$stmt->bind_param("i", $driver_id);
$stmt->execute();
$notifs = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Notifications</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f1f5ff;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
        }
        h2 {
            color: #0d6efd;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
        }
        select, textarea, button {
            width: 100%;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            background: #0d6efd;
            color: white;
            border: none;
            margin-top: 10px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #0a58ca;
        }
        .alert {
            margin: 10px 0;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
        }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .notifications {
            margin-top: 30px;
        }
        .notif-item {
            background: #f4f8ff;
            border-left: 5px solid #0d6efd;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 6px;
        }
        .notif-item small {
            display: block;
            color: #777;
            margin-top: 5px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            background: #6c757d;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
        }
        .back-btn:hover {
            background: #5a6268;
        }
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
                <label for="message">Message:</label>
                <textarea name="message" id="message" rows="4" placeholder="Enter your notification here..." required></textarea>
            </div>

            <button type="submit"><i class="fa-solid fa-paper-plane"></i> Send Notification</button>
        </form>

        <div class="notifications">
            <h3>Recent Notifications</h3>
            <?php while ($row = $notifs->fetch_assoc()) { ?>
                <div class="notif-item">
                    <strong>To <?= ucfirst($row['role']); ?>:</strong> <?= htmlspecialchars($row['message']); ?>
                    <small><i class="fa-regular fa-clock"></i> <?= $row['created_at']; ?></small>
                </div>
            <?php } ?>
        </div>

        <a href="driverdash.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
    </div>
</body>
</html>
