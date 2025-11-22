<?php
session_start();
include("../../config/database.php");

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT user_role FROM tb_users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_role = $row['user_role'];
    }
}

if ($user_role !== 'Admin') {
    header("Location: index.php");
    exit;
}

$monitor_id = $_GET['monitor_id'] ?? 0;
$stmt = $conn->prepare("
    SELECT m.*, s.stud_fname, s.stud_mname, s.stud_lname, s.stud_course, s.stud_year, u.user_username
    FROM tb_monitor m
    LEFT JOIN tb_students s ON m.stud_id = s.stud_id
    LEFT JOIN tb_users u ON s.user_id = u.user_id
    WHERE m.monitor_id=?
");
$stmt->bind_param("i", $monitor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Monitor record not found.");
}
$monitor = $result->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monitor_datechecked = $_POST['monitor_datechecked'];
    $monitor_temperature = trim($_POST['monitor_temperature']);
    $monitor_symptoms = trim($_POST['monitor_symptoms']);
    $monitor_remarks = $_POST['monitor_remarks'];

    $stmt = $conn->prepare("
        UPDATE tb_monitor SET monitor_datechecked=?, monitor_temperature=?, monitor_symptoms=?, monitor_remarks=? 
        WHERE monitor_id=?
    ");
    $stmt->bind_param("ssssi", $monitor_datechecked, $monitor_temperature, $monitor_symptoms, $monitor_remarks, $monitor_id);
    if ($stmt->execute()) {
        $success = "Monitor record updated successfully!";
        $stmt2 = $conn->prepare("
            SELECT m.*, s.stud_fname, s.stud_mname, s.stud_lname, s.stud_course, s.stud_year, u.user_username
            FROM tb_monitor m
            LEFT JOIN tb_students s ON m.stud_id = s.stud_id
            LEFT JOIN tb_users u ON s.user_id = u.user_id
            WHERE m.monitor_id=?
        ");
        $stmt2->bind_param("i", $monitor_id);
        $stmt2->execute();
        $monitor = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
    } else {
        $error = "Error updating record: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | Edit Monitor Record</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI'; background: #f8f9fa; margin: 0; }
.sidebar { position: fixed; width: 250px; height: 100vh; left: 0; top: 0; background: rgba(11,30,74,0.85); color: #fff; padding:20px; overflow-y:auto; }
.main-content { margin-left: 260px; padding: 20px; }
.form-container { max-width: 800px; margin:auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
h4.page-title { color:#0b1e4a; font-weight:bold; font-size:2.5rem; margin-bottom:30px; }
.btn-group-custom { display:flex; justify-content:flex-end; gap:10px; margin-top:15px; }
</style>
</head>
<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">
    <div class="form-container">
        <h4 class="page-title">Edit Monitor Record</h4>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">No.</label><input type="text" class="form-control" value="<?= $monitor['monitor_id'] ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Username: </label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($monitor['user_username'] ?? '-') ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Student Name: </label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($monitor['stud_fname'].' '.$monitor['stud_mname'].' '.$monitor['stud_lname']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Course - Year: </label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($monitor['stud_course'].' - '.$monitor['stud_year']) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Date Checked: </label>
                <input type="datetime-local" name="monitor_datechecked" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($monitor['monitor_datechecked'])) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Temperature (°C): </label>
                <input type="text" name="monitor_temperature" class="form-control" value="<?= htmlspecialchars($monitor['monitor_temperature']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Symptoms/Findings: </label>
                <textarea name="monitor_symptoms" class="form-control" rows="3"><?= htmlspecialchars($monitor['monitor_symptoms']) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Remarks</label>
                <select name="monitor_remarks" class="form-select" required>
                    <option value="Good" <?= $monitor['monitor_remarks']=='Good'?'selected':'' ?>>Good</option>
                    <option value="Sick" <?= $monitor['monitor_remarks']=='Sick'?'selected':'' ?>>Sick</option>
                    <option value="Recovering" <?= $monitor['monitor_remarks']=='Recovering'?'selected':'' ?>>Recovering</option>
                    <option value="Critical" <?= $monitor['monitor_remarks']=='Critical'?'selected':'' ?>>Critical</option>
                </select>
            </div>

            <div class="btn-group-custom">
                <a href="../admin/admin_Monitor.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
