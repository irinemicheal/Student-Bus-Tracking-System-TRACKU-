<?php

session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';

if (!isset($_GET['id'])) {
    header("Location: manage_routes.php");
    exit();
}
$route_id = intval($_GET['id']);
$routeQuery = "SELECT * FROM routes WHERE route_id=$route_id";
$routeResult = mysqli_query($mysqli, $routeQuery);
$route = mysqli_fetch_assoc($routeResult);
$stopsQuery = "SELECT * FROM stops WHERE route_id=$route_id ORDER BY stop_order ASC";
$stopsResult = mysqli_query($mysqli, $stopsQuery);
$stops = [];
while ($row = mysqli_fetch_assoc($stopsResult)) {
    $stops[] = $row['stop_name'];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $route_name = mysqli_real_escape_string($mysqli, $_POST['route_name']);
    $stops_post = $_POST['stops']; 
    $updateRoute = $mysqli->prepare("UPDATE routes SET route_name=? WHERE route_id=?");
    $updateRoute->bind_param("si", $route_name, $route_id);
    $updateRoute->execute();
    $updateRoute->close();

    $deleteStops = $mysqli->prepare("DELETE FROM stops WHERE route_id=?");
    $deleteStops->bind_param("i", $route_id);
    $deleteStops->execute();
    $deleteStops->close();
    $order = 1;
    $insertStop = $mysqli->prepare("INSERT INTO stops (route_id, stop_name, stop_order) VALUES (?, ?, ?)");
    foreach ($stops_post as $stop_name) {
        $stop_name = trim($stop_name);
        if ($stop_name !== "") {
            $insertStop->bind_param("isi", $route_id, $stop_name, $order);
            $insertStop->execute();
            $order++;
        }
    }
    $insertStop->close();

    header("Location: manage_routes.php?msg=Route+Updated+Successfully");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Route | Student Bus Tracking</title>
<style>
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
    margin: 0;
    padding: 0;
    color: #333;
}
.container {
    max-width: 500px;
    margin: 80px auto;
    background: #fff;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}
h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 25px;
    border-left: 4px solid #2ebf91;
    padding-left: 10px;
}
form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
label {
    font-weight: bold;
    margin-bottom: 4px;
}
input[type="text"] {
    padding: 10px 12px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 6px;
}
input[type="text"]:focus {
    outline: none;
    border-color: #2ebf91;
    box-shadow: 0 0 6px #2ebf9180;
}
.btn {
    background-color: #2c3e50;
    color: #fff;
    padding: 12px 20px;
    font-size: 1rem;
    font-weight: bold;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.btn:hover { background-color: #1a252f; }
.back-btn {
    display: inline-flex;
    align-items: center;
    padding: 10px 15px;
    font-size: 0.95rem;
    color: #2c3e50;
    text-decoration: none;
    border: 1px solid #2c3e50;
    border-radius: 6px;
    transition: all 0.3s ease;
}
.back-btn:hover {
    background-color: #2c3e50;
    color: #fff;
}
.back-btn svg { margin-right: 5px; width: 16px; height: 16px; }

.stop-input {
    display: flex;
    gap: 8px;
    align-items: center;
}
.stop-input input[type="text"] {
    flex: 1;
}
.remove-btn {
    background: #ff4444;
    border: none;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    padding: 0 12px;
    transition: 0.3s;
}
.remove-btn:hover { background: #cc0000; }
#add-stop-btn { background: #2ebf91; margin-top: 5px; }
#add-stop-btn:hover { background: #27a07a; }
</style>
</head>
<body>
<div class="container">
    <h2>Edit Route</h2>
    <form method="POST">
        <label for="route_name">Route Name</label>
        <input type="text" id="route_name" name="route_name" value="<?php echo htmlspecialchars($route['route_name']); ?>" placeholder="Route Name" required>

        <div id="stops-container">
            <?php foreach ($stops as $index => $stop): ?>
                <div class="stop-input">
                    <label>Stop <?php echo $index + 1; ?></label>
                    <input type="text" name="stops[]" value="<?php echo htmlspecialchars($stop); ?>" placeholder="Stop <?php echo $index + 1; ?>" required>
                    <button type="button" class="remove-btn" onclick="removeStop(this)">✖</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="btn" id="add-stop-btn" onclick="addStop()">➕ Add Another Stop</button>

        <button type="submit" class="btn">Update Route</button>

        <a href="manage_routes.php" class="back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H2.707l4.147 4.146a.5.5 0 0 1-.708.708l-5-5a.5.5 0 0 1 0-.708l5-5a.5.5 0 1 1 .708.708L2.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
            </svg>
            Back
        </a>
    </form>
</div>

<script>
let stopCount = <?php echo count($stops); ?>;

function addStop() {
    stopCount++;
    const container = document.getElementById('stops-container');
    const div = document.createElement('div');
    div.className = 'stop-input';
    div.innerHTML = `
        <label>Stop ${stopCount}</label>
        <input type="text" name="stops[]" placeholder="Stop ${stopCount}" required>
        <button type="button" class="remove-btn" onclick="removeStop(this)">✖</button>
    `;
    container.appendChild(div);
}

function removeStop(btn) {
    const div = btn.parentNode;
    div.remove();
}
</script>
</body>
</html>
