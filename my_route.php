<?php 
session_start();
include 'db.php';


$user_id = $_SESSION['user_id'] ?? null;
$routeData = null;
$stops = [];

if ($user_id) {

    $query = "
        SELECT 
            b.bus_number,
            r.route_id,
            r.route_name,
            d.full_name AS driver_name,
            d.phone AS driver_phone,
            st.pickup_stop_id,
            st.drop_stop_id
        FROM students st
        LEFT JOIN assignments a ON st.student_id = a.student_id
        LEFT JOIN buses b ON a.bus_id = b.bus_id
        LEFT JOIN routes r ON a.route_id = r.route_id
        LEFT JOIN drivers d ON a.driver_id = d.driver_id
        WHERE st.user_id = ?
        LIMIT 1
    ";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $routeData = $result->fetch_assoc();
    $stmt->close();

    if ($routeData && $routeData['route_id']) {
        $stmt2 = $mysqli->prepare("SELECT stop_id, stop_name, stop_order FROM stops WHERE route_id=? ORDER BY stop_order ASC");
        $stmt2->bind_param("i", $routeData['route_id']);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        while ($row = $res2->fetch_assoc()) {
            $stops[] = $row;
        }
        $stmt2->close();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $pickup_stop_id = $_POST['pickup_stop'] ?? '';
    $drop_stop_id = $_POST['drop_stop'] ?? '';

    if ($pickup_stop_id === 'school_college') $pickup_stop_id = NULL;
    if ($drop_stop_id === 'school_college') $drop_stop_id = NULL;

    $update = $mysqli->prepare("UPDATE students SET pickup_stop_id=?, drop_stop_id=? WHERE user_id=?");
    $update->bind_param("iii", $pickup_stop_id, $drop_stop_id, $user_id);
    $update->execute();
    $update->close();
    header("Location: my_route.php?success=1");
    exit();
}

$stopNames = [];
foreach ($stops as $s) {
    $stopNames[$s['stop_id']] = $s['stop_name'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Route</title>
    <style>
        body { font-family: 'Poppins', sans-serif; text-align: center; background: #19c0d661; margin: 0; padding: 0; }
        .container { margin: 60px auto; max-width: 600px; background: white; padding: 30px; border-radius: 15px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        h2 { color: #0d6efd; margin-bottom: 20px; }
        .details { text-align: left; margin-top: 20px; }
        .details p { font-size: 16px; margin: 12px 0; }
        .label { font-weight: bold; color: #0a58ca; }
        select { width: 100%; padding: 10px; margin-top: 6px; border-radius: 8px; border: 1px solid #ccc; }
        button.submit-btn { margin-top: 20px; padding: 12px 25px; border:none; border-radius: 8px; background:#0d6efd; color:white; font-weight:bold; cursor:pointer; transition:0.3s; }
        button.submit-btn:hover { background:#0a58ca; transform:scale(1.05); }
        a.back { display: inline-block; margin-top: 30px; text-decoration: none; padding: 12px 25px; background: #0d6efd; color: white; font-weight: bold; border-radius: 8px; transition: 0.3s; }
        a.back:hover { background: #0a58ca; transform: scale(1.05); }
        .no-data { color: red; font-size: 18px; margin-top: 20px; }
        .success { color: green; margin-top: 15px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <h2>🚍 My Assigned Route</h2>

    <?php if (isset($_GET['success'])): ?>
        <p class="success">✅ Stops updated successfully!</p>
    <?php endif; ?>

    <?php if ($routeData): ?>
        <div class="details">
            <p><span class="label">Bus Number:</span> <?= htmlspecialchars($routeData['bus_number'] ?? 'Not Assigned'); ?></p>
            <p><span class="label">Route:</span> <?= htmlspecialchars($routeData['route_name'] ?? 'Not Assigned'); ?></p>
            <p><span class="label">Driver:</span> <?= htmlspecialchars($routeData['driver_name'] ?? 'Not Assigned'); ?> (📞 <?= htmlspecialchars($routeData['driver_phone'] ?? 'N/A'); ?>)</p>
            
            <?php if ($routeData['pickup_stop_id'] || $routeData['drop_stop_id']): ?>
                <p><span class="label">Assigned Stops:</span>
                    <?= htmlspecialchars($stopNames[$routeData['pickup_stop_id']] ?? 'School / College') ?>
                    ➜
                    <?= htmlspecialchars($stopNames[$routeData['drop_stop_id']] ?? 'School / College') ?>
                </p>
            <?php endif; ?>
        </div>

        <form method="post" id="stopsForm">
            <p class="label">Select Pickup Stop:</p>
            <select name="pickup_stop" id="pickupSelect" required>
               
                <?php foreach($stops as $stop): ?>
                    <option value="<?= $stop['stop_id'] ?>" data-order="<?= $stop['stop_order'] ?>" <?= ($stop['stop_id'] == $routeData['pickup_stop_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($stop['stop_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <p class="label">Select Drop Stop:</p>
            <select name="drop_stop" id="dropSelect" required>
                <?php foreach($stops as $stop): ?>
                    <option value="<?= $stop['stop_id'] ?>" data-order="<?= $stop['stop_order'] ?>" <?= ($stop['stop_id'] == $routeData['drop_stop_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($stop['stop_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="submit-btn">Save Stops</button>
        </form>

    <?php else: ?>
        <p class="no-data">⚠️ No route assigned yet. Please contact admin.</p>
    <?php endif; ?>

    <a class="back" href="studentdash.php">⬅ Back to Dashboard</a>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var pickupSelect = document.getElementById('pickupSelect');
    var dropSelect = document.getElementById('dropSelect');

    function enableAllStops() {
        if (pickupSelect) {
            Array.from(pickupSelect.options).forEach(opt => opt.disabled = false);
        }
        if (dropSelect) {
            Array.from(dropSelect.options).forEach(opt => opt.disabled = false);
        }
    }

    enableAllStops();

    if (pickupSelect) {
        pickupSelect.addEventListener('change', enableAllStops);
    }
    if (dropSelect) {
        dropSelect.addEventListener('change', enableAllStops);
    }
});
</script>

</body>
</html>
