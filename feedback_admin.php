<?php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';

mysqli_query($mysqli, "UPDATE feedback SET status='Unmarked' WHERE status IS NULL OR status=''");

$query = "
    SELECT 
        f.feedback_id, f.feedback, f.rating, f.status, f.created_at,
        -- prefer linked student fields; if missing, show '-'
        COALESCE(s.full_name, '-') AS student_name,
        COALESCE(s.roll_number, '-') AS roll_number,
        COALESCE(s.class, '-') AS class,
        -- prefer linked driver name; if missing, show '-'
        COALESCE(d.full_name, '-') AS driver_name
    FROM feedback f
    LEFT JOIN students s ON f.student_id = s.student_id
    LEFT JOIN drivers d ON f.driver_id = d.driver_id
    ORDER BY f.created_at DESC
";

$result = mysqli_query($mysqli, $query);
if (!$result) {
    $err = mysqli_error($mysqli);
    echo "<div style='color:#b00;background:#fff3f3;padding:12px;border-radius:6px;margin:20px;'>Query error: " . htmlspecialchars($err) . "</div>";
    $result = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Feedback Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
body {
    font-family: 'Poppins', sans-serif;
    background: #19c0d661;
    margin: 0;
    padding: 20px;
}
.container {
    max-width: 1150px;
    margin: auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 4px 25px rgba(0,0,0,0.1);
}
h2 {
    color: #0d6efd;
    text-align: center;
    margin-bottom: 25px;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 12px;
    text-align: left;
}
th {
    background: #007bff;
    color: #fff;
    font-size: 14px;
}
tr:nth-child(even) { background: #f5f8ff; }
tr:hover { background: #e1f0ff; }

.rating i {
    font-size: 16px;
    margin-right: 2px;
    color: #ffc107;
}

.status-select {
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: bold;
    border: 1px solid #ccc;
    cursor: pointer;
}

.status-Mark { background: #28a745; color: white; }
.status-Unmarked { background: #ffc107; color: #212529; }

.back-btn {
    display: block;
    margin: 25px auto 0;
    width: max-content;
    padding: 10px 18px;
    background: #1a73e8;
    color: white;
    text-decoration: none;
    border-radius: 6px;
}
.back-btn:hover { background: #0d6efd; }
</style>
</head>
<body>
<div class="container">
<h2><i class="fa-solid fa-comments"></i> Student Feedback & Ratings</h2>

<table>
<thead>
<tr>
    <th>Student</th>
    <th>Roll No</th>
    <th>Class</th>
    <th>Driver</th>
    <th>Feedback</th>
    <th>Rating</th>
    <th>Status</th>
    <th>Submitted At</th>
</tr>
</thead>
<tbody>

<?php while($row = mysqli_fetch_assoc($result)): ?>
<tr>
    <td><?= htmlspecialchars($row['student_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['roll_number'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['class'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['driver_name'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['feedback']) ?></td>
    <td class="rating">
        <?php 
            $rating = intval($row['rating'] ?? 0);
            for ($i=1; $i<=5; $i++) {
                echo $i <= $rating 
                    ? "<i class='fa-solid fa-star'></i>"
                    : "<i class='fa-regular fa-star'></i>";
            }
        ?>
    </td>
    <td>
        <select class="status-select" onchange="updateStatus(<?= $row['feedback_id'] ?>, this)">
            <option value="Unmarked" <?= $row['status'] === 'Unmarked' ? 'selected' : '' ?>>Unmarked</option>
            <option value="Marked" <?= $row['status'] === 'Marked' ? 'selected' : '' ?>>Marked</option>
        </select>
    </td>

    <td><?= date("d M Y, h:i A", strtotime($row['created_at'])) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>

<a href="admin.php" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
</div>

<script>
function updateStatus(feedbackId, selectElement) {
    let newStatus = selectElement.value;
    let formData = new FormData();
    formData.append("feedback_id", feedbackId);
    formData.append("status", newStatus);

    fetch("update_feedback_status.php", {
        method: "POST",
        body: formData
    })
    .then(resp => resp.json())
    .then(data => {
        if(data.success) {
            showToast("✅ Status updated to " + newStatus);
        } else {
            alert("❌ Failed to update status");
        }
    });
}

function showToast(msg) {
    let toast = document.createElement("div");
    toast.innerText = msg;
    toast.style.position = "fixed";
    toast.style.bottom = "20px";
    toast.style.right = "20px";
    toast.style.background = "#28a745";
    toast.style.color = "#fff";
    toast.style.padding = "10px 18px";
    toast.style.borderRadius = "6px";
    toast.style.fontWeight = "bold";
    toast.style.boxShadow = "0 2px 10px rgba(0,0,0,0.2)";
    toast.style.zIndex = "9999";
    document.body.appendChild(toast);

    setTimeout(() => toast.remove(), 2000);
}
</script>
</body>
</html>
