<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$user_id = $_SESSION['user_id'];
$student_id = null;
$student_name = null;
$bus_id = null;
$pickup_stop_id = null;
$stmt = $mysqli->prepare("
    SELECT s.student_id, s.full_name, s.bus_id, s.pickup_stop_id
    FROM students s
    WHERE s.user_id = ? LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($student_id, $student_name, $bus_id, $pickup_stop_id);
$stmt->fetch();
$stmt->close();

$drivers = [];
if ($student_id) {
    $stmt = $mysqli->prepare("
        SELECT DISTINCT d.driver_id, d.full_name AS driver_name
        FROM assignments a
        INNER JOIN drivers d ON a.driver_id = d.driver_id
        WHERE a.student_id = ?
    ");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $drivers[] = $row;
    }
    $stmt->close();
}

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $driver_id = isset($_POST['driver_id']) ? intval($_POST['driver_id']) : 0;
    $feedback_text = trim($_POST['feedback'] ?? '');
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : null;

    if ($driver_id <= 0) {
        $message = "⚠️ Please select a driver.";
    } elseif ($feedback_text === '') {
        $message = "⚠️ Please write your feedback.";
    } else {
        $sql = "
            INSERT INTO feedback (student_id, driver_id, full_name, feedback, rating, created_at, status)
            VALUES (?, ?, ?, ?, ?, NOW(), 'Unseen')
        ";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iissi", $student_id, $driver_id, $student_name, $feedback_text, $rating);

        if ($stmt->execute()) {
            $message = "✅ Feedback submitted successfully!";
        } else {
            $message = "❌ Error submitting feedback!";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Feedback</title>
    <style>
        body { font-family: 'Poppins', sans-serif; background:#19c0d661; padding:0; margin:0; }
        .container { width: 500px; background:white; margin:60px auto; padding:30px; border-radius:12px; box-shadow:0 6px 15px rgba(0,0,0,0.1); }
        h2 { text-align:center; color:#0d6efd; margin-bottom:20px; }
        label { font-weight:bold; color:#0a58ca; }
        select, textarea { width:100%; padding:12px; border:1px solid #ccc; border-radius:8px; margin:10px 0; }
        textarea { height:100px; resize:none; }
        button { width:100%; background:#0d6efd; color:white; font-size:16px; font-weight:bold; padding:12px; border:none; border-radius:8px; cursor:pointer; transition:0.3s; }
        button:hover { background:#0a58ca; }
        .message { color: green; font-weight:bold; text-align:center; margin-bottom:15px; }
        .back-btn { margin-top:20px; display:block; text-align:center; text-decoration:none; color:white; font-weight:bold; background:#198754; padding:10px; border-radius:8px; }
        .back-btn:hover { background:#146c43; }
    </style>
</head>
<body>

<div class="container">
    <h2>📝 Submit Feedback</h2>

    <?php if(!empty($message)): ?>
        <p class="message"><?= htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">
        <label for="driver_id">Select Driver</label>
        <select name="driver_id" required>
            <option value="">Select Driver</option>
            <?php foreach ($drivers as $driver): ?>
                <option value="<?= $driver['driver_id'] ?>">
                    <?= htmlspecialchars($driver['driver_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="rating">Rating ⭐</label>
        <select name="rating" required>
            <option value="">Select Rating</option>
            <option value="1">⭐</option>
            <option value="2">⭐⭐</option>
            <option value="3">⭐⭐⭐</option>
            <option value="4">⭐⭐⭐⭐</option>
            <option value="5">⭐⭐⭐⭐⭐</option>
        </select>

        <label for="feedback">Feedback</label>
        <textarea name="feedback" placeholder="Write your feedback..." required></textarea>

        <button type="submit">Submit ✅</button>
    </form>

    <a href="studentdash.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

</body>
</html>
