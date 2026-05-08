<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
$student_user_id = $_SESSION['user_id'];

$drivers = [];
$driver_stmt = $mysqli->prepare("
    SELECT d.driver_id, u.username AS driver_name
    FROM assignments a
    LEFT JOIN drivers d ON a.driver_id = d.driver_id
    LEFT JOIN users u ON d.user_id = u.user_id
    WHERE a.student_id = (SELECT student_id FROM students WHERE user_id = ?)
");
$driver_stmt->bind_param("i", $student_user_id);
$driver_stmt->execute();
$result = $driver_stmt->get_result();
while($row = $result->fetch_assoc()){
    $drivers[] = $row;
}
$driver_stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feedback</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #19c0d661;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: #fff;
            padding: 40px;
            border-radius: 15px;
            width: 450px;
            text-align: center;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            animation: fadeIn 0.7s ease-in-out;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
        h2 { color: #0d6efd; margin-bottom: 15px; }
        select, textarea {
            width: 90%;
            margin-top: 15px;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #ccc;
            font-size: 15px;
            transition: 0.3s;
        }
        select:focus, textarea:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 8px rgba(13, 110, 253, 0.3);
        }
        button {
            margin-top: 15px;
            padding: 12px 25px;
            background: #0d6efd;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover { background: #0a58ca; }
        a.back {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            padding: 10px 20px;
            background: #0575d7ff;
            color: white;
            border-radius: 8px;
            transition: background 0.3s;
        }
        a.back:hover { background: #5a6268; }
    </style>
</head>
<body>
<div class="container">
    <h2>💬 Share Your Feedback</h2>
    <p style="color: #555;">Your opinion matters. Help us improve!</p>
    <form method="POST" action="submit_feedback.php">
        <label for="driver">Select Driver</label><br>
        <select name="driver_id" id="driver" required>
            <option value="">-- Choose Driver --</option>
            <?php foreach($drivers as $driver): ?>
                <option value="<?php echo $driver['driver_id']; ?>">
                    <?php echo htmlspecialchars($driver['driver_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <textarea name="feedback" placeholder="Enter your feedback..." required></textarea><br>
        <button type="submit">🚀 Submit Feedback</button>
    </form>
    <a class="back" href="studentdash.php">⬅ Back to Dashboard</a>
</div>
</body>
</html>
