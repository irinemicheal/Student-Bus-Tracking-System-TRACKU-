<?php
session_start();
if (isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php"); 
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout</title>
<style>
:root {
    --bg: #0f1724;
    --card: rgba(255,255,255,0.05);
    --primary: #2466d9;
    --danger: #dc3545;
    --muted: #cbd5e1;
    --input-bg: rgba(255,255,255,0.03);
    --shadow: 0 6px 18px rgba(2,6,23,0.5);
}
* { box-sizing: border-box; margin:0; padding:0; font-family: 'Poppins', sans-serif; }
body {
    background: linear-gradient(180deg,#071029 0%,#11203a 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100vh;
    color: var(--muted);
}

.logout-card {
    background: var(--card);
    padding: 40px 35px; 
    border-radius: 12px;
    box-shadow: var(--shadow);
    width: 450px;       
    max-width: 95%;      
    text-align: center;
    position: relative;
}


.logout-card h2 {
    color: #fff;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.logout-card h2::before {
    content: "🔒";
}

.logout-card p {
    margin-bottom: 25px;
    color: var(--muted);
    font-size: 15px;
}

.btn {
    padding: 10px 22px;
    margin: 5px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 500;
    transition: 0.2s;
}

.btn-yes {
    background: var(--danger);
    color: #fff;
}
.btn-yes:hover { background: #b02a37; }

.btn-no {
    background: var(--primary);
    color: #fff;
    text-decoration: none;
    display: inline-block;
}
.btn-no:hover { background: #1b4eb8; }

.logout-card::after {
    content: "❗";
    position: absolute;
    top: 15px;
    right: 15px;
    font-size: 20px;
}
</style>
</head>
<body>
    <div class="logout-card">
        <h2>Confirm Logout</h2>
        <p>Are you sure you want to log out of your account?</p>
        <form method="POST">
            <button type="submit" name="confirm_logout" class="btn btn-yes">Yes, Logout</button>
            <a href="javascript:history.back()" class="btn btn-no">Cancel</a>
        </form>
    </div>
</body>
</html>
