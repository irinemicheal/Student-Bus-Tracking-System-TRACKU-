<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = trim($_POST['role']);

    $message = '';
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        $_SESSION['signup_name'] = $name;
        $_SESSION['signup_email'] = $email;
        $_SESSION['signup_password'] = $password;

        switch ($role) {
            case 'student':
                header("Location: signup_student.php");
                exit();
            case 'driver':
                header("Location: signup_driver.php");
                exit();
            case 'parent':
                header("Location: signup_parent.php");
                exit();
            case 'admin':
                header("Location: signup_admin.php");
                exit();
            default:
                $message = "Invalid role selected.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Bus Tracking - Signup</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('studentpic.jpg') no-repeat center center fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .signup-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 20px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        h2 { text-align: center; margin-bottom: 20px; }
        label { display: block; margin: 10px 0 5px; }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        button {
            background: #007BFF;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover { background: #0056b3; }
        .message { text-align: center; color: red; margin-bottom: 10px; }
        p { text-align: center; }
        a { color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="signup-container">
        <h2>Sign Up</h2>
        <?php if(isset($message) && $message != '') echo "<div class='message'>$message</div>"; ?>
        <form method="POST" action="">
            <label for="name">Full Name</label>
            <input type="text" name="name" placeholder="Enter your full name" required>

            <label for="email">Email</label>
            <input type="email" name="email" placeholder="Enter your email" required>

            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Enter password" required>

            <label for="confirm_password">Re-enter Password</label>
            <input type="password" name="confirm_password" placeholder="Re-enter password" required>

            <label for="role">Select Role</label>
            <select name="role" required>
                <option value="">--Select Role--</option>
                <option value="student">Student</option>
                <option value="parent">Parent</option>
                <option value="driver">Driver</option>
                <option value="admin">Admin</option>
            </select>

            <button type="submit">Next</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>
