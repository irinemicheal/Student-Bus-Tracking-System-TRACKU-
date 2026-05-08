<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';

$error = '';
$success = '';
function digits_only(string $s): string {
    return preg_replace('/\D+/', '', $s);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = digits_only(trim($_POST['phone'] ?? ''));
    $student_unique_id = trim($_POST['student_unique_id'] ?? '');

    if (!$full_name || !$email || !$password || !$phone || !$student_unique_id) {
        $error = "All fields are required (including student unique ID).";
    } elseif (!preg_match('/^[A-Za-z .]{3,}$/', $full_name)) {
        $error = "Full name must be at least 3 characters and may contain letters, spaces and periods.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
        $error = "Password must be at least 6 characters with 1 uppercase letter and 1 number.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = "Phone must be a valid 10-digit Indian number starting with 6-9.";
    }
    if (!$error) {
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email=? OR phone_no=? LIMIT 1");
        if (!$stmt) {
            $error = "Database error.";
        } else {
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows) $error = "Email or phone already used.";
            $stmt->close();
        }
    }
    $student_id = null;
    if (!$error) {
        $stmt = $mysqli->prepare("SELECT student_id, parent_id FROM students WHERE student_unique_id = ? LIMIT 1");
        if (!$stmt) {
            $error = "Database error.";
        } else {
            $stmt->bind_param("s", $student_unique_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if (!$res || $res->num_rows === 0) {
                $error = "Student with that Unique ID not found.";
            } else {
                $srow = $res->fetch_assoc();
                $student_id = intval($srow['student_id']);
                if (!empty($srow['parent_id'])) {
                    $error = "This student already has a parent linked.";
                }
            }
            $stmt->close();
        }
    }
    if (!$error && $student_id !== null) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, phone_no, role) VALUES (?, ?, ?, ?, 'parent')");
        if (!$stmt) {
            $error = "Database error (users prepare failed).";
        } else {
            $stmt->bind_param("ssss", $full_name, $email, $hash, $phone);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $stmt->close();
                $stmt2 = $mysqli->prepare("INSERT INTO parents (user_id, full_name, email, phone, student_id) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt2) {
                    $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                    $error = "Database error (parents prepare failed).";
                } else {
                    $stmt2->bind_param("isssi", $user_id, $full_name, $email, $phone, $student_id);
                    if ($stmt2->execute()) {
                        $stmt2->close();
$stmt3 = $mysqli->prepare("UPDATE students SET parent_id = ? WHERE student_id = ?");
if (!$stmt3) {
    $error = "Failed to link student (update prepare failed).";
} else {
    $parent_id = $mysqli->insert_id; 
    $stmt3->bind_param("ii", $parent_id, $srow['student_id']); 
                            if ($stmt3->execute()) {
                              $success = "Parent account created and student linked successfully ✅";
                            } else {
                              $mysqli->query("DELETE FROM parents WHERE parent_id=" . intval($parent_id));
                              $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                              $error = "Failed to link student record.";
                            }
                            $stmt3->close();
                          }

                    } else {
                        $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                        $error = "Failed to insert parent record.";
                        $stmt2->close();
                    }
                }
            } else {
                $error = "Failed to create user account.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Parent (Admin)</title>
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
body{font-family:Inter,Arial,Helvetica,sans-serif;background:linear-gradient(180deg,#071029 0%,#11203a 100%);
color:var(--muted);display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px}
.card{background:var(--card);padding:24px;border-radius:10px;width:560px;max-width:95%;box-shadow:0 6px 18px rgba(2,6,23,0.6)}
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
.back-link{display:inline-block;margin-top:10px;color:#fff;text-decoration:underline}

.errorBox{background:rgba(255,92,92,0.12);color:#ffdede;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.successBox{background:rgba(76,175,80,0.12);color:#dfffe0;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.small-hint{font-size:12px;color:#98a6bd;margin-top:6px}
</style>
</head>
<body>
<div class="card">
  <h2>Add Parent (Admin)</h2>

  <?php if ($error): ?><div class="errorBox"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="successBox"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form id="parentForm" method="post" autocomplete="off" novalidate>
    <div class="form-row">
      <label for="full_name">Full Name (will be used as username)</label>
      <input id="full_name" name="full_name" class="input-el" placeholder="Full Name (letters, spaces, periods allowed)" required value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>">
      <div class="check-icon" id="nameCheck"></div>
      <div class="msg" id="nameMsg"></div>
    </div>
    <div class="form-row">
      <label for="email">Email</label>
      <input id="email" name="email" class="input-el" placeholder="you@example.com" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
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
      <label for="phone">Mobile Number</label>
      <input id="phone" name="phone" class="input-el" placeholder="98765 43210" maxlength="13" required value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
      <div class="check-icon" id="phoneCheck"></div>
      <div class="msg" id="phoneMsg"></div>
      <div class="small-hint">Indian numbers only. Auto-formats as <code>XXXXX XXXXX</code></div>
    </div>
    <div class="form-row">
      <label for="student_unique_id">Student Unique ID (enter the student's unique ID)</label>
      <input id="student_unique_id" name="student_unique_id" class="input-el" placeholder="e.g. STU1001" required value="<?= isset($student_unique_id) ? htmlspecialchars($student_unique_id) : '' ?>">
      <div class="check-icon" id="studentUidCheck"></div>
      <div class="msg" id="studentUidMsg"></div>
    </div>

    <div style="margin-top:10px;">
      <button type="submit" class="button" id="submitBtn" disabled>Add Parent</button>
    </div>

    <div style="margin-top:10px;">
      <a href="manage_users.php" class="back-link">← Back to Manage Users</a>
    </div>
  </form>
</div>

<script>
function el(id){ return document.getElementById(id); }
function setCheck(id,state){ const n=el(id); if(!n) return; n.innerHTML = state==='ok' ? '✅' : (state==='taken' ? '❌' : ''); }
function setMsg(id,text){ const n=el(id); if(!n) return; n.textContent = text || ''; }
function addClassInput(input,ok){ if(!input) return; input.classList.remove('valid','invalid'); if(ok===true) input.classList.add('valid'); else if(ok===false) input.classList.add('invalid'); }

function checkServer(params, cb){
  fetch('validate.php?' + new URLSearchParams(params), { cache:'no-store' })
    .then(r => r.json()).then(cb).catch(()=> cb({ status: 'ok' }));
}

const fld = {
  name: el('full_name'), nameCheck: el('nameCheck'), nameMsg: el('nameMsg'),
  email: el('email'), emailCheck: el('emailCheck'), emailMsg: el('emailMsg'),
  password: el('password'), passwordCheck: el('passwordCheck'), passwordMsg: el('passwordMsg'),
  phone: el('phone'), phoneCheck: el('phoneCheck'), phoneMsg: el('phoneMsg'),
  studentUid: el('student_unique_id'), studentUidCheck: el('studentUidCheck'), studentUidMsg: el('studentUidMsg'),
  submit: el('submitBtn'), form: el('parentForm')
};

let V = { name:false, email:false, password:false, phone:false, student:false };
function toggleSubmit(){ fld.submit.disabled = !(V.name && V.email && V.password && V.phone && V.student); }

fld.name.addEventListener('input', ()=> {
  const v = fld.name.value.trim();
  if (!/^[A-Za-z .]{3,}$/.test(v)) { setMsg('nameMsg','At least 3 characters; letters, space and periods allowed'); addClassInput(fld.name,false); V.name=false; }
  else { setMsg('nameMsg',''); addClassInput(fld.name,true); V.name=true; }
  toggleSubmit();
});
fld.email.addEventListener('input', ()=> {
  const v = fld.email.value.trim();
  if (!v) { setMsg('emailMsg','Email required'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  const gmailRegex = /^[A-Za-z0-9._%+-]+@gmail\.com$/i;
  const parts = v.split('@'); const local = parts[0] || '';
  if (!gmailRegex.test(v)) { setMsg('emailMsg','Use a valid Gmail address, e.g. user@gmail.com'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  if (/^\d+$/.test(local)) { setMsg('emailMsg','Local part cannot be only numbers'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  setMsg('emailMsg','');
  checkServer({ type:'email', value:v }, function(d){
    if (d && d.status==='taken'){ setMsg('emailMsg','Email already used'); setCheck('emailCheck','taken'); addClassInput(fld.email,false); V.email=false; }
    else { setMsg('emailMsg',''); setCheck('emailCheck','ok'); addClassInput(fld.email,true); V.email=true; }
    toggleSubmit();
  });
});
fld.password.addEventListener('input', ()=> {
  const ok = /^(?=.*[A-Z])(?=.*\d).{6,}$/.test(fld.password.value);
  if (!ok) { setMsg('passwordMsg','Must include capital & number (min 6)'); setCheck('passwordCheck',''); addClassInput(fld.password,false); V.password=false; }
  else { setMsg('passwordMsg',''); setCheck('passwordCheck','ok'); addClassInput(fld.password,true); V.password=true; }
  toggleSubmit();
});
fld.phone.addEventListener('input', ()=> {
  let digits = fld.phone.value.replace(/\D/g,'').slice(0,10);
  fld.phone.value = digits.length>5 ? digits.slice(0,5)+' '+digits.slice(5) : digits;
  setMsg('phoneMsg',''); setCheck('phoneCheck',''); addClassInput(fld.phone,null); V.phone=false; toggleSubmit();
  if(digits.length<10) return;
  if(!/^[6-9]/.test(digits)){ setMsg('phoneMsg','Must start with digits 6-9'); setCheck('phoneCheck','taken'); addClassInput(fld.phone,false); V.phone=false; toggleSubmit(); return; }
  checkServer({ type:'phone', value:digits }, function(d){ if(d && d.status==='taken'){ setMsg('phoneMsg','Phone already used'); setCheck('phoneCheck','taken'); addClassInput(fld.phone,false); V.phone=false; } else { setMsg('phoneMsg',''); setCheck('phoneCheck','ok'); addClassInput(fld.phone,true); V.phone=true; } toggleSubmit(); });
});
fld.studentUid.addEventListener('input', ()=> {
  const v=fld.studentUid.value.trim();
  if(v.length<2){ setMsg('studentUidMsg','Enter student unique ID'); setCheck('studentUidCheck',''); addClassInput(fld.studentUid,false); V.student=false; toggleSubmit(); return; }
  setMsg('studentUidMsg','Will be checked on submit'); setCheck('studentUidCheck',''); addClassInput(fld.studentUid,true); V.student=true; toggleSubmit();
});
fld.form.addEventListener('submit', function(e){ if(!(V.name && V.email && V.password && V.phone && V.student)){ e.preventDefault(); return; } fld.phone.value = fld.phone.value.replace(/\D/g,''); });
</script>
</body>
</html>
