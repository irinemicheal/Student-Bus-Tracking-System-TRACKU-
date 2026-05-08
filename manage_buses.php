<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db_connect.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Buses | Student Bus Tracking</title>
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(to right, #4facfe, #00f2fe);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #003366;
            color: #fff;
            padding: 15px 25px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .navbar h2 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1px;
        }

        .navbar .back-btn {
            text-decoration: none;
            color: #fff;
            background: #00c6ff;
            padding: 8px 15px;
            border-radius: 5px;
            transition: 0.3s;
        }

        .navbar .back-btn:hover {
            background: #0096d6;
        }

        .container {
            max-width: 1100px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .form-box {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .form-box h3 {
            margin-bottom: 15px;
            color: #003366;
        }

        .form-box input {
            width: 100%;
            padding: 10px;
            margin-bottom: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            outline: none;
            transition: 0.3s;
        }

        .form-box input:focus {
            border-color: #00c6ff;
            box-shadow: 0 0 6px #00c6ff80;
        }

        .form-box .btn {
            width: 100%;
            background: #003366;
            color: #fff;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }

        .form-box .btn:hover {
            background: #00509e;
        }

        h3 {
            margin-bottom: 15px;
            color: #003366;
            border-left: 4px solid #00c6ff;
            padding-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
        }

        table thead {
            background: #003366;
            color: #fff;
        }

        table th, table td {
            text-align: center;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .edit-btn, .delete-btn {
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            margin: 2px;
            transition: 0.3s;
        }

        .edit-btn {
            background: #00c851;
            color: #fff;
        }

        .edit-btn:hover {
            background: #007e33;
        }

        .delete-btn {
            background: #ff4444;
            color: #fff;
        }

        .delete-btn:hover {
            background: #cc0000;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h2>Manage Buses</h2>
        <a href="admin.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>

    <div class="container">
        <form method="POST" action="add_bus.php" class="form-box">
            <h3>➕ Add New Bus</h3>
            <input type="text" name="bus_number" placeholder="Bus Number" required>
            <input type="text" name="capacity" placeholder="Capacity" required>
            <button type="submit" class="btn">Add Bus</button>
        </form>

        <h3>🚌 Available Buses</h3>
        <table>
            <thead>
                <tr>
                    <th>Bus ID</th>
                    <th>Bus Number</th>
                    <th>Capacity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM buses";
                $result = mysqli_query($mysqli, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<tr>
                            <td>{$row['bus_id']}</td>
                            <td>{$row['bus_number']}</td>
                            <td>{$row['capacity']}</td>
                            <td>
                                <a href='edit_bus.php?id={$row['bus_id']}' class='edit-btn'>✏ Edit</a>
                                <a href='delete_bus.php?id={$row['bus_id']}' class='delete-btn'>🗑 Delete</a>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

