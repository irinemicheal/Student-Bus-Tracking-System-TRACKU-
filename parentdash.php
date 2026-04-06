<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}
include 'db.php';

$username = $_SESSION['username'];
$query = "SELECT * FROM parents WHERE full_name='$username'";
$result = mysqli_query($mysqli, $query);
$parent = mysqli_fetch_assoc($result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parent Dashboard | Student Bus Tracking</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
* {
    box-sizing: border-box;
}
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;

   
    background: url('parentpic.jpg') no-repeat center 45% fixed;
    background-size: cover;   
    background-attachment: fixed; 

    min-height: 100vh;

    display: flex;
    flex-direction: column;
}


.header {
    background: #1a73e8;
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
}
.header h2 { 
    font-size: 24px; 
    margin: 0; 
}
.header p {
    margin: 5px 0 0;
    font-size: 14px;
}
.logout-btn {
    background: #ff4757;
    padding: 10px 18px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 15px;
    transition: 0.3s;
}
.logout-btn:hover { background: #e84118; }


.cards {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    padding: 30px 20px;
    max-width: 1200px;
    margin: 30px auto;
}
.card {
    flex: 0 0 250px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    padding: 30px 20px;
    text-align: center;
    cursor: pointer;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 8px 20px rgba(0,0,0,0.2); 
}
.card i {
    font-size: 40px;
    color: #1a73e8;
    margin-bottom: 15px;
}
.card h3 { 
    margin-bottom: 10px; 
    color: #1a73e8; 
}
.card p { 
    font-size: 15px; 
    font-weight: 500; 
    color: #333; 
}

footer {
    text-align: center;
    padding: 14px;
    background: #1a73e8;
    color: white;
    font-size: 14px;
    margin-top: auto;
}
</style>
</head>
<body>

<div class="header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2>Welcome, <?php echo $_SESSION['username']; ?> 👋</h2>
        <p>Track your child's bus and stay informed</p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="about.php?role=student" class="logout-btn" style="background:#34ace0;">
            <i class="fa-solid fa-circle-info"></i> About
        </a>
        <a href="logout.php" class="logout-btn">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>

<div class="cards">
    <div class="card" onclick="location.href='plive_tracking.php';">
        <i class="fa-solid fa-bus"></i>
        <h3>Live Bus Tracking</h3>
        <p>See your child’s bus location in real time!</p>
    </div>

    <div class="card" onclick="location.href='trip_history.php';">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <h3>Trip History</h3>
        <p>View your child's past trips deails!</p>
    </div>

    <div class="card" onclick="location.href='notifications_parent.php';">
        <i class="fa-solid fa-bell"></i>
        <h3>Notifications</h3>
        <p>View important updates and alerts here!</p>
    </div>

    <div class="card" onclick="location.href='complaints.php';">
        <i class="fa-solid fa-comments"></i>
        <h3>Complaints</h3>
        <p>Submit a concern</p>
    </div>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Student Bus Tracking System | All Rights Reserved
</footer>

</body>
</html>
