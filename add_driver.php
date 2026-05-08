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
    $licence = strtoupper(trim($_POST['licences_number'] ?? ''));

    if (!$full_name || !$email || !$password || !$phone || !$licence) {
        $error = "All fields are required.";
    } elseif (!preg_match('/^[A-Za-z ]{3,}$/', $full_name)) {
        $error = "Full name must be at least 3 letters (letters and spaces only).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (!preg_match('/^(?=.*[A-Z])(?=.*\d).{6,}$/', $password)) {
        $error = "Password must be at least 6 chars with 1 uppercase and 1 number.";
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = "Phone must be a valid 10-digit Indian number starting with 6-9.";
    } elseif (!preg_match('/^[A-Z]{2}[0-9]{2}[0-9A-Z]{11}$/', $licence)) {
        $error = "Driving licence format invalid (expected like DL01XXXXXXXXXXX).";
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

    if (!$error) {
        $stmt = $mysqli->prepare("SELECT driver_id FROM drivers WHERE licences_number=? LIMIT 1");
        if (!$stmt) {
            $error = "Database error.";
        } else {
            $stmt->bind_param("s", $licence);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows) $error = "Driving licence already used.";
            $stmt->close();
        }
    }
    if (!$error) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $mysqli->prepare("INSERT INTO users (username, email, password, phone_no, role) VALUES (?, ?, ?, ?, 'driver')");
        if (!$stmt) {
            $error = "Database error (prepare failed).";
        } else {
            $stmt->bind_param("ssss", $full_name, $email, $hash, $phone);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $stmt->close();

                // Note: some installations don't have `assigned_route`/`stops` columns in `drivers`.
                // Insert only the columns that are expected to exist.
                $stmt2 = $mysqli->prepare("INSERT INTO drivers (user_id, full_name, phone, licences_number) VALUES (?,?,?,?)");
                if (!$stmt2) {
                    $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                    $error = "Database error (prepare failed for drivers).";
                } else {
                    $stmt2->bind_param("isss", $user_id, $full_name, $phone, $licence);
                    if ($stmt2->execute()) {
                        $success = "Driver account created ✅";
                        $stmt2->close();
                    } else {
                    
                        $mysqli->query("DELETE FROM users WHERE user_id=" . intval($user_id));
                        $error = "Failed to insert driver record.";
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
<title>Add Driver | Admin</title>
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
body{
  font-family:Inter,Arial,Helvetica,sans-serif;
  background:linear-gradient(180deg,#071029 0%,#11203a 100%);
  color:var(--muted);
  display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;padding:20px;
}
.card{
  background:var(--card);
  padding:24px;border-radius:10px;width:480px;max-width:95%;
  box-shadow:0 6px 18px rgba(2,6,23,0.6);
}
h2{margin:0 0 12px;color:#fff}
.form-row{position:relative;margin-bottom:12px}
label{display:block;font-size:13px;color:#b8c6d9;margin-bottom:6px}
.input-el{
  width:100%;padding:10px 12px;border-radius:8px;border:1px solid rgba(255,255,255,0.06);
  background:var(--input-bg);color:#fff;font-size:15px;
}
.input-el:focus{outline:none;box-shadow:0 0 0 3px rgba(74,144,226,0.06);border-color:rgba(255,255,255,0.12)}
.check-icon{position:absolute;right:8px;top:36px;font-size:18px;width:28px;height:28px;display:flex;align-items:center;justify-content:center;pointer-events:none}
.msg {font-size:13px;height:16px;margin-top:6px;color:var(--err)}
.input-el.valid { border:2px solid rgba(76,175,80,0.25); }
.input-el.invalid { border:2px solid rgba(255,92,92,0.25); }

.button {width:100%;padding:11px;border-radius:8px;border:none;background:#2466d9;color:#fff;font-weight:600;font-size:15px;cursor:pointer}
.button:disabled {background:#314155;cursor:not-allowed;opacity:0.9}
.back-btn { display:inline-block;margin-top:12px;background:#3e4b7a;padding:10px;border-radius:8px;color:#fff;text-decoration:none;border:none;cursor:pointer }

.errorBox{background:rgba(255,92,92,0.12);color:#ffdede;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.successBox{background:rgba(76,175,80,0.12);color:#dfffe0;padding:10px;border-radius:6px;margin-bottom:10px;text-align:center}
.small-hint{font-size:12px;color:#98a6bd;margin-top:6px}
</style>
</head>
<body>
<div class="card">
  <h2>➕ Add Driver (Admin)</h2>

  <?php if ($error): ?><div class="errorBox"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if ($success): ?><div class="successBox"><?= htmlspecialchars($success) ?></div><?php endif; ?>

  <form id="driverForm" method="post" autocomplete="off" novalidate>
    <div class="form-row">
      <label for="full_name">Full Name</label>
      <input id="full_name" name="full_name" class="input-el" placeholder="Full name (this will be username)" required value="<?= isset($full_name) ? htmlspecialchars($full_name) : '' ?>">
      <div class="msg" id="fullNameMsg"></div>
    </div>

    <div class="form-row">
      <label for="email">Email</label>
      <input id="email" name="email" class="input-el" placeholder="you@example.com" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
      <div class="check-icon" id="emailCheck"></div>
      <div class="msg" id="emailMsg"></div>
    </div>

    <div class="form-row">
      <label for="password">Password</label>
      <input id="password" type="password" name="password" class="input-el" placeholder="Min 6 chars, include A and 1" required>
      <div class="check-icon" id="passwordCheck"></div>
      <div class="msg" id="passwordMsg"></div>
    </div>

    <div class="form-row">
      <label for="phone">Mobile Number (login)</label>
      <input id="phone" name="phone" class="input-el" placeholder="98765 43210" maxlength="13" required value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>">
      <div class="check-icon" id="phoneCheck"></div>
      <div class="msg" id="phoneMsg"></div>
      <div class="small-hint">Indian numbers only. Auto-formats as <code>XXXXX XXXXX</code></div>
    </div>

    <div class="form-row">
      <label for="licences_number">Driving Licence Number</label>
      <input id="licences_number" name="licences_number" class="input-el" placeholder="e.g. DL01 XXXXXXXXXXX" required value="<?= isset($licence) ? htmlspecialchars($licence) : '' ?>">
      <div class="check-icon" id="licenceCheck"></div>
      <div class="msg" id="licenceMsg"></div>
    </div>

    <div style="margin-top:10px;">
      <button type="submit" class="button" id="submitBtn" disabled>✅ Add Driver</button>
    </div>

    <div style="margin-top:10px;">
      <button type="button" class="back-btn" id="backBtn">⬅ Back to Manage Users</button>
    </div>
  </form>
</div>

<script>
function el(id){ return document.getElementById(id); }
function setCheck(id,state){ const node = el(id); if(!node) return; node.innerHTML = state === 'ok' ? '✅' : (state === 'taken' ? '❌' : ''); }
function setMsg(id,text){ const node = el(id); if(!node) return; node.textContent = text || ''; }
function addClassInput(input,ok){ if(!input) return; input.classList.remove('invalid','valid'); if(ok===true) input.classList.add('valid'); else if(ok===false) input.classList.add('invalid'); }
function checkServer(params, cb) {
  fetch('validate.php?' + new URLSearchParams(params), { cache: 'no-store' })
    .then(r => r.json())
    .then(cb)
    .catch(()=> cb({ status: 'ok' }));
}

const fld = {
  fullName: el('full_name'), fullNameMsg: el('fullNameMsg'),
  email: el('email'), emailCheck: el('emailCheck'), emailMsg: el('emailMsg'),
  password: el('password'), passwordCheck: el('passwordCheck'), passwordMsg: el('passwordMsg'),
  phone: el('phone'), phoneCheck: el('phoneCheck'), phoneMsg: el('phoneMsg'),
  licence: el('licences_number'), licenceCheck: el('licenceCheck'), licenceMsg: el('licenceMsg'),
  submit: el('submitBtn'), form: el('driverForm')
};

let V = { fullName:false, email:false, password:false, phone:false, licence:false };
function toggleSubmit(){ fld.submit.disabled = !(V.fullName && V.email && V.password && V.phone && V.licence); }
fld.fullName.addEventListener('input', () => {
  const v = fld.fullName.value.trim();
  if (!/^[A-Za-z ]{3,}$/.test(v)) {
    setMsg('fullNameMsg', 'Full name must be at least 3 letters (letters & spaces only)');
    addClassInput(fld.fullName, false);
    V.fullName = false;
  } else {
    setMsg('fullNameMsg','');
    addClassInput(fld.fullName, true);
    V.fullName = true;
  }
  toggleSubmit();
});
fld.email.addEventListener('input', () => {
  const v = fld.email.value.trim();
  if (!v) { setMsg('emailMsg','Email required'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  const gmailRegex = /^[A-Za-z0-9._%+-]+@gmail\.com$/i;
  const parts = v.split('@'); const local = parts[0] || '';
  if (!gmailRegex.test(v)) { setMsg('emailMsg','Use a valid Gmail address, e.g. user@gmail.com'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  if (/^\d+$/.test(local)) { setMsg('emailMsg','Local part cannot be only numbers'); setCheck('emailCheck',''); addClassInput(fld.email,false); V.email=false; toggleSubmit(); return; }
  setMsg('emailMsg','');
  checkServer({ type: 'email', value: v }, function(d){
    if (d && d.status === 'taken') { setMsg('emailMsg', 'Email already used'); setCheck('emailCheck','taken'); addClassInput(fld.email,false); V.email = false; }
    else { setMsg('emailMsg',''); setCheck('emailCheck','ok'); addClassInput(fld.email,true); V.email = true; }
    toggleSubmit();
  });
});
fld.password.addEventListener('input', () => {
  const v = fld.password.value;
  const ok = /^(?=.*[A-Z])(?=.*\d).{6,}$/.test(v);
  if (!ok) {
    setMsg('passwordMsg', 'Must include 1 uppercase & 1 number (min 6)');
    setCheck('passwordCheck','');
    addClassInput(fld.password,false);
    V.password = false;
  } else {
    setMsg('passwordMsg','');
    setCheck('passwordCheck','ok');
    addClassInput(fld.password,true);
    V.password = true;
  }
  toggleSubmit();
});
fld.phone.addEventListener('input', () => {
  let digits = fld.phone.value.replace(/\D/g,'').slice(0,10);
  fld.phone.value = digits.length > 5 ? digits.slice(0,5) + ' ' + digits.slice(5) : digits;
  setMsg('phoneMsg',''); setCheck('phoneCheck',''); addClassInput(fld.phone,null);
  V.phone = false; toggleSubmit();
  if (digits.length < 10) return;
  if (!/^[6-9]/.test(digits)) {
    setMsg('phoneMsg','Must start with digits 6-9');
    setCheck('phoneCheck','taken'); addClassInput(fld.phone,false);
    V.phone = false; toggleSubmit(); return;
  }
  checkServer({ type: 'phone', value: digits }, function(d){
    if (d && d.status === 'taken') {
      setMsg('phoneMsg', 'Phone already used');
      setCheck('phoneCheck','taken'); addClassInput(fld.phone,false);
      V.phone = false;
    } else {
      setMsg('phoneMsg','');
      setCheck('phoneCheck','ok'); addClassInput(fld.phone,true);
      V.phone = true;
    }
    toggleSubmit();
  });
});
fld.licence.addEventListener('input', () => {
  let v = fld.licence.value.trim().toUpperCase();
  fld.licence.value = v;
  if (!/^[A-Z]{2}[0-9]{2}[0-9A-Z]{11}$/.test(v)) {
    setMsg('licenceMsg','Invalid format (e.g. DL01XXXXXXXXXXX)');
    setCheck('licenceCheck','');
    addClassInput(fld.licence,false);
    V.licence = false; toggleSubmit(); return;
  }
  checkServer({ type: 'licences_number', value: v }, function(d){
    if (d && d.status === 'taken') {
      setMsg('licenceMsg','Licence already used');
      setCheck('licenceCheck','taken'); addClassInput(fld.licence,false); V.licence = false;
    } else {
      setMsg('licenceMsg','');
      setCheck('licenceCheck','ok'); addClassInput(fld.licence,true); V.licence = true;
    }
    toggleSubmit();
  });
});
fld.form.addEventListener('submit', function(e){
  if (!(V.fullName && V.email && V.password && V.phone && V.licence)) {
    e.preventDefault();
    return;
  }
  fld.phone.value = fld.phone.value.replace(/\D/g,'');
});

el('backBtn').addEventListener('click', ()=> { location.href = 'manage_users.php'; });
</script>
</body>
</html>
