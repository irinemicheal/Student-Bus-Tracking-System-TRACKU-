<?php
declare(strict_types=1);
session_start();
include 'db_connect.php';

$error = '';
$success = '';

$prefill_name = isset($_SESSION['signup_name']) ? $_SESSION['signup_name'] : '';
$prefill_email = isset($_SESSION['signup_email']) ? $_SESSION['signup_email'] : '';
$prefill_password = isset($_SESSION['signup_password']) ? $_SESSION['signup_password'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = preg_replace('/\D+/', '', trim($_POST['phone'] ?? ''));
    $licence = trim($_POST['licences_number'] ?? '');

    if (!$username || !$email || !$password || !$phone || !$licence) $error = "All fields required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Invalid email.";
    elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) $error = "Weak password.";
    elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) $error = "Invalid phone.";
    elseif (!preg_match('/^[A-Z]{2}[0-9]{2}[0-9A-Z]{11}$/', $licence)) $error = "Invalid driving licence format.";

    if (!$error) {$stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username=? OR email=? OR phone_no=? LIMIT 1");
$stmt->bind_param("sss", $username, $email, $phone);

        $stmt->execute();
        $r = $stmt->get_result(); if ($r && $r->num_rows) $error = "Username/email/phone already used.";
        $stmt->close();
    }

    if (!$error) {
        $stmt = $mysqli->prepare("SELECT driver_id FROM drivers WHERE licences_number=? LIMIT 1");
        $stmt->bind_param("s", $licence);
        $stmt->execute();
        $r = $stmt->get_result(); if ($r && $r->num_rows) $error = "Driving licence already used.";
        $stmt->close();
    }

    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT);$stmt = $mysqli->prepare("INSERT INTO users (username, email, password, phone_no, role) VALUES (?,?,?,?, 'driver')");
