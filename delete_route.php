<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'admin') {
    header("Location: login.php");
    exit();
}
if (!isset($_GET['id'])) {
    die("No route specified.");
}

$route_id = intval($_GET['id']);

try {
    $mysqli->begin_transaction();

    $stmtStops = $mysqli->prepare("SELECT stop_id FROM stops WHERE route_id = ?");
    $stmtStops->bind_param("i", $route_id);
    $stmtStops->execute();
    $result = $stmtStops->get_result();
    $stop_ids = [];
    while ($row = $result->fetch_assoc()) {
        $stop_ids[] = $row['stop_id'];
    }
    $stmtStops->close();

    if (!empty($stop_ids)) {
        $stop_ids_str = implode(",", $stop_ids);

        // Only attempt to delete from `bus_loc` if the table exists (some installs may not have it)
        $tblCheck = $mysqli->query("SHOW TABLES LIKE 'bus_loc'");
        if ($tblCheck && $tblCheck->num_rows > 0) {
            $mysqli->query("DELETE FROM bus_loc WHERE current_stop_id IN ($stop_ids_str)");
        }

        $mysqli->query("DELETE FROM stops WHERE stop_id IN ($stop_ids_str)");
    }
    $stmtRoute = $mysqli->prepare("DELETE FROM routes WHERE route_id = ?");
    $stmtRoute->bind_param("i", $route_id);
    $stmtRoute->execute();
    $stmtRoute->close();
    $mysqli->commit();
    header("Location: manage_routes.php?msg=Route+deleted+successfully");
    exit();
} catch (Exception $e) {
    $mysqli->rollback();
    die("Error deleting route: " . $e->getMessage());
}
?>
