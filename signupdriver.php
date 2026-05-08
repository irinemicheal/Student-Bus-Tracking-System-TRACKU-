<?php
declare(strict_types=1);
session_start();
include 'db_connect.php';

$error=''; $success='';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = preg_replace('/\D+/', '', trim($_POST['phone'] ?? ''));

    if (!$username || !$email || !$password || !$phone) $error = "All fields required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email.";
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) $error = "Weak password.";
    elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) $error = "Invalid phone.";

    if (!$error) {
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username=? OR email=? OR phone=? LIMIT 1");
        $stmt->bind_param("sss", $username, $email, $phone);
        $stmt->execute();
        $r = $stmt->get_result(); if ($r && $r->num_rows) $error = "Username/email/phone already used.";
        $stmt->close();
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, phone, role) VALUES (?,?,?,?, 'driver')");
        $stmt->bind_param("ssss", $username, $email, $hash, $phone);
        if ($stmt->execute()) {
            $uid = $stmt->insert_id; $stmt->close();
            $stmt2 = $mysqli->prepare("INSERT INTO drivers (user_id, full_name, phone) VALUES (?,?,?)");
            $stmt2->bind_param("iss", $uid, $username, $phone);
            if ($stmt2->execute()) $success = "Driver account created. <a href='login.php'>Login</a>";
            else { $error = "Failed to insert driver."; $mysqli->query("DELETE FROM users WHERE user_id=$uid"); }
            $stmt2->close();
        } else $error = "Failed to create user: " . $stmt->error;
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Driver Signup</title>
<style>
body{font-family:Arial;background:#1e3c72;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{background:rgba(255,255,255,0.06);padding:20px;border-radius:8px;width:380px}
input,button{width:100%;padding:10px;margin:8px 0;border-radius:6px;border:none;font-size:14px}
.invalid{border:2px solid #ff5252} .valid{border:2px solid #4caf50}
small.error{color:#ffb3b3;display:block;height:16px}
button:disabled{background:#444}
.errorBox{background:#ff5252;padding:8px;border-radius:5px;margin-bottom:8px;text-align:center}
.successBox{background:#4caf50;padding:8px;border-radius:5px;margin-bottom:8px;text-align:center}
</style>
</head>
<body>
<form method="post" class="card" autocomplete="off" novalidate>
<h2>Driver Sign Up</h2>
<?php if ($error): ?><div class="errorBox"><?=htmlspecialchars($error)?></div><?php endif; ?>
<?php if ($success): ?><div class="successBox"><?=$success?></div><?php endif; ?>

<input id="username" name="username" placeholder="Username" required><small id="usernameMsg" class="error"></small>
<input id="email" name="email" placeholder="Email" required><small id="emailMsg" class="error"></small>
<input id="password" type="password" name="password" placeholder="Password" required><small id="passwordMsg" class="error"></small>

<div style="position:relative">
  <input id="phone" name="phone" placeholder="98765 43210" maxlength="13" required>
  <span id="phoneCheck" style="position:absolute; right:10px; top:50%; transform:translateY(-50%); font-size:18px"></span>
</div>
<small id="phoneMsg" class="error"></small>

<button type="submit" id="submitBtn" disabled>Create Driver Account</button>
</form>

<script>
function showError(el,input,text){ el.textContent=text; input.classList.add('invalid'); input.classList.remove('valid'); }
function clearError(el,input){ el.textContent=''; input.classList.remove('invalid'); input.classList.add('valid'); }
function checkServer(params, cb){ const qs = new URLSearchParams(params).toString(); fetch('validate.php?' + qs).then(r=>r.json()).then(cb).catch(()=>{}); }

const username=document.getElementById('username'), usernameMsg=document.getElementById('usernameMsg');
const email=document.getElementById('email'), emailMsg=document.getElementById('emailMsg');
const password=document.getElementById('password'), passwordMsg=document.getElementById('passwordMsg');
const phone=document.getElementById('phone'), phoneMsg=document.getElementById('phoneMsg'), phoneCheck=document.getElementById('phoneCheck');
const submitBtn=document.getElementById('submitBtn');
let validUsername=false, validEmail=false, validPassword=false, validPhone=false;
function toggleSubmit(){ submitBtn.disabled = !(validUsername && validEmail && validPassword && validPhone); }


username.addEventListener('input', ()=>{ const v=username.value.trim(); if (v.length<3){ showError(usernameMsg,username,'Min 3 chars'); validUsername=false; toggleSubmit(); return;} checkServer({type:'username', value:v}, d=>{ if (d.status==='taken'){ showError(usernameMsg,username,'❌ used'); validUsername=false;} else { clearError(usernameMsg,username); validUsername=true;} toggleSubmit(); }); });

email.addEventListener('input', ()=>{ const v=email.value.trim(); if (!v.includes('@')){ showError(emailMsg,email,'Invalid'); validEmail=false; toggleSubmit(); return;} checkServer({type:'email', value:v}, d=>{ if (d.status==='taken'){ showError(emailMsg,email,'❌ used'); validEmail=false;} else{ clearError(emailMsg,email); validEmail=true;} toggleSubmit(); }); });


password.addEventListener('input', ()=>{ const v=password.value; if (/^(?=.*[A-Z])(?=.*\d).{6,}$/.test(v)){ clearError(passwordMsg,password); validPassword=true;} else{ showError(passwordMsg,password,'Must include Capital & Number (min6)'); validPassword=false;} toggleSubmit(); });


phone.addEventListener('input', ()=>{ let digits = phone.value.replace(/\D/g,'').slice(0,10); if (digits.length>5) phone.value = digits.slice(0,5) + ' ' + digits.slice(5); else phone.value = digits; phoneCheck.textContent=''; if (digits.length<10){ showError(phoneMsg,phone,'Enter 10 digits'); validPhone=false; toggleSubmit(); return;} if (!/^[6-9]/.test(digits)){ showError(phoneMsg,phone,'Must start with 6-9'); validPhone=false; toggleSubmit(); return;} checkServer({type:'phone', value:digits}, d=>{ if (d.status==='taken'){ showError(phoneMsg,phone,'❌ used'); validPhone=false; } else { clearError(phoneMsg,phone); validPhone=true; phoneCheck.textContent='✅'; phoneCheck.style.color='lightgreen'; } toggleSubmit(); }); });
</script>
</body>
</html>
