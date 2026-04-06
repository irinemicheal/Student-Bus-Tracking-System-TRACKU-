<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Routes | Student Bus Tracking</title>
<link rel="stylesheet" href="assets/css/admin_style.css">
<style>
    body {
        font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
        margin: 0;
        padding: 0;
        color: #333;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #2c3e50;
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

    .navbar .back-btn:hover { background: #0096d6; }

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
        color: #2c3e50;
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
        border-color: #2ebf91;
        box-shadow: 0 0 6px #2ebf9180;
    }

    .form-box .btn {
        width: 100%;
        background: #2c3e50;
        color: #fff;
        padding: 12px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s;
        margin-top: 8px;
    }

    .form-box .btn:hover { background: #1a252f; }

    h3 {
        margin-bottom: 15px;
        color: #2c3e50;
        border-left: 4px solid #2ebf91;
        padding-left: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 10px;
        overflow: hidden;
    }

    table thead { background: #2c3e50; color: #fff; }

    table th, table td {
        text-align: center;
        padding: 12px;
        border-bottom: 1px solid #ddd;
    }

    table tbody tr:nth-child(even) { background: #f9f9f9; }

    .edit-btn, .delete-btn {
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 14px;
        margin: 2px;
        transition: 0.3s;
        display: inline-block;
    }

    .edit-btn { background: #00c851; color: #fff; }
    .edit-btn:hover { background: #007e33; }
    .delete-btn { background: #ff4444; color: #fff; }
    .delete-btn:hover { background: #cc0000; }

    /* Scrollable Stops */
    #stops-container-wrapper {
        max-height: 200px;
        overflow-y: auto;
        padding-right: 5px;
        border: 1px solid #ccc;
        border-radius: 6px;
        margin-bottom: 8px;
    }

    #stops-container-wrapper::-webkit-scrollbar { width: 6px; }
    #stops-container-wrapper::-webkit-scrollbar-thumb {
        background-color: #2c3e50;
        border-radius: 3px;
    }

    #stops-container input { margin-top: 5px; }
</style>
</head>
<body>
    <div class="navbar">
        <h2>Manage Routes</h2>
        <a href="admin.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>

    <div class="container">
        <form method="POST" action="add_route.php" class="form-box">
            <h3>➕ Add New Route</h3>
            <input type="text" name="route_name" placeholder="Route Name" required>
            <input type="text" name="start_point" placeholder="Start Point" required>
            <input type="text" name="end_point" placeholder="End Point" required>

            <div id="stops-container-wrapper">
                <div id="stops-container">
                    <input type="text" name="stops[]" placeholder="Stop 1" required>
                </div>
            </div>
            <button type="button" class="btn" id="add-stop-btn" onclick="addStop()">➕ Add Another Stop</button>
            <button type="submit" class="btn">Add Route</button>
        </form>

        <h3>🛣 Available Routes</h3>
        <table>
            <thead>
                <tr>
                    <th>Route ID</th>
                    <th>Route Name</th>
                    <th>Start Point</th>
                    <th>End Point</th>
                    <th>Stops</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM routes";
                $result = mysqli_query($mysqli, $query);
                while ($row = mysqli_fetch_assoc($result)) {
                    $route_id = $row['route_id'];
                    $stops_result = mysqli_query($mysqli, "SELECT stop_name FROM stops WHERE route_id = $route_id ORDER BY stop_id ASC");
                    $stops_array = [];
                    while ($stop_row = mysqli_fetch_assoc($stops_result)) {
                        $stops_array[] = $stop_row['stop_name'];
                    }
                    $stops_list = implode(", ", $stops_array);

                    echo "<tr>
                            <td>{$row['route_id']}</td>
                            <td>{$row['route_name']}</td>
                            <td>{$row['start_point']}</td>
                            <td>{$row['end_point']}</td>
                            <td>{$stops_list}</td>
                            <td>
                                <a href='edit_route.php?id={$row['route_id']}' class='edit-btn'>✏ Edit</a>
                                <a href='delete_route.php?id={$row['route_id']}' class='delete-btn' onclick=\"return confirm('Are you sure you want to delete this route and all its stops?');\">🗑 Delete</a>
                            </td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

<script>
let stopCount = 1;
function addStop() {
    stopCount++;
    const container = document.getElementById('stops-container');
    const input = document.createElement('input');
    input.type = 'text';
    input.name = 'stops[]';
    input.placeholder = 'Stop ' + stopCount;
    input.required = true;
    container.appendChild(input);
    const wrapper = document.getElementById('stops-container-wrapper');
    wrapper.scrollTop = wrapper.scrollHeight;
}
</script>
</body>
</html>