$stmt->bind_param("ssss", $username, $email, $hash, $phone);

        if ($stmt->execute()) {
            $uid = $stmt->insert_id;
            $stmt->close();
            $stmt2 = $mysqli->prepare("INSERT INTO drivers (user_id, full_name, phone, licences_number) VALUES (?,?,?,?)");
            $stmt2->bind_param("isss", $uid, $username, $phone, $licence);
            if ($stmt2->execute()) $success = "Driver account created ✅ <a href='login.php' style='color:#fff;text-decoration:underline'>Login</a>";
            else { $error = "Failed to insert driver."; $mysqli->query("DELETE FROM users WHERE user_id=$uid"); }
            $stmt2->close();
        } else $error = "Failed to create user: " . $stmt->error;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Driver Signup</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root {
  --bg: #0f1724;
  --card: rgba(255,255,255,0.04);
  --muted: #cbd5e1;
  --err: #ff5c5c;
  --ok: #4caf50;
  --input-bg: rgba(255,255,255,0.03);
}
*{box-sizing:border-box}
body{font-family:Inter,Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#071029 0%,#11203a 100%);color:var(--muted);display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
.card{background:var(--card);padding:24px;border-radius:10px;width:420px;max-width:95%;box-shadow:0 6px 18px rgba(2,6,23,0.6)}
h2{margin:0 0 12px 0;color:#fff}
.form-row{position:relative;margin-bottom:12px}
label{display:block;font-size:13px;color:#b8c6d9;margin-bottom:6px}
.input-el{width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:var(--input-bg);color:#fff;font-size:15px}
.input-el:focus{outline:none;box-shadow:0 0 0 3px rgba(74,144,226,0.06);border-color:rgba(255,255,255,0.12)}
.check-icon{position:absolute;right:8px;top:36px;font-size:18px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;pointer-events:none}
.msg {font-size:13px;height:16px;margin-top:6px;color:var(--err)}
.input-el.valid { border:2px solid rgba(76,175,80,0.25); }
.input-el.invalid { border:2px solid rgba(255,92,92,0.25); }

.button {width:100%;padding:11px;border-radius:8px;border:none;background:#2466d9;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
.button:disabled {background:#314155;cursor:not-allowed;opacity:0.9}

.errorBox{background:rgba(255,92,92,0.12);color:#ffdede;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.successBox{background:rgba(76,175,80,0.12);color:#dfffe0;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.small-hint{font-size:12px;color:#98a6bd;margin-top:6px}
</style>
</head>
<body>
<div class="card">
<h2>Driver Sign Up</h2>
<?php if ($error): ?><div class="errorBox"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="successBox"><?=$success?></div><?php endif; ?>
<form id="signupForm" method="post" autocomplete="off" novalidate>

<div class="form-row">
<label for="username">Username</label>
<input id="username" name="username" class="input-el" placeholder="Full Name or Username" required value="<?= htmlspecialchars($prefill_name) ?>">
<div class="check-icon" id="usernameCheck"></div>
<div class="msg" id="usernameMsg"></div>
</div>

<div class="form-row">
<label for="email">Email</label>
<input id="email" name="email" class="input-el" placeholder="you@example.com" required value="<?= htmlspecialchars($prefill_email) ?>">
<div class="check-icon" id="emailCheck"></div>
<div class="msg" id="emailMsg"></div>
</div>

<div class="form-row">
<label for="password">Password</label>
<input id="password" type="password" name="password" class="input-el" placeholder="Min 6 chars, include A and 1" required value="<?= htmlspecialchars($prefill_password) ?>">
<div class="check-icon" id="passwordCheck"></div>
<div class="msg" id="passwordMsg"></div>
</div>

<div class="form-row">
<label for="phone">Phone</label>
<input id="phone" name="phone" class="input-el" placeholder="98765 43210" maxlength="13" required>
<div class="check-icon" id="phoneCheck"></div>
<div class="msg" id="phoneMsg"></div>
<div class="small-hint">Indian numbers only. Auto-formats as <code>XXXXX XXXXX</code></div>
</div>

<div class="form-row">
<label for="licences_number">Driving Licence</label>
<input id="licences_number" name="licences_number" class="input-el" placeholder="e.g. DL01 23456789012" required>
<div class="check-icon" id="licenceCheck"></div>
<div class="msg" id="licenceMsg"></div>
</div>

<div style="margin-top:10px;">
<button type="submit" class="button" id="submitBtn" disabled>Create Account</button>
</div>
<div style="margin-top:10px;">
<button type="button" class="button" id="backBtn">Back to Signup</button>
</div>
</form>
</div>

<script>
function el(id){return document.getElementById(id);}
function setCheck(id,state){const n=el(id); if(!n)return; n.innerHTML=(state==='ok')?'✔':(state==='taken'?'✖':''); n.classList.toggle('invalid',state==='taken');}
function setMsg(id,text){el(id).textContent=text||'';}
function addClassInput(input,ok){input.classList.remove('invalid','valid'); if(ok===true) input.classList.add('valid'); else if(ok===false) input.classList.add('invalid');}
function checkServer(params, cb){ fetch('validate.php?'+new URLSearchParams(params)).then(r=>r.json()).then(cb).catch(()=>{cb({status:'ok'});}); }

const fld = {
  username: el('username'), usernameCheck: el('usernameCheck'), usernameMsg: el('usernameMsg'),
  email: el('email'), emailCheck: el('emailCheck'), emailMsg: el('emailMsg'),
  password: el('password'), passwordCheck: el('passwordCheck'), passwordMsg: el('passwordMsg'),
  phone: el('phone'), phoneCheck: el('phoneCheck'), phoneMsg: el('phoneMsg'),
  licence: el('licences_number'), licenceCheck: el('licenceCheck'), licenceMsg: el('licenceMsg'),
  submit: el('submitBtn'), form: el('signupForm')
};

let V = {username:false,email:false,password:false,phone:false,licence:false};
function toggleSubmit(){ fld.submit.disabled = !(V.username && V.email && V.password && V.phone && V.licence); }

fld.username.addEventListener('input', ()=> {
  const v=fld.username.value.trim();
  if(v.length<3){ setMsg('usernameMsg','Min 3 chars'); setCheck('usernameCheck',''); addClassInput(fld.username,false); V.username=false; toggleSubmit(); return;}
  checkServer({type:'username',value:v},d=>{ if(d.status==='taken'){ setMsg('usernameMsg','Username taken'); setCheck('usernameCheck','taken'); addClassInput(fld.username,false); V.username=false; } else { setMsg('usernameMsg',''); setCheck('usernameCheck','ok'); addClassInput(fld.username,true); V.username=true;} toggleSubmit(); });
});

fld.email.addEventListener('input', ()=> {
  const v = fld.email.value.trim();
  if (!v) { setMsg('emailMsg','Email required'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  const gmailRegex = /^[A-Za-z0-9._%+-]+@gmail\.com$/i;
  const parts = v.split('@'); const local = parts[0] || '';
  if (!gmailRegex.test(v)){
    setMsg('emailMsg','Use a valid Gmail address, e.g. user@gmail.com'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return;
  }
  if (/^\d+$/.test(local)){
    setMsg('emailMsg','Local part cannot be only numbers'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return;
  }
  setMsg('emailMsg','');
  checkServer({type:'email',value:v},d=>{ if(d && d.status==='taken'){ setMsg('emailMsg','Email used'); setCheck('emailCheck','taken'); addClassInput(fld.email,false); V.email=false;} else { setMsg('emailMsg',''); setCheck('emailCheck','ok'); addClassInput(fld.email,true); V.email=true;} toggleSubmit(); });
});
fld.password.addEventListener('input', ()=> {
  const v=fld.password.value;
  if(/^(?=.*[A-Z])(?=.*\d).{6,}$/.test(v)){ setMsg('passwordMsg',''); setCheck('passwordCheck','ok'); addClassInput(fld.password,true); V.password=true;} 
  else{ setMsg('passwordMsg','Capital & Number min 6'); setCheck('passwordCheck',''); addClassInput(fld.password,false); V.password=false;} toggleSubmit();
});

fld.phone.addEventListener('input', ()=> {
  let d = fld.phone.value.replace(/\D/g,'').slice(0,10);
  fld.phone.value = (d.length>5)?d.slice(0,5)+' '+d.slice(5):d;
  setMsg('phoneMsg',''); setCheck('phoneCheck',''); addClassInput(fld.phone,null); V.phone=false; toggleSubmit();
  if(d.length<10) return;
  if(!/^[6-9]/.test(d)){ setMsg('phoneMsg','Must start with 6-9'); setCheck('phoneCheck','taken'); addClassInput(fld.phone,false); V.phone=false; toggleSubmit(); return;}
  checkServer({type:'phone', value:d},d=>{ if(d.status==='taken'){ setMsg('phoneMsg','Phone used'); setCheck('phoneCheck','taken'); addClassInput(fld.phone,false); V.phone=false;} else { setMsg('phoneMsg',''); setCheck('phoneCheck','ok'); addClassInput(fld.phone,true); V.phone=true;} toggleSubmit(); });
});
fld.licence.addEventListener('input', ()=> {
  const v=fld.licence.value.trim().toUpperCase();
  fld.licence.value = v;
  if(!/^[A-Z]{2}[0-9]{2}[0-9A-Z]{11}$/.test(v)){ setMsg('licenceMsg','Invalid format'); setCheck('licenceCheck',''); addClassInput(fld.licence,false); V.licence=false; toggleSubmit(); return;}
  checkServer({type:'licences_number', value:v},d=>{ if(d.status==='taken'){ setMsg('licenceMsg','Licence used'); setCheck('licenceCheck','taken'); addClassInput(fld.licence,false); V.licence=false;} else { setMsg('licenceMsg',''); setCheck('licenceCheck','ok'); addClassInput(fld.licence,true); V.licence=true;} toggleSubmit(); });
});
fld.form.addEventListener('submit', e=> {
  if(!(V.username && V.email && V.password && V.phone && V.licence)){ e.preventDefault(); return; }
  fld.phone.value = fld.phone.value.replace(/\D/g,'');
});
document.getElementById('backBtn').addEventListener('click', ()=>{ window.location.href='signup.php'; });

document.addEventListener('DOMContentLoaded', function(){
  ['username','email','password','phone','licences_number'].forEach(function(id){
    var n = document.getElementById(id);
    if(n && n.value) n.dispatchEvent(new Event('input', { bubbles: true }));
  });
});
</script>
</body>
</html>

<?php
unset($_SESSION['signup_name'], $_SESSION['signup_email'], $_SESSION['signup_password']);
?>
