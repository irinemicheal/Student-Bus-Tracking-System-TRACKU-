<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'student') {
    header("Location: login.php");
    exit();
}

include 'db.php';
$username = $_SESSION['username'];
$query = "SELECT * FROM students WHERE full_name='$username'";
$result = mysqli_query($mysqli, $query);
$student = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard | Bus Tracking</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
* {box-sizing: border-box;}
body {
    margin: 0;
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: url('studentpic.jpg') no-repeat center 5% fixed;
    background-size: cover;       
    background-attachment: fixed; 
}



nav {
    background-color: #1a73e8;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    position: sticky;
    top: 0;
    z-index: 1000;
}
nav .logo {
    color: white;
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 10px;
}
nav .logout-btn {
    background: #ff4757;
    padding: 10px 18px;
    border-radius: 6px;
    color: white;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}
nav .logout-btn:hover { background: #e84118; }


.container {
    padding: 40px 20px;
    text-align: center;
    flex: 1;
}
.container h2 { color: #1a73e8; font-size: 26px; margin-bottom: 10px; }
.container p { color: #555; font-size: 16px; margin-bottom: 30px; }


.dashboard {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 20px;
}
.card {
    background: white;
    flex: 0 0 220px;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    color: #1a73e8;
    text-decoration: none;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s, box-shadow 0.3s;
}
.card i {
    font-size: 40px;
    margin-bottom: 15px;
}
.card h3 { margin-bottom: 10px; }
.card p { font-size: 14px; color: #555; }
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 25px rgba(0,0,0,0.15);
}

footer {
    text-align: center;
    padding: 15px;
    background: #1a73e8;
    color: white;
    font-size: 14px;
    margin-top: auto;
}
</style>
</head>
<body>

<nav>
    <div class="logo"><i class="fa-solid fa-user-graduate"></i> Student Panel</div>
    
    <div>
        <a href="about.php?role=student" class="logout-btn" style="background:#34ace0; margin-right:10px;">
            <i class="fa-solid fa-circle-info"></i> About
        </a>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-sign-out-alt"></i> Logout</a>
    </div>
</nav>

<div class="container">
    <h2>Welcome, <?php echo $_SESSION['username']; ?> 👋</h2>
    <p>Track your bus, view your route, and stay updated in real-time!</p>

    <div class="dashboard">
        <a href="slive_tracking.php" class="card">
            <i class="fa-solid fa-map-location-dot"></i>
            <h3>Live Tracking</h3>
            <p>Track your bus's live location !!</p>
        </a>

        <a href="my_route.php" class="card">
            <i class="fa-solid fa-route"></i>
            <h3>My Route</h3>
            <p>View your assigned bus route details!</p>
        </a>


        <a href="notifications_student.php" class="card">
            <i class="fa-solid fa-bell"></i>
            <h3>Notifications</h3>
            <p>Get important bus alerts and updates in here!</p>
        </a>

        <a href="submit_feedback.php" class="card">
            <i class="fa-solid fa-comments"></i>
            <h3>Feedback</h3>
            <p>Send feedback or report an issue !</p>
        </a>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Student Bus Tracking System | All Rights Reserved
</footer>

</body>
</html>
