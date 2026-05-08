<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

$username = $_SESSION['username'];
$query = "SELECT * FROM drivers WHERE full_name='$username'";
$result = mysqli_query($mysqli, $query);
$driver = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driver Dashboard | Student Bus Tracking</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    display: flex;
    flex-direction: column;
    min-height: 100vh;
    background: url('driverpic.jpg') no-repeat center center fixed;
    background-size: cover;       
    background-attachment: fixed; 
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
.header h2 { font-size: 24px; margin: 0; display: flex; align-items: center; gap: 10px; }
.header a {
    background: #ff4757;
    padding: 10px 18px;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-size: 15px;
    transition: 0.3s;
}
.header a:hover { background: #e84118; }


.welcome {
    text-align: center;
    padding: 25px 15px;
}
.welcome h1 {
    font-size: 26px;
    color: #004085;
    margin-bottom: 8px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
}
.welcome p {
    font-size: 15px;
    color: #666;
}


.dashboard {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    padding: 20px 30px;
    flex: 1;
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
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.2);
}
.card i {
    font-size: 40px;
    color: #0d6efd;
    margin-bottom: 15px;
}
.card h3 { margin-bottom: 10px; font-size: 18px; }
.card p { font-size: 14px; color: #555; }


footer {
    text-align: center;
    padding: 14px;
    background: #1a73e8;
    color: white;
    font-size: 14px;
    margin-top: auto;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
}


@media (max-width: 768px) {
    .dashboard { gap: 15px; padding: 15px; }
    .card i { font-size: 35px; }
}
</style>
</head>
<body>


<div class="header" style="display: flex; justify-content: space-between; align-items: center;">
    <h2><i class="fa-solid fa-id-card-clip"></i> Driver Dashboard</h2>
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="about.php?role=driver" 
           style="background:#34ace0; padding:10px 18px; border-radius:6px; color:white; text-decoration:none; font-weight:bold; display:flex; align-items:center; gap:5px;">
            <i class="fa-solid fa-circle-info"></i> About
        </a>
        <a href="logout.php" 
           style="background:#ff4757; padding:10px 18px; border-radius:6px; color:white; text-decoration:none; font-weight:bold; display:flex; align-items:center; gap:5px;">
            <i class="fa-solid fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div>



<div class="welcome">
    <h1><i class="fa-solid fa-user-tie"></i> Welcome, <?php echo $_SESSION['username']; ?></h1>
    <p>Manage your assigned routes, students, notifications, and bus updates here.</p>
</div>

<div class="dashboard">
    <a href="assigned_route.php" class="card">
        <i class="fa-solid fa-road"></i>
        <h3>Assigned Route</h3>
        <p>View details of your assigned bus route!</p>
    </a>

    <a href="update_status.php" class="card">
        <i class="fa-solid fa-bus"></i>
        <h3>Update Status</h3>
        <p>Update your current bus status here!</p>
    </a>

    <div class="card" onclick="location.href='manage_trips.php';">
        <i class="fa-solid fa-map-location-dot"></i>
        <h3>Past Trip Details</h3>
        <p>Manage your past trips here!</p>
    </div>

    <a href="student_list.php" class="card">
        <i class="fa-solid fa-users"></i>
        <h3>Student List</h3>
        <p>Check all students assigned to your bus!</p>
    </a>

    <a href="notificationss.php" class="card">
        <i class="fa-solid fa-bell"></i>
        <h3>Notifications</h3>
        <p>View recent alerts and notifications!</p>
    </a>

  
    <a href="complaints_driver.php" class="card">
        <i class="fa-solid fa-message"></i>
        <h3>Complaints</h3>
        <p>View and resolve complaints sent by parents.</p>
    </a>
</div>

<footer>
    <i class="fa-solid fa-bus"></i> &copy; <?php echo date("Y"); ?> Student Bus Tracking System | All Rights Reserved
</footer>

</body>
</html>
