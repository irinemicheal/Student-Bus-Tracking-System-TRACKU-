<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

if (isset($_GET['id'])) {
    $assignment_id = intval($_GET['id']);
    $assignment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM bus_assignments WHERE assignment_id='$assignment_id'"));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Assignment</title>
    <link rel="stylesheet" href="assets/css/style_subpages.css">
</head>
<body>
    <div class="container">
        <h2>✏ Edit Bus Assignment</h2>
        <a href="assign_buses.php" class="back-btn">⬅ Back</a>

        <form method="POST" action="update_assignment.php">
            <input type="hidden" name="assignment_id" value="<?php echo $assignment['assignment_id']; ?>">

            <label>Bus:</label>
            <select name="bus_id" required>
                <?php
                $buses = mysqli_query($conn, "SELECT * FROM buses");
                while ($bus = mysqli_fetch_assoc($buses)) {
                    $selected = ($bus['bus_id'] == $assignment['bus_id']) ? "selected" : "";
                    echo "<option value='{$bus['bus_id']}' $selected>{$bus['bus_number']}</option>";
                }
                ?>
            </select>

            <label>Driver:</label>
            <select name="driver_id" required>
                <?php
                $drivers = mysqli_query($conn, "SELECT * FROM drivers");
                while ($driver = mysqli_fetch_assoc($drivers)) {
                    $selected = ($driver['driver_id'] == $assignment['driver_id']) ? "selected" : "";
                    echo "<option value='{$driver['driver_id']}' $selected>{$driver['full_name']}</option>";
                }
                ?>
            </select>

            <button type="submit" class="btn">Update Assignment</button>
        </form>
    </div>
</body>
</html>
