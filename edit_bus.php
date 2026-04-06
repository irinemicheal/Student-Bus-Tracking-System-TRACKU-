<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
include 'db.php';

$bus_id = $_GET['id'];
$query = "SELECT * FROM buses WHERE bus_id=$bus_id";
$result = mysqli_query($mysqli, $query);
$bus = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bus_number = mysqli_real_escape_string($mysqli, $_POST['bus_number']);
    $capacity = (int)$_POST['capacity'];

    $update = "UPDATE buses SET bus_number='$bus_number', capacity='$capacity' WHERE bus_id=$bus_id";
    if (mysqli_query($mysqli, $update)) {
        header("Location: manage_buses.php?msg=Bus Updated Successfully");
        exit();
    } else {
        echo "Error: " . mysqli_error($mysqli);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Bus - TRACKU</title>
<style>
    /* Reset */
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Arial', sans-serif; background-color: #f5f6fa; color: #333; }
    .container { max-width: 500px; margin: 80px auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
    h2 { text-align: center; margin-bottom: 30px; color: #0072ff; }
    form { display: flex; flex-direction: column; gap: 20px; }
    input[type="text"], input[type="number"] { padding: 12px 15px; font-size: 1rem; border: 1px solid #ccc; border-radius: 6px; }
    input[type="text"]:focus, input[type="number"]:focus { outline: none; border-color: #0072ff; box-shadow: 0 0 5px rgba(0,114,255,0.3); }

    .btn {
        background-color: #0072ff;
        color: #fff;
        padding: 12px 20px;
        font-size: 1rem;
        font-weight: bold;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .btn:hover { background-color: #005fcc; }

    .back-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 10px 15px;
        font-size: 0.95rem;
        color: #0072ff;
        text-decoration: none;
        border: 1px solid #0072ff;
        border-radius: 6px;
        transition: all 0.3s ease;
    }
    .back-btn:hover {
        background-color: #0072ff;
        color: #fff;
    }
    .back-btn svg { margin-right: 5px; }

</style>
</head>
<body>
    <div class="container">
        <h2>Edit Bus</h2>
        <form method="POST">
            <input type="text" name="bus_number" value="<?php echo htmlspecialchars($bus['bus_number']); ?>" placeholder="Bus Number" required>
            <input type="number" name="capacity" value="<?php echo htmlspecialchars($bus['capacity']); ?>" placeholder="Capacity" required>
            <button type="submit" class="btn">Update Bus</button>
            <a href="manage_buses.php" class="back-btn">
                <!-- Back arrow symbol -->
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 1-.5.5H2.707l4.147 4.146a.5.5 0 0 1-.708.708l-5-5a.5.5 0 0 1 0-.708l5-5a.5.5 0 1 1 .708.708L2.707 7.5H14.5A.5.5 0 0 1 15 8z"/>
                </svg>
                Back
            </a>
        </form>
    </div>
</body>
</html>
