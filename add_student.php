<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    $full_name = trim($_POST['username'] ?? ''); 
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = preg_replace('/\D+/', '', trim($_POST['phone'] ?? '')); 
    $student_class = trim($_POST['student_class'] ?? '');
    $roll_number = trim($_POST['roll_number'] ?? '');
    $student_unique_id = trim($_POST['student_unique_id'] ?? '');

    if (!$full_name || !$email || !$password || !$phone || !$student_class || !$roll_number || !$student_unique_id) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
        $error = "Password must include a capital letter and a number (min 6 chars).";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = "Phone must be a valid 10-digit Indian number starting with 6-9.";
  } elseif (!preg_match('/^[1-9][0-9]?$/', $roll_number)) { // allow 1 or 2 digits, no leading zero
    $error = "Roll number must be 1 or 2 digits and cannot start with 0.";
    }

    if (!$error) {
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE username=? OR email=? OR phone_no=? LIMIT 1");
        if (!$stmt) {
            $error = "Database error (prepare failed).";
        } else {
            $stmt->bind_param("sss", $full_name, $email, $phone);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $r->num_rows) {
                $error = "Username, email or phone already used.";
            }
            $stmt->close();
        }
    }

    if (!$error) {
      
        $stmt = $mysqli->prepare("SELECT student_id FROM students WHERE student_unique_id = ? LIMIT 1");
        if (!$stmt) {
            $error = "Database error (prepare failed).";
        } else {
            $stmt->bind_param("s", $student_unique_id);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $r->num_rows) $error = "Student unique ID already used.";
            $stmt->close();
        }
    }

    if (!$error) {
      
        $stmt = $mysqli->prepare("SELECT student_id FROM students WHERE class = ? AND roll_number = ? LIMIT 1");
        if (!$stmt) {
            $error = "Database error (prepare failed).";
        } else {
            $stmt->bind_param("ss", $student_class, $roll_number);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && $r->num_rows) $error = "Roll number already exists in this class.";
            $stmt->close();
        }
    }
    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, phone_no, role) VALUES (?, ?, ?, ?, 'student')");
        if (!$stmt) {
            $error = "Failed to prepare user insert.";
        } else {
            $stmt->bind_param("ssss", $full_name, $email, $hash, $phone);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $stmt->close();

                $stmt2 = $mysqli->prepare("INSERT INTO students (user_id, full_name, email, phone, class, roll_number, student_unique_id)
                                          VALUES (?, ?, ?, ?, ?, ?, ?)");
                if (!$stmt2) {
                    $error = "Failed to prepare student insert.";
                   
                    $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                } else {
                    
                    $stmt2->bind_param("issssss", $user_id, $full_name, $email, $phone, $student_class, $roll_number, $student_unique_id);
                    if ($stmt2->execute()) {
                        $success = "Student account created ✅ <a href='manage_users.php' style='color:#fff;text-decoration:underline'>Back to Manage Users</a>";
                    } else {
                        $error = "Failed to insert student record.";

                        $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                    }
                    $stmt2->close();
                }
            } else {
                $error = "Failed to create user.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Student (Admin)</title>
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
.card{background:var(--card);padding:24px;border-radius:10px;width:520px;max-width:95%;box-shadow:0 6px 18px rgba(2,6,23,0.6)}
h2{margin:0 0 12px 0;color:#fff}
.form-row{position:relative;margin-bottom:12px}
label{display:block;font-size:13px;color:#b8c6d9;margin-bottom:6px}
.input-el{width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);background:var(--input-bg);color:#fff;font-size:15px}
.input-el:focus{outline:none;box-shadow:0 0 0 3px rgba(74,144,226,0.06);border-color:rgba(255,255,255,0.12)}
.check-icon{position:absolute;right:8px;top:36px; font-size:18px; width:28px; height:28px; display:flex;align-items:center;justify-content:center;pointer-events:none}
.msg {font-size:13px;height:16px;margin-top:6px;color:var(--err)}
.input-el.valid { border:2px solid rgba(76,175,80,0.25); }
.input-el.invalid { border:2px solid rgba(255,92,92,0.25); }

.button {width:100%;padding:11px;border-radius:8px;border:none;background:#2466d9;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
.button:disabled {background:#314155;cursor:not-allowed;opacity:0.9}

.small-hint{font-size:12px;color:#98a6bd;margin-top:6px}

.errorBox{background:rgba(255,92,92,0.12);color:#ffdede;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.successBox{background:rgba(76,175,80,0.12);color:#dfffe0;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}

.back-link {display:inline-block;margin-top:10px;color:#fff;text-decoration:underline}
</style>
</head>
<body>
<div class="card">
  <h2>Add Student (Admin)</h2>

  <?php if ($error): ?><div class="errorBox"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="successBox"><?= $success ?></div><?php endif; ?>

  <form id="signupForm" method="post" autocomplete="off" novalidate>
    <div class="form-row">
      <label for="username">Full Name</label>
      <input id="username" name="username" class="input-el" placeholder="Full name" required>
      <div class="check-icon" id="usernameCheck"></div>
      <div class="msg" id="usernameMsg"></div>
    </div>
    <div class="form-row">
      <label for="email">Email</label>
      <input id="email" name="email" class="input-el" placeholder="you@example.com" required>
      <div class="check-icon" id="emailCheck"></div>
      <div class="msg" id="emailMsg"></div>
    </div>
    <div class="form-row">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" class="input-el" placeholder="Min 6 chars, include A and 1" required>
      <div class="check-icon" id="passwordCheck"></div>
      <div class="msg" id="passwordMsg"></div>
    </div>
    <div class="form-row">
      <label for="student_class">Class</label>
      <input id="student_class" name="student_class" class="input-el" placeholder="Class (e.g. BCA)" required>
      <div class="check-icon" id="classCheck"></div>
      <div class="msg" id="classMsg"></div>
    </div>
    <div class="form-row">
      <label for="roll_number">Roll Number </label>
      <input id="roll_number" name="roll_number" class="input-el" placeholder="e.g. 12" required maxlength="2">
      <div class="check-icon" id="rollCheck"></div>
      <div class="msg" id="rollMsg"></div>
    </div>
    <div class="form-row">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" class="input-el" placeholder="98765 43210" maxlength="13" required>
      <div class="check-icon" id="phoneCheck"></div>
      <div class="msg" id="phoneMsg"></div>
      <div class="small-hint">Indian numbers only. Auto-formats as <code>XXXXX XXXXX</code></div>
    </div>
    <div class="form-row">
      <label for="student_unique_id">Unique Student ID</label>
      <input id="student_unique_id" name="student_unique_id" class="input-el" placeholder="School unique id" required>
      <div class="check-icon" id="uidCheck"></div>
      <div class="msg" id="uidMsg"></div>
    </div>

    <div style="margin-top:10px;">
      <button type="submit" class="button" id="submitBtn" disabled>Add Student</button>
    </div>
    <div style="margin-top:10px;">
      <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
    </div>
  </form>
</div>

<script>
const endpoints = { validate: 'validate.php' };

function el(id){ return document.getElementById(id); }
function setCheck(id, state){
  const node = el(id);
  if (!node) return;
  if (state === 'ok') node.innerHTML = '✅';
  else if (state === 'taken') node.innerHTML = '❌';
  else node.innerHTML = '';
}
function setMsg(id, text){ el(id).textContent = text || ''; }
function addClassInput(input, ok){
  if (ok === true){ input.classList.add('valid'); input.classList.remove('invalid'); }
  else if (ok === false){ input.classList.add('invalid'); input.classList.remove('valid'); }
  else { input.classList.remove('invalid'); input.classList.remove('valid'); }
}
function checkServer(params, cb){
  const qs = new URLSearchParams(params).toString();
  fetch(endpoints.validate + '?' + qs, { cache: 'no-store' })
    .then(r => r.json())
    .then(cb)
    .catch(err => { console.error('validate.php error', err); cb({ status: 'ok' }); });
}

const fld = {
  username: el('username'), usernameCheck: el('usernameCheck'), usernameMsg: el('usernameMsg'),
  email: el('email'), emailCheck: el('emailCheck'), emailMsg: el('emailMsg'),
  password: el('password'), passwordCheck: el('passwordCheck'), passwordMsg: el('passwordMsg'),
  student_class: el('student_class'), classCheck: el('classCheck'), classMsg: el('classMsg'),
  roll_number: el('roll_number'), rollCheck: el('rollCheck'), rollMsg: el('rollMsg'),
  phone: el('phone'), phoneCheck: el('phoneCheck'), phoneMsg: el('phoneMsg'),
  uid: el('student_unique_id'), uidCheck: el('uidCheck'), uidMsg: el('uidMsg'),
  submit: el('submitBtn'), form: el('signupForm')
};

const V = { username: false, email: false, password: false, student_class: false, roll_number: false, phone: false, uid: false };
function toggleSubmit(){ fld.submit.disabled = !(V.username && V.email && V.password && V.student_class && V.roll_number && V.phone && V.uid); }

fld.username.addEventListener('input', ()=>{
  const v = fld.username.value.trim();
  if (v.length < 3) {
    setMsg('usernameMsg', 'Min 3 characters');
    setCheck('usernameCheck','');
    addClassInput(fld.username,false);
    V.username = false; toggleSubmit(); return;
  }
  setMsg('usernameMsg', '');
  checkServer({ type: 'username', value: v }, function(d){
    if (d && d.status === 'taken'){
      setMsg('usernameMsg', 'Username already used');
      setCheck('usernameCheck','taken');
      addClassInput(fld.username,false);
      V.username = false;
    } else {
      setMsg('usernameMsg', '');
      setCheck('usernameCheck','ok');
      addClassInput(fld.username,true);
      V.username = true;
    }
    toggleSubmit();
  });
});
fld.email.addEventListener('input', ()=>{
  const v = fld.email.value.trim();
  if (!v) {
    setMsg('emailMsg', 'Email required');
    setCheck('emailCheck','');
    addClassInput(fld.email,false);
    V.email = false; toggleSubmit(); return;
  }

  // Enforce Gmail addresses and prevent numeric-only local part
  const gmailRegex = /^[A-Za-z0-9._%+-]+@gmail\.com$/i;
  const parts = v.split('@');
  const local = parts[0] || '';

  if (!gmailRegex.test(v)){
    setMsg('emailMsg', 'Use a valid Gmail address, e.g. user@gmail.com');
    setCheck('emailCheck','');
    addClassInput(fld.email,false);
    V.email = false; toggleSubmit(); return;
  }

  if (/^\d+$/.test(local)){
    setMsg('emailMsg', 'Local part cannot be only numbers');
    setCheck('emailCheck','');
    addClassInput(fld.email,false);
    V.email = false; toggleSubmit(); return;
  }

  // Passed client-side checks — check server uniqueness
  setMsg('emailMsg','');
  checkServer({ type: 'email', value: v }, function(d){
    if (d && d.status === 'taken') {
      setMsg('emailMsg', 'Email already used');
      setCheck('emailCheck','taken');
      addClassInput(fld.email,false);
      V.email=false;
    } else {
      setMsg('emailMsg','');
      setCheck('emailCheck','ok');
      addClassInput(fld.email,true);
      V.email=true;
    }
    toggleSubmit();
  });
});
fld.password.addEventListener('input', ()=>{
  const v = fld.password.value;
  const ok = /^(?=.*[A-Z])(?=.*\d).{6,}$/.test(v);
  if (!ok){
    setMsg('passwordMsg','Must include capital & number (min 6)');
    setCheck('passwordCheck','');
    addClassInput(fld.password,false);
    V.password=false;
  } else {
    setMsg('passwordMsg','');
    setCheck('passwordCheck','ok');
    addClassInput(fld.password,true);
    V.password=true;
  }
  toggleSubmit();
});

fld.student_class.addEventListener('input', ()=>{
  const v = fld.student_class.value.trim();
  if (!v){
    setMsg('classMsg','Class required');
    setCheck('classCheck','');
    addClassInput(fld.student_class,false);
    V.student_class=false;
  } else {
    setMsg('classMsg','');
    setCheck('classCheck','ok');
    addClassInput(fld.student_class,true);
    V.student_class=true;
  }
  fld.roll_number.dispatchEvent(new Event('input'));
  toggleSubmit();
});
fld.roll_number.addEventListener('input', ()=>{
  const r = fld.roll_number.value.trim();
  const c = fld.student_class.value.trim();
  if (!/^[1-9][0-9]?$/.test(r)) {
    setMsg('rollMsg', 'Roll must be 1 or 2 digits and cannot start with 0');
    setCheck('rollCheck','');
    addClassInput(fld.roll_number,false);
    V.roll_number=false; toggleSubmit(); return;
  }
  if (!c) {
    setMsg('rollMsg', 'Provide class first');
    setCheck('rollCheck','');
    addClassInput(fld.roll_number,false);
    V.roll_number=false; toggleSubmit(); return;
  }

  setMsg('rollMsg','');
  checkServer({ type: 'roll_number', value: r, class: c }, function(d){
    if (d && d.status === 'taken'){
      setMsg('rollMsg','Roll exists in this class');
      setCheck('rollCheck','taken');
      addClassInput(fld.roll_number,false);
      V.roll_number=false;
    } else {
      setMsg('rollMsg','');
      setCheck('rollCheck','ok');
      addClassInput(fld.roll_number,true);
      V.roll_number=true;
    }
    toggleSubmit();
  });
});
fld.phone.addEventListener('input', ()=>{
  let digits = fld.phone.value.replace(/\D/g,'').slice(0,10);
  if (digits.length > 5) fld.phone.value = digits.slice(0,5) + ' ' + digits.slice(5);
  else fld.phone.value = digits;

  setMsg('phoneMsg','');
  setCheck('phoneCheck','');
  addClassInput(fld.phone,null);
  V.phone = false;
  toggleSubmit();

  if (digits.length < 10) {
    return;
  }

  if (!/^[6-9]/.test(digits)){
    setMsg('phoneMsg','Must start with digits 6-9');
    setCheck('phoneCheck','taken');
    addClassInput(fld.phone,false);
    V.phone=false; toggleSubmit(); return;
  }

  checkServer({ type: 'phone', value: digits }, function(d){
    if (d && d.status === 'taken'){
      setMsg('phoneMsg','Phone already used');
      setCheck('phoneCheck','taken');
      addClassInput(fld.phone,false);
      V.phone=false;
    } else {
      setMsg('phoneMsg','');
      setCheck('phoneCheck','ok');
      addClassInput(fld.phone,true);
      V.phone=true;
    }
    toggleSubmit();
  });
});
fld.uid.addEventListener('input', ()=>{
  const v = fld.uid.value.trim();
  if (v.length < 2) {
    setMsg('uidMsg','Too short');
    setCheck('uidCheck','');
    addClassInput(fld.uid,false);
    V.uid=false; toggleSubmit(); return;
  }
  setMsg('uidMsg','');
  checkServer({ type: 'student_unique_id', value: v }, function(d){
    if (d && d.status === 'taken'){
      setMsg('uidMsg','ID already used');
      setCheck('uidCheck','taken');
      addClassInput(fld.uid,false);
      V.uid=false;
    } else {
      setMsg('uidMsg','');
      setCheck('uidCheck','ok');
      addClassInput(fld.uid,true);
      V.uid=true;
    }
    toggleSubmit();
  });
});

fld.form.addEventListener('submit', function(e){
  if (!(V.username && V.email && V.password && V.student_class && V.roll_number && V.phone && V.uid)){
    e.preventDefault();
    if (!V.username) fld.username.focus();
    return;
  }
  fld.phone.value = fld.phone.value.replace(/\D/g,'');
});
</script>
</body>
</html>
