<?php 
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'parent') {
    header("Location: login.php");
    exit();
}
$parent_user_id = $_SESSION['user_id'];

$stmt = $mysqli->prepare("SELECT parent_id, full_name FROM parents WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $parent_user_id);
$stmt->execute();
$stmt->bind_result($parent_id, $parent_name);
$stmt->fetch();
$stmt->close();

if (!$parent_id) {
    die("Parent not found. Please login again.");
}

$stmt = $mysqli->prepare("
    SELECT student_id, full_name AS student_name
    FROM students
    WHERE parent_id = ?
    ORDER BY full_name ASC
");
$stmt->bind_param("i", $parent_id);
$stmt->execute();
$students_result = $stmt->get_result();
$students = [];
while($row = $students_result->fetch_assoc()){
    $students[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Trip History | Parent</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body { font-family: 'Poppins', sans-serif; margin: 0; background: #19c0d661; display: flex; flex-direction: column; min-height: 100vh; }
.header { background: #1a73e8; color: white; padding: 20px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
.header h2 { font-size: 24px; margin: 0; }
.logout-btn { background: #ff4757; padding: 10px 18px; color: white; text-decoration: none; border-radius: 6px; font-size: 15px; transition: 0.3s; }
.logout-btn:hover { background: #e84118; }
.container { flex: 1; max-width: 1000px; margin: 40px auto; padding: 0 20px; }
.card { background: white; border-radius: 12px; padding: 30px; box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 40px; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
table th, table td { padding: 12px; border: 1px solid #ddd; text-align: center; }
table th { background: #f5f5f5; color: #0d6efd; }
table td { color: #333; }
.back-btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #1a73e8; color: white; text-decoration: none; border-radius: 8px; font-weight: 500; transition: 0.3s; }
.back-btn:hover { background: #0d6efd; }
footer { text-align: center; padding: 15px; background: #1a73e8; color: white; font-size: 14px; margin-top: auto; }
.no-students { text-align: center; color: #ff4757; font-weight: 500; padding: 50px 0; font-size: 18px; }
</style>
</head>
<body>

<div class="container">
<?php if(count($students) > 0): ?>
    <?php foreach($students as $student): ?>
        <?php
        $student_id = $student['student_id'];

        $stmt = $mysqli->prepare("
            SELECT a.assignment_id, a.bus_id, a.route_id, a.driver_id,
                   b.bus_number, r.route_name, u.username AS driver_name
            FROM assignments a
            LEFT JOIN buses b ON a.bus_id = b.bus_id
            LEFT JOIN routes r ON a.route_id = r.route_id
            LEFT JOIN drivers d ON a.driver_id = d.driver_id
            LEFT JOIN users u ON d.user_id = u.user_id
            WHERE a.student_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $assignment_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $bus_id = $assignment_result['bus_id'] ?? 0;
        $trips = [];
        if($bus_id){
            $stmt = $mysqli->prepare("SELECT trip_date, start_time, end_time, status FROM trips WHERE bus_id = ? ORDER BY trip_date DESC");
            $stmt->bind_param("i", $bus_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while($t = $res->fetch_assoc()){
                $trips[] = $t;
            }
            $stmt->close();
        }
        ?>
        <div class="card">
            <h2>
                <i class="fas fa-history"></i> <?php echo htmlspecialchars($student['student_name']); ?>
                <?php if($bus_id): ?>
                    | Bus: <?php echo htmlspecialchars($assignment_result['bus_number']); ?>
                    | Route: <?php echo htmlspecialchars($assignment_result['route_name']); ?>
                    | Driver: <?php echo htmlspecialchars($assignment_result['driver_name']); ?>
                <?php else: ?>
                    | No Assignment
                <?php endif; ?>
            </h2>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($trips) > 0): ?>
                        <?php foreach($trips as $trip): ?>
                        <tr>
                            <td><?php echo $trip['trip_date']; ?></td>
                            <td><?php echo $trip['start_time']; ?></td>
                            <td><?php echo $trip['end_time']; ?></td>
                            <td><?php echo ucfirst($trip['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No trips found for this bus.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="no-students">No students are linked to your account yet. Please contact admin.</div>
<?php endif; ?>

<a href="parentdash.php" class="back-btn">⬅ Back to Dashboard</a>
</div>

<footer>
    &copy; <?php echo date("Y"); ?> Student Bus Tracking System | All Rights Reserved
</footer>

</body>
</html>
