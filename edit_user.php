<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php'; 

function safe_post($k){
    return isset($_POST[$k]) ? trim($_POST[$k]) : null;
}

$uid = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($uid <= 0) {
    header("Location: manage_users.php?msg=invalid_id");
    exit();
}

$stmt = $mysqli->prepare("SELECT user_id, username, role FROM users WHERE user_id=?");
$stmt->bind_param("i", $uid);
$stmt->execute();
$user_res = $stmt->get_result();
if ($user_res->num_rows === 0) {
    $stmt->close();
    header("Location: manage_users.php?msg=user_not_found");
    exit();
}
$user = $user_res->fetch_assoc();
$stmt->close();

$role = $user['role'];
$role_data = [];
if ($role === 'student') {
    $stmt = $mysqli->prepare("SELECT * FROM students WHERE user_id=?");
} elseif ($role === 'parent') {
    $stmt = $mysqli->prepare("SELECT * FROM parents WHERE user_id=?");
} elseif ($role === 'driver') {
    $stmt = $mysqli->prepare("SELECT * FROM drivers WHERE user_id=?");
} else {
    $stmt = null;
}

if ($stmt) {
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $role_data = $res->fetch_assoc();
    }
    $stmt->close();
}

$linked_student_id = null;
$linked_student_name = '';
if($role === 'parent'){
    $stmt_student = $mysqli->prepare("
        SELECT s.student_id, s.full_name 
        FROM students s 
        WHERE s.parent_id = (SELECT parent_id FROM parents WHERE user_id=?)
    ");
    $stmt_student->bind_param("i", $uid);
    $stmt_student->execute();
    $res_student = $stmt_student->get_result();
    if ($res_student && $res_student->num_rows > 0) {
        $row = $res_student->fetch_assoc();
        $linked_student_id = $row['student_id'];
        $linked_student_name = $row['full_name'];
    }
    $stmt_student->close();
    $unassigned_students = [];
    $res_unassigned = $mysqli->query("SELECT student_id, full_name FROM students WHERE parent_id IS NULL");
    while ($row = $res_unassigned->fetch_assoc()) {
        $unassigned_students[] = $row;
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = safe_post('username');
    $new_password = safe_post('password');

    if (!$new_username) $errors[] = "Username cannot be empty.";

    if (empty($errors)) {
        $mysqli->begin_transaction();
        try {
            $stmt = $mysqli->prepare("UPDATE users SET username=? WHERE user_id=?");
            $stmt->bind_param("si", $new_username, $uid);
            $stmt->execute();
            $stmt->close();
            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET password=? WHERE user_id=?");
                $stmt->bind_param("si", $hashed, $uid);
                $stmt->execute();
                $stmt->close();
            }
            if ($role === 'student') {
                $full_name = safe_post('full_name');
                $email = safe_post('email');
                $phone = safe_post('phone');
                $student_unique_id = safe_post('student_unique_id');

                $stmt = $mysqli->prepare("UPDATE students SET full_name=?, email=?, phone=?, student_unique_id=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $full_name, $email, $phone, $student_unique_id, $uid);
                $stmt->execute();
                $stmt->close();

            } elseif ($role === 'parent') {
                $full_name = safe_post('full_name');
                $email = safe_post('email');
                $phone = safe_post('phone');
                $assigned_student = safe_post('student_id') ?? null;
                $stmt = $mysqli->prepare("UPDATE parents SET full_name=?, email=?, phone=? WHERE user_id=?");
                $stmt->bind_param("sssi", $full_name, $email, $phone, $uid);
                $stmt->execute();
                $stmt->close();
                if ($assigned_student) {
                    $stmt_clear = $mysqli->prepare("UPDATE students SET parent_id=NULL WHERE parent_id=?");
                    $stmt_clear->bind_param("i", $uid);
                    $stmt_clear->execute();
                    $stmt_clear->close();

                    $stmt_assign = $mysqli->prepare("UPDATE students SET parent_id=? WHERE student_id=?");
                    $stmt_assign->bind_param("ii", $uid, $assigned_student);
                    $stmt_assign->execute();
                    $stmt_assign->close();
                }

            } elseif ($role === 'driver') {
                $full_name = safe_post('full_name');
                $licences_number = safe_post('licences_number');
                $phone = safe_post('phone');

                $stmt = $mysqli->prepare("UPDATE drivers SET full_name=?, licences_number=?, phone=? WHERE user_id=?");
                $stmt->bind_param("sssi", $full_name, $licences_number, $phone, $uid);
                $stmt->execute();
                $stmt->close();
            }

            $mysqli->commit();
            header("Location: manage_users.php?msg=updated");
            exit();
        } catch (Exception $e) {
            $mysqli->rollback();
            $errors[] = "Update failed: " . $e->getMessage();
        }
    }
}
?>

<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Edit User #<?php echo $uid; ?></title>
<link rel="stylesheet" href="assets/css/style_subpages.css">
<style>
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#a1c4fd,#c2e9fb);padding:30px}
.card{max-width:800px;margin:0 auto;background:#fff;padding:22px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.12)}
label{display:block;margin-top:12px;font-weight:600}
input, select{width:100%;padding:10px;border-radius:8px;border:1px solid #ddd;margin-top:6px}
.btn{display:inline-block;padding:10px 14px;border-radius:8px;background:#1e90ff;color:#fff;text-decoration:none;border:none;margin-top:12px}
.danger{background:#dc3545}
.note{font-size:13px;color:#666;margin-top:6px}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
</style>
</head>
<body>
<div class="card">
<h2>Edit User — ID #<?php echo $uid; ?> (<?php echo htmlspecialchars($role); ?>)</h2>

<?php if(!empty($errors)): ?>
<div style="background:#ffe6e6;padding:10px;border-radius:8px;margin-bottom:12px;">
<?php foreach($errors as $er) echo "<div>".htmlspecialchars($er)."</div>"; ?>
</div>
<?php endif; ?>

<form method="post" novalidate>
<label>Username</label>
<input name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>

<label>Change Password (leave blank to keep existing)</label>
<input id="password" name="password" type="password" placeholder="New password (optional)">
<div id="passwordMsg" class="note" style="color:#c00;margin-top:6px;height:18px"></div>

<?php if($role==='student'): ?>
<h3>Student details</h3>
<div class="row">
<div>
<label>Full name</label>
<input name="full_name" value="<?php echo htmlspecialchars($role_data['full_name'] ?? ''); ?>">
</div>
<div>
<label>Email</label>
<input name="email" value="<?php echo htmlspecialchars($role_data['email'] ?? ''); ?>">
</div>
</div>
<label>Phone</label>
<input name="phone" value="<?php echo htmlspecialchars($role_data['phone'] ?? ''); ?>">

<label>Student Unique ID</label>
<input name="student_unique_id" value="<?php echo htmlspecialchars($role_data['student_unique_id'] ?? ''); ?>">

<?php elseif($role==='parent'): ?>
<h3>Parent details</h3>
<label>Full name</label>
<input name="full_name" value="<?php echo htmlspecialchars($role_data['full_name'] ?? ''); ?>">
<label>Email</label>
<input name="email" value="<?php echo htmlspecialchars($role_data['email'] ?? ''); ?>">
<label>Phone</label>
<input name="phone" value="<?php echo htmlspecialchars($role_data['phone'] ?? ''); ?>">

<label>Linked Student</label>
<select name="student_id">
    <?php if ($linked_student_id): ?>
        <option value="<?= $linked_student_id ?>" selected><?= htmlspecialchars($linked_student_name) ?> (Currently Linked)</option>
    <?php else: ?>
        <option value="">-- Select a student --</option>
    <?php endif; ?>
    <?php foreach ($unassigned_students as $stu): ?>
        <option value="<?= $stu['student_id'] ?>"><?= htmlspecialchars($stu['full_name']) ?></option>
    <?php endforeach; ?>
</select>

<?php elseif($role==='driver'): ?>
<h3>Driver details</h3>
<label>Full name</label>
<input name="full_name" value="<?php echo htmlspecialchars($role_data['full_name'] ?? ''); ?>" required>
<label>Licence number</label>
<input name="licences_number" value="<?php echo htmlspecialchars($role_data['licences_number'] ?? ''); ?>" required>
<label>Phone</label>
<input name="phone" value="<?php echo htmlspecialchars($role_data['phone'] ?? ''); ?>" required>
<?php endif; ?>

<div style="margin-top:14px">
<button class="btn" type="submit">Save changes</button>
<a class="btn danger" href="manage_users.php" style="text-decoration:none;">Cancel</a>
</div>
</form>
</div>
<script>
// Client-side password validation: optional, but if provided must include uppercase and number and be at least 6 chars
(function(){
    var pwd = document.getElementById('password');
    var pwdMsg = document.getElementById('passwordMsg');
    var form = document.querySelector('form');
    var pwdRegex = /^(?=.*[A-Z])(?=.*\d).{6,}$/;

    if (!pwd) return;

    function showInvalid(msg){
        pwd.style.borderColor = '#e55353';
        pwdMsg.textContent = msg;
    }
    function clearInvalid(){
        pwd.style.borderColor = '';
        pwdMsg.textContent = '';
    }

    pwd.addEventListener('input', function(){
        var v = pwd.value || '';
        if (!v) { clearInvalid(); return; }
        if (!pwdRegex.test(v)) {
            showInvalid('Must include an uppercase letter and a number (min 6 chars)');
        } else {
            clearInvalid();
        }
    });

    form.addEventListener('submit', function(e){
        var v = pwd.value || '';
        if (v && !pwdRegex.test(v)){
            e.preventDefault();
            showInvalid('Password invalid. Include uppercase and number, min 6 chars.');
            pwd.focus();
        }
    });
})();
</script>
</body>
</html>
