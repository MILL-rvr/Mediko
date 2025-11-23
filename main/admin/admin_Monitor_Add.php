<?php
session_start();
include("../../config/database.php");

$user_id = $_SESSION['user_id'] ?? 0;
$user_role = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT user_role FROM tb_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $user_role = $row['user_role'];
    }
}

if ($user_role !== 'Admin') {
    header("Location: index.php");
    exit;
}

$students = $conn->query("
    SELECT s.stud_id, CONCAT(s.stud_fname, ' ', s.stud_lname) AS full_name, s.stud_course, s.stud_year, u.user_username
    FROM tb_students s
    INNER JOIN tb_users u ON s.user_id = u.user_id
    LEFT JOIN tb_monitor m ON s.stud_id = m.stud_id
    WHERE m.monitor_id IS NULL
    ORDER BY u.user_username ASC
");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stud_id = intval($_POST['stud_id']);
    $monitor_datechecked = trim($_POST['monitor_datechecked']);
    $monitor_temperature = trim($_POST['monitor_temperature']);
    $monitor_symptoms = trim($_POST['monitor_symptoms']);
    $monitor_remarks = $_POST['monitor_remarks'];

    if (empty($stud_id) || empty($monitor_datechecked) || empty($monitor_temperature) || empty($monitor_remarks)) {
        $error = "Please fill in all required fields.";
    } else {
        $check = $conn->prepare("SELECT monitor_id FROM tb_monitor WHERE stud_id = ? AND monitor_datechecked = ?");
        $check->bind_param("is", $stud_id, $monitor_datechecked);
        
        if ($check->execute()) {
            $res_check = $check->get_result();
            if ($res_check->num_rows > 0) {
                $error = "Monitor record for this student at the selected date/time already exists!";
            } else {
                $stmt = $conn->prepare("INSERT INTO tb_monitor (stud_id, monitor_datechecked, monitor_temperature, monitor_symptoms, monitor_remarks) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $stud_id, $monitor_datechecked, $monitor_temperature, $monitor_symptoms, $monitor_remarks);
                if ($stmt->execute()) {
                    $success = "Monitor record added successfully!";
                } else {
                    $error = "Error adding monitor record: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            $error = "Error checking existing monitor records: " . $check->error;
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin | Add Monitor Record</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { font-family: 'Segoe UI'; background: #f8f9fa; margin: 0; }
.sidebar { position: fixed; width: 250px; height: 100vh; left: 0; top: 0; background: rgba(11, 30, 74, 0.85); color: #fff; padding: 20px; overflow-y: auto; }
.main-content { margin-left: 250px; padding: 20px; }
.form-container { max-width: 700px; margin: auto; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0px 4px 20px rgba(0,0,0,0.1); }
h4.page-title { color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px; }
.btn-group-custom { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; }
@media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 100px; } .sidebar { width: 200px; } }
</style>
</head>
<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">

    <div class="form-container">

        <h4 class="page-title">Add Monitor Record</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Filter by Course:</label>
                <select id="courseFilter" class="form-select">
                    <option value="">-- All Courses --</option>
                    <?php
                    $courses = $conn->query("SELECT DISTINCT stud_course FROM tb_students ORDER BY stud_course ASC");
                    while($c = $courses->fetch_assoc()):
                    ?>
                        <option value="<?= htmlspecialchars($c['stud_course']) ?>"><?= htmlspecialchars($c['stud_course']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Select Student:</label>
                <select id="studentSelect" name="stud_id" class="form-select" required>
                    <option value="">-- Select Student --</option>
                    <?php
                    while($student = $students->fetch_assoc()):
                    ?>
                        <option value="<?= $student['stud_id'] ?>" data-course="<?= htmlspecialchars($student['stud_course']) ?>">
                            <?= htmlspecialchars($student['user_username']) ?> - <?= htmlspecialchars($student['full_name']) ?> (<?= htmlspecialchars($student['stud_course']) ?>, Year <?= htmlspecialchars($student['stud_year']) ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Date Checked:</label>
                <input type="datetime-local" name="monitor_datechecked" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Temperature (°C):</label>
                <input type="number" step="0.1" name="monitor_temperature" class="form-control" placeholder="Enter temperature" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Symptoms:</label>
                <textarea name="monitor_symptoms" class="form-control" rows="3" placeholder="Enter symptoms"></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Remarks:</label>
                <select name="monitor_remarks" class="form-select" required>
                    <option value="">-- Select Remarks --</option>
                    <option value="Good">Good</option>
                    <option value="Sick">Sick</option>
                    <option value="Recovering">Recovering</option>
                    <option value="Critical">Critical</option>
                </select>
            </div>

            <div class="btn-group-custom">
                <a href="../admin/admin_Monitor.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Add Monitor Record</button>
            </div>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const courseFilter = document.getElementById('courseFilter');
const studentSelect = document.getElementById('studentSelect');

courseFilter.addEventListener('change', function() {
    const selectedCourse = this.value;
    const options = studentSelect.querySelectorAll('option');

    options.forEach(option => {
        if(option.value === "") return; 
        if(selectedCourse === "" || option.dataset.course === selectedCourse) {
            option.style.display = '';
        } else {
            option.style.display = 'none';
        }
    });
});
</script>

</body>
</html>
