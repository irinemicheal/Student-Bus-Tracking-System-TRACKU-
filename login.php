<?php
declare(strict_types=1);
session_start();
include 'db.php';
if(isset($_SESSION['username'],$_SESSION['role'])){
    switch($_SESSION['role']){
        case 'admin': header("Location: admin.php"); break;
        case 'driver': header("Location: driverdash.php"); break;
        case 'student': header("Location: studentdash.php"); break;
        case 'parent': header("Location: parentdash.php"); break;
    }
    exit;
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = trim($_POST['role'] ?? '');
    $admin_code = trim($_POST['admin_code'] ?? '');

    if(!$username || !$password || !$role){
        $error = "All fields are required!";
    } else {
        $stmt = $mysqli->prepare("SELECT user_id, username, password, role FROM users WHERE username=? AND role=?");
        $stmt->bind_param("ss",$username, $role);
        $stmt->execute();
        $stmt->store_result();

        if($stmt->num_rows === 1){
            $stmt->bind_result($user_id,$db_username,$db_password,$db_role);
            $stmt->fetch();

            if(password_verify($password,$db_password)){
                if($db_role === 'admin'){
                    if(!$admin_code){
                        $error = "Admin code is required!";
                    } else {
                        $stmt2 = $mysqli->prepare("SELECT id FROM admin_codes WHERE code=? AND used_by=? LIMIT 1");
                        $stmt2->bind_param("si",$admin_code,$user_id);
                        $stmt2->execute();
                        $r2 = $stmt2->get_result();
                        if(!$r2 || !$r2->num_rows){
                            $error = "Invalid admin code!";
                        }
                        $stmt2->close();
                    }
                }

                if(!$error){
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['username'] = $db_username;
                    $_SESSION['role'] = $db_role;

                    switch($db_role){
                        case 'admin': header("Location: admin.php"); break;
                        case 'driver': header("Location: driverdash.php"); break;
                        case 'student': header("Location: studentdash.php"); break;
                        case 'parent': header("Location: parentdash.php"); break;
                    }
                    exit;
                }

            } else {
                $error="Incorrect password!";
            }
        } else {
            $error="Username not found or role mismatch!";
        }
        $stmt->close();
    }
}
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Student Bus Tracking</title>
<style>
:root {
  --bg: #0f1724;
  --card: rgba(255,255,255,0.04);
  --muted: #cbd5e1;
  --err: #ff5c5c;
  --ok: #4caf50;
  --input-bg: rgba(255,255,255,0.03);
}
*{box-sizing:border-box;}
body {
    font-family: Inter, Arial, Helvetica, sans-serif;
    color: var(--muted);
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100vh;
    margin: 0;
    padding: 20px;

    background: url('loginpic.png') no-repeat center center fixed;
    background-size: cover;      
    background-attachment: fixed;

.card {
    background: rgba(135, 186, 232, 0.88); 
    padding: 24px;
    border-radius: 10px;
    width: 420px;
    max-width: 95%;
    box-shadow: 0 6px 18px rgba(2, 6, 23, 0.6);
    backdrop-filter: blur(6px); 
}

h2{margin:0 0 12px 0;color:#2466d9;text-align:center;}
.input-el{width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:var(--input-bg);color:#000;font-size:15px;margin:8px 0}
.input-el:focus{outline:none;box-shadow:0 0 0 3px rgba(74,144,226,0.06);border-color:rgba(255,255,255,0.12);}
select.input-el{color:#000;background:var(--input-bg);}
.check-icon{position:absolute;right:8px;top:36px;font-size:18px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;pointer-events:none;}
.msg {font-size:13px;height:16px;margin-top:6px;color:var(--err);}
.input-el.valid { border:2px solid rgba(76,175,80,0.25); }
.input-el.invalid { border:2px solid rgba(255,92,92,0.25); }
.button {width:100%;padding:11px;border-radius:8px;border:none;background:#2466d9;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
.button:disabled {background:#314155;cursor:not-allowed;opacity:0.9;}
.errorBox{background:rgba(255,92,92,0.12);color:#ffdede;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.successBox{background:rgba(76,175,80,0.12);color:#dfffe0;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
</style>
<script>
function toggleAdminCode() {
    const role = document.getElementById('role').value;
    document.getElementById('adminCodeRow').style.display = (role==='admin') ? 'block' : 'none';
}

// Tick mark validation
function setCheck(id,state){ const node=document.getElementById(id); if(!node)return; node.textContent=(state==='ok')?'✔':(state==='taken'?'✖':''); node.classList.toggle('invalid',state==='taken'); }
</script>
</head>
<body>
<div class="card">
<h2>Login To TRACKU</h2>
<?php if($error):?><div class="errorBox"><?=htmlspecialchars($error)?></div><?php endif;?>
<form method="post" autocomplete="off">
<div style="position:relative;">
<input type="text" name="username" id="username" class="input-el" placeholder="Username" required>
<div class="check-icon" id="usernameCheck"></div>
</div>

<div style="position:relative;">
<input type="password" name="password" id="password" class="input-el" placeholder="Password" required>
<div class="check-icon" id="passwordCheck"></div>
</div>

<div style="position:relative;">
<select name="role" id="role" class="input-el" onchange="toggleAdminCode()" required>
    <option value="">Select Role</option>
    <option value="admin">Admin</option>
    <option value="driver">Driver</option>
    <option value="student">Student</option>
    <option value="parent">Parent</option>
</select>
</div>

<div id="adminCodeRow" style="display:none;position:relative;">
<input type="text" name="admin_code" id="admin_code" class="input-el" placeholder="Admin Code">
<div class="check-icon" id="codeCheck"></div>
</div>

<div style="margin-top:10px;">
<button type="submit" class="button">Login</button>
</div>
<div style="margin-top:10px;text-align:center;">
<a href="signup.php" style="color:#000;text-decoration:underline;font-size:14px;">Back to Sign Up</a>
</div>
</form>
</div>
</body>
</html>
