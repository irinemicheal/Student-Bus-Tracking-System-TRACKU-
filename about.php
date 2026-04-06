<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['student', 'parent', 'driver'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];
switch ($role) {
    case 'student':
        $dashboard = 'studentdash.php';
        break;
    case 'parent':
        $dashboard = 'parentdash.php';
        break;
    case 'driver':
        $dashboard = 'driverdash.php';
        break;
    default:
        $dashboard = 'login.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About | Student Bus Tracking System</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: url('aboutpic.png') no-repeat center center fixed;
    background-size: cover;
    color: #fff;
    line-height: 1.7;
}

/* Header */
.header {
    background: rgba(26, 115, 232, 0.9);
    color: white;
    padding: 20px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}
.header h2 {
    font-size: 24px;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}
.header a.back-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 10px 18px;
    border-radius: 6px;
    font-weight: bold;
    color: white;
    text-decoration: none;
    background: #ff4757;
    transition: 0.3s;
}
.header a.back-btn:hover { opacity: 0.85; }
.about-content-wrapper {
    background: rgba(0, 0, 0, 0.6);
    padding: 40px 20px;
    max-width: 900px;
    margin: 40px auto;
    border-radius: 12px;
}

.about-content {
    text-align: justify;
}
.about-content h2 {
    color: #ffd700;
    text-align: center;
    margin-bottom: 25px;
}
.about-content p i {
    color: #ffd700;
    margin-right: 6px;
}
.about-content strong.keyword {
    color: #ffd700;
}
.founders {
    margin-top: 40px;
    font-weight: 500;
    color: #ffd700;
    font-size: 16px;
    text-align: left;
}
.founders div {
    margin-bottom: 5px;
}

@media (max-width: 768px) {
    .header h2 {
        font-size: 20px;
        margin-bottom: 10px;
    }
    .header a.back-btn {
        padding: 8px 14px;
        font-size: 14px;
        margin-top: 5px;
    }
    .about-content-wrapper {
        padding: 30px 15px;
        margin: 20px 10px;
    }
}
</style>
</head>
<body>
<div class="header">
    <h2><i class="fa-solid fa-circle-info"></i> About</h2>
    <a href="<?php echo $dashboard; ?>" class="back-btn">
        <i class="fa-solid fa-arrow-left"></i> Back
    </a>
</div>
<div class="about-content-wrapper">
    <div class="about-content">
        <h2>ABOUT US</h2>
        
        <p>
            <i class="fa-solid fa-shield-halved"></i>
            The <strong class="keyword">TRACKU</strong> is a comprehensive web-based platform designed to enhance the <strong class="keyword">safety</strong>, <strong class="keyword">efficiency</strong>, and <strong class="keyword">transparency</strong> of school transportation. 
            Managing student transportation in modern educational institutions is a significant challenge, as traditional manual methods are often inefficient, prone to human error, and fail to provide real-time information to parents, students, or administrators. 
            This system addresses these challenges by integrating advanced technology to monitor buses in real-time, manage routes effectively, and facilitate seamless communication between all stakeholders.
        </p>
        
        <p>
            <i class="fa-solid fa-bus"></i>
            By automating route management, enabling real-time communication, and maintaining comprehensive operational logs, the system improves <strong class="keyword">operational efficiency</strong>, enhances <strong class="keyword">student safety</strong>, and ensures <strong class="keyword">transparency and accountability</strong> across school transportation operations. 
            Its scalable design can accommodate multiple buses, routes, and a large student population, making it suitable for schools of all sizes. Overall, the Student Bus Tracking System empowers administrators, drivers, parents, and students alike, providing a reliable, safe, and user-friendly platform that enhances the overall school transportation experience.
        </p>
        <div class="founders">
            <div><strong>Founder:</strong> IRINE MICHEAL</div>
            <div><strong>Co-Founder:</strong> ASHLIN TOMY</div>
        </div>
    </div>
</div>

</body>
</html>
