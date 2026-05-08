<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';


$bus_count = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as total FROM buses"))['total'];
$route_count = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as total FROM routes"))['total'];
$student_count = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as total FROM students"))['total'];
$driver_count = mysqli_fetch_assoc(mysqli_query($mysqli, "SELECT COUNT(*) as total FROM drivers"))['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard | Student Bus Tracking</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
* { box-sizing: border-box; }
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;

    background: url('adminpic.jpg') no-repeat center 64% fixed;
    background-size: cover;
    background-attachment: fixed;
}



.navbar { display: flex; justify-content: space-between; align-items: center; background: #1a73e8; padding: 20px 30px; color: white; position: sticky; top: 0; z-index: 10; }
.navbar h2 { font-size: 24px; font-weight: bold; }
.navbar a { color: white; text-decoration: none; margin-left: 20px; transition: 0.3s; }
.navbar a:hover { color: #ffd700; }


.tabs { display: flex; justify-content: center; flex-wrap: wrap; background: #0d6efd; padding: 12px 0; gap: 20px; }
.tabs a { color: white; padding: 12px 20px; text-decoration: none; display: flex; align-items: center; border-radius: 6px; transition: all 0.3s ease-in-out; font-weight: 500; }
.tabs a i { margin-right: 8px; font-size: 18px; }
.tabs a:hover { background-color: #0a58ca; transform: scale(1.05); }


.welcome { text-align: center; padding: 30px 20px; }
.welcome h1 { font-size: 30px; color: #004085; margin-bottom: 10px; }
.welcome p { font-size: 16px; color: #555; }


.cards { display: flex; flex-wrap: wrap; justify-content: center; gap: 25px; padding: 20px; }
.card { background: white; flex: 0 0 230px; padding: 25px; border-radius: 12px; display: flex; align-items: center; gap: 15px; text-decoration: none; color: #1a73e8; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: transform 0.3s, box-shadow 0.3s; }
.card i { font-size: 40px; }
.card div { text-align: left; }
.card h3 { margin: 0; font-size: 18px; font-weight: bold; }
.card p { margin: 0; font-size: 16px; color: #555; }
.card:hover { transform: translateY(-5px); box-shadow: 0 12px 25px rgba(0,0,0,0.15); }


footer { text-align: center; padding: 15px; background: #1a73e8; color: white; font-size: 14px; margin-top: auto; }

@media (max-width: 768px) {
    .card { flex-direction: column; text-align: center; }
    .card div { text-align: center; }
    .card i { margin-bottom: 10px; }
}
</style>
</head>
<body>

<div class="navbar">
    <h2>BusTrack Admin Dashboard</h2>
    <div>
        Welcome, <?php echo $_SESSION['username']; ?> 👋
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="tabs">
    <a href="manage_buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
    <a href="manage_routes.php"><i class="fas fa-map-signs"></i> Manage Routes</a>
     <a href="assign_route.php"><i class="fas fa-link"></i> Assign Buses</a>
    <a href="assign_buses.php"><i class="fas fa-user-friends"></i> Assign Students</a>
    <a href="manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
    <a href="feedback_admin.php"><i class="fas fa-comment-dots"></i> Student Feedback</a>
</div>


<div class="welcome">
    <h1>Admin Control Panel</h1>
    <p>Manage buses, routes, drivers, students, parents, live tracking, and student feedback in one place.</p>
</div>


<div class="cards">
    <a href="manage_buses.php" class="card">
        <i class="fas fa-bus"></i>
        <div>
            <h3>Total Buses</h3>
            <p><?= $bus_count ?></p>
        </div>
    </a>
    <a href="manage_routes.php" class="card">
        <i class="fas fa-map-signs"></i>
        <div>
            <h3>Total Routes</h3>
            <p><?= $route_count ?></p>
        </div>
    </a>
    <a href="manage_users.php?role=student" class="card">
        <i class="fas fa-user-graduate"></i>
        <div>
            <h3>Registered Students</h3>
            <p><?= $student_count ?></p>
        </div>
    </a>
    <a href="manage_users.php?role=driver" class="card">
        <i class="fas fa-chalkboard-teacher"></i>
        <div>
            <h3>Drivers</h3>
            <p><?= $driver_count ?></p>
        </div>
    </a>
</div>


<footer>
    &copy; <?php echo date("Y"); ?> Student Bus Tracking System | All Rights Reserved
</footer>

</body>
</html>
