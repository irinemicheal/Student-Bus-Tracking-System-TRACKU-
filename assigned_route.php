<?php 
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'driver') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php';
$username = $_SESSION['username'];
$query = "SELECT d.driver_id 
          FROM drivers d
          JOIN users u ON d.user_id = u.user_id
          WHERE u.username = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$driver = $result->fetch_assoc();

$busRoute = [];
$stops = [];

if ($driver) {
    $driver_id = $driver['driver_id'];

    $query = "SELECT DISTINCT b.bus_number, r.route_id, r.route_name
              FROM assignments a
              JOIN buses b ON a.bus_id = b.bus_id
              JOIN routes r ON a.route_id = r.route_id
              WHERE a.driver_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $driver_id);
    $stmt->execute();
    $busRouteResult = $stmt->get_result();
    $busRoute = $busRouteResult->fetch_assoc();

    if ($busRoute) {
        $route_id = $busRoute['route_id'];
        $queryStops = "SELECT stop_name FROM stops WHERE route_id = ? ORDER BY stop_order ASC";
        $stmtStops = $mysqli->prepare($queryStops);
        $stmtStops->bind_param("i", $route_id);
        $stmtStops->execute();
        $resStops = $stmtStops->get_result();
        while ($row = $resStops->fetch_assoc()) {
            $stops[] = $row['stop_name'];
        }
    } else {
        $debugMsg = "⚠ No bus or route assigned to you yet.";
    }
} else {
    $debugMsg = "⚠ Driver record not found.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assigned Route | Driver Panel</title>
<style>
body { font-family: 'Poppins', sans-serif; background: #08b3e27d; margin:0; padding:0; display:flex; justify-content:center; align-items:flex-start; min-height:100vh; padding-top:30px;}
.main-content { background:#fff; padding:30px 40px; border-radius:15px; box-shadow:0 6px 20px rgba(0,0,0,0.2); max-width:700px; width:95%; text-align:center; }
h2 { color:#0d6efd; margin-bottom:25px; }
.info-card { background:#f4f8ff; border-left:6px solid #0d6efd; padding:15px; margin-bottom:15px; border-radius:10px; text-align:left; box-shadow:0 4px 10px rgba(0,0,0,0.1); }
.info-card strong { color:#0d6efd; }
.stops-list { text-align:left; margin-top:20px; }
.stop-item { background:#eef5ff; padding:10px; margin:8px 0; border-radius:6px; border-left:4px solid #0d6efd; }
.btn { display:inline-block; padding:10px 20px; margin-top:20px; background:#0d6efd; color:white; text-decoration:none; border-radius:8px; transition:0.3s; }
.btn:hover { background:#0a58ca; transform:scale(1.05); }
.debug { margin-top:20px; font-size:14px; color:red; }
</style>
</head>
<body>
<div class="main-content">
    <h2>🛣 Assigned Route Details</h2>

    <?php if (!empty($busRoute)) { ?>
        <div class="info-card">
            <p><strong>Bus No:</strong> <?= htmlspecialchars($busRoute['bus_number']) ?></p>
        </div>
        <div class="info-card">
            <p><strong>Route:</strong> <?= htmlspecialchars($busRoute['route_name']) ?></p>
        </div>

        <div class="stops-list">
            <h3>Stops:</h3>
            <?php if (!empty($stops)) { 
                foreach ($stops as $index => $stop) { ?>
                    <div class="stop-item">
                        <strong><?= ($index + 1) . ". " . htmlspecialchars($stop) ?></strong>
                    </div>
            <?php } } else { ?>
                <p>No stops assigned to this route.</p>
            <?php } ?>
        </div>

    <?php } else { ?>
        <p>No route assigned yet.</p>
    <?php } ?>

    <?php if (isset($debugMsg)) { ?>
        <div class="debug"><?= $debugMsg ?></div>
    <?php } ?>

    <a class="btn" href="driverdash.php">⬅ Back to Dashboard</a>
</div>
</body>
</html>
