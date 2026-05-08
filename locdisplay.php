<?php
include 'db_connect.php';

$bus_id = 1; 
$stops_query = $conn->query("SELECT * FROM stops WHERE bus_id=$bus_id ORDER BY stop_order ASC");
$stops = [];
while ($row = $stops_query->fetch_assoc()) {
    $stops[] = $row;
}
$total_stops = count($stops);
$status = $conn->query("SELECT * FROM bus_status WHERE bus_id=$bus_id")->fetch_assoc();
$current_stop = $status['current_stop'] ?? 0;
$current_status = $status['status'] ?? "Not Started";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bus Live Tracking</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f3f4f6;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }
        .container {
            width: 90%;
            max-width: 900px;
            background: #fff;
            margin-top: 30px;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            text-align: center;
        }
        h2 { margin-bottom: 10px; color: #333; }
        .status-bar {
            margin-bottom: 20px;
            padding: 10px;
            border-radius: 8px;
            background: #e3f2fd;
            font-weight: bold;
            color: #007bff;
        }
        .progress-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            margin: 40px 0 20px;
        }
        .progress-line {
            position: absolute;
            top: 18px;
            left: 0;
            width: 100%;
            height: 6px;
            background: #ddd;
            z-index: 1;
            border-radius: 3px;
        }
        .progress-fill {
            position: absolute;
            top: 18px;
            left: 0;
            height: 6px;
            background: #4CAF50;
            border-radius: 3px;
            z-index: 2;
            transition: width 1s ease-in-out;
        }
        .stop {
            position: relative;
            text-align: center;
            width: calc(100% / <?php echo max(1, $total_stops); ?>);
            z-index: 3;
        }
        .stop-circle {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: #ccc;
            margin: 0 auto;
            transition: background 0.5s ease;
            box-shadow: 0 0 5px rgba(0,0,0,0.2);
        }
        .completed .stop-circle { background: #4CAF50; }
        .current .stop-circle { background: #ff9800; animation: pulse 1s infinite; }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(1); opacity: 0.8; }
        }
        .stop-name {
            font-size: 14px;
            font-weight: bold;
            margin-top: 8px;
        }
        .eta {
            font-size: 12px;
            color: #555;
        }
        .bus-icon {
            position: absolute;
            top: -30px;
            width: 40px;
            height: 40px;
            transition: left 1s ease-in-out;
            z-index: 5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Bus Route Progress</h2>
        <div class="status-bar">
            Bus ID: <?php echo $bus_id; ?> | Status: <?php echo $current_status; ?>
        </div>
        <div class="progress-container">
            <div class="progress-line"></div>
            <div class="progress-fill" style="width: <?php echo (($current_stop - 1) / max(1, $total_stops - 1)) * 100; ?>%;"></div>
            <img src="bus_icon.png" alt="Bus" class="bus-icon" 
                 style="left: calc(<?php echo (($current_stop - 1) / max(1, $total_stops - 1)) * 100; ?>% - 20px);">
            
            <?php foreach ($stops as $stop): ?>
                <div class="stop 
                    <?php 
                        if ($stop['stop_id'] < $current_stop) echo 'completed';
                        elseif ($stop['stop_id'] == $current_stop) echo 'current';
                    ?>">
                    <div class="stop-circle"></div>
                    <div class="stop-name"><?php echo $stop['stop_name']; ?></div>
                    <div class="eta">ETA: <?php echo $stop['eta']; ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="status-bar">
            Last Updated: <?php echo $status['last_update'] ?? 'Not yet started'; ?>
        </div>
    </div>
</body>
</html>
