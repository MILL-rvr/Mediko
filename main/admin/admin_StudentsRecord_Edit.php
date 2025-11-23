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
$edit_stud_id = $_GET['stud_id'] ?? 0;
$stmt = $conn->prepare("
    SELECT s.*, u.user_username, u.user_email, r.record_id, r.record_height, r.record_weight, r.record_bmi, r.record_bmiCategory, r.record_healthissues
    FROM tb_students s
    LEFT JOIN tb_users u ON s.user_id = u.user_id
    LEFT JOIN tb_record r ON s.stud_id = r.stud_id
    WHERE s.stud_id = ?
");
$stmt->bind_param("i", $edit_stud_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Student not found.");
}
$student = $result->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_username = trim($_POST['user_username']);
    $user_email = trim($_POST['user_email']);
    $stud_lname = trim($_POST['stud_lname']);
    $stud_fname = trim($_POST['stud_fname']);
    $stud_mname = trim($_POST['stud_mname']);
    $stud_course = $_POST['stud_course'];
    $stud_year = $_POST['stud_year'];
    $stud_gender = $_POST['stud_gender'];
    $stud_age = trim($_POST['stud_age']);
    $stud_imageurl = $student['stud_imageurl']; 
    $record_height = trim($_POST['record_height']);
    $record_weight = trim($_POST['record_weight']);
    $record_bmi = trim($_POST['record_bmi']);
    $record_bmiCategory = trim($_POST['record_bmiCategory']);
    $record_healthissues = trim($_POST['record_healthissues']);

    if (isset($_FILES['stud_image']) && $_FILES['stud_image']['error'] == UPLOAD_ERR_OK) {
        $upload_dir = "../../images/students/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; 
        $file_type = $_FILES['stud_image']['type'];
        $file_size = $_FILES['stud_image']['size'];
        $file_tmp = $_FILES['stud_image']['tmp_name'];
        $file_ext = strtolower(pathinfo($_FILES['stud_image']['name'], PATHINFO_EXTENSION));
        $new_filename = "student_" . $edit_stud_id . "_" . time() . "." . $file_ext;
        $file_path = $upload_dir . $new_filename;

        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, GIF, WebP allowed.";
        } elseif ($file_size > $max_size) {
            $error = "File too large. Max 2MB.";
        } elseif (move_uploaded_file($file_tmp, $file_path)) {
            $stud_imageurl = "images/students/" . $new_filename; 
        } else {
            $error = "Failed to upload image.";
        }
    }
    if (!empty($record_height) && !empty($record_weight)) {
        $height_m = $record_height / 100;
        $calculated_bmi = round($record_weight / ($height_m * $height_m), 2);
        if ($calculated_bmi < 18.5) $calculated_category = 'Underweight';
        elseif ($calculated_bmi < 25) $calculated_category = 'Healthy Weight';
        elseif ($calculated_bmi < 30) $calculated_category = 'Overweight';
        else $calculated_category = 'Obesity';
        $record_bmi = $calculated_bmi;
        $record_bmiCategory = $calculated_category;
    }

    if (empty($user_username) || empty($user_email) || empty($stud_lname) || empty($stud_fname) || empty($stud_course) || empty($stud_year) || empty($stud_gender) || empty($stud_age)) {
        $error = "All required fields are required.";
    } elseif (!filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (isset($error)) {
    } else {
        $check = $conn->prepare("SELECT user_id FROM tb_users WHERE (user_username = ? OR user_email = ?) AND user_id != ?");
        $check->bind_param("ssi", $user_username, $user_email, $student['user_id']);
        $check->execute();
        $dup = $check->get_result();

        if ($dup->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $stmt_user = $conn->prepare("UPDATE tb_users SET user_username=?, user_email=? WHERE user_id=?");
            $stmt_user->bind_param("ssi", $user_username, $user_email, $student['user_id']);
            $user_updated = $stmt_user->execute();
            $stmt_user->close();
            $stmt = $conn->prepare("
                UPDATE tb_students 
                SET stud_lname=?, stud_fname=?, stud_mname=?, stud_course=?, stud_year=?, stud_gender=?, stud_age=?, stud_imageurl=? 
                WHERE stud_id=?
            ");
            $stmt->bind_param("ssssssssi", $stud_lname, $stud_fname, $stud_mname, $stud_course, $stud_year, $stud_gender, $stud_age, $stud_imageurl, $edit_stud_id);

            if ($stmt->execute() && $user_updated) {
                if (!empty($record_height) || !empty($record_weight) || !empty($record_bmi) || !empty($record_bmiCategory) || !empty($record_healthissues)) {
                    if ($student['record_id']) {
                        $stmt_record = $conn->prepare("
                            UPDATE tb_record 
                            SET record_height=?, record_weight=?, record_bmi=?, record_bmiCategory=?, record_healthissues=? 
                            WHERE record_id=?
                        ");
                        $stmt_record->bind_param("sssssi", $record_height, $record_weight, $record_bmi, $record_bmiCategory, $record_healthissues, $student['record_id']);
                    } else {
                        $stmt_record = $conn->prepare("
                            INSERT INTO tb_record (stud_id, record_height, record_weight, record_bmi, record_bmiCategory, record_healthissues) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt_record->bind_param("isssss", $edit_stud_id, $record_height, $record_weight, $record_bmi, $record_bmiCategory, $record_healthissues);
                    }
                    if ($stmt_record->execute()) {
                        $success = "Student record updated successfully!";
                    } else {
                        $error = "Error updating health record: " . $stmt_record->error;
                    }
                    $stmt_record->close();
                } else {
                    $success = "Student record updated successfully!";
                }
            } else {
                $error = "Error updating student or user: " . $stmt->error;
            }
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
<title>Admin | Edit Student Record</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body {
    font-family: 'Segoe UI';
    background: #f8f9fa;
    margin: 0;
}

.sidebar {
    position: fixed;
    width: 250px;
    height: 100vh;
    left: 0;
    top: 0;
    background: rgba(11, 30, 74, 0.85);
    color: #fff;
    padding: 20px;
    overflow-y: auto;
}

.main-content {
    margin-left: 260px; 
    padding: 20px;
}

.form-container {
    max-width: 700px;
    margin: auto;
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
}

h4.page-title {
    color: #0b1e4a;
    font-weight: bold;
    font-size: 2.5rem;
    margin-bottom: 30px;
}

.btn-group-custom {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}
</style>
</head>

<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">

    <div class="form-container">

        <h4 class="page-title">Edit Student Record</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Username:</label>
                <input type="text" name="user_username" class="form-control" value="<?= htmlspecialchars($student['user_username']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($student['user_email']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" name="stud_lname" class="form-control" value="<?= htmlspecialchars($student['stud_lname']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" name="stud_fname" class="form-control" value="<?= htmlspecialchars($student['stud_fname']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Middle Name:</label>
                <input type="text" name="stud_mname" class="form-control" value="<?= htmlspecialchars($student['stud_mname']); ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Course:</label>
                <select name="stud_course" class="form-select" required>
                    <option value="BS IT" <?= $student['stud_course'] == 'BS IT' ? 'selected' : '' ?>>BS IT</option>
                    <option value="BS Architecture" <?= $student['stud_course'] == 'BS Architecture' ? 'selected' : '' ?>>BS Architecture</option>
                    <option value="BS CoE" <?= $student['stud_course'] == 'BS CoE' ? 'selected' : '' ?>>BS CoE</option>
                    <option value="BS CE" <?= $student['stud_course'] == 'BS CE' ? 'selected' : '' ?>>BS CE</option>
                    <option value="BS EE" <?= $student['stud_course'] == 'BS EE' ? 'selected' : '' ?>>BS EE</option>
                    <option value="BS ME" <?= $student['stud_course'] == 'BS ME' ? 'selected' : '' ?>>BS ME</option>
                    <option value="BSED" <?= $student['stud_course'] == 'BSED' ? 'selected' : '' ?>>BSED</option>
                    <option value="BEED" <?= $student['stud_course'] == 'BEED' ? 'selected' : '' ?>>BEED</option>
                    <option value="BS Mathemathics" <?= $student['stud_course'] == 'BS Mathemathics' ? 'selected' : '' ?>>BS Mathemathics</option>
                    <option value="ABEL" <?= $student['stud_course'] == 'ABEL' ? 'selected' : '' ?>>ABEL</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Year:</label>
                <select name="stud_year" class="form-select" required>
                    <option value="1" <?= $student['stud_year'] == '1' ? 'selected' : '' ?>>1</option>
                    <option value="2" <?= $student['stud_year'] == '2' ? 'selected' : '' ?>>2</option>
                    <option value="3" <?= $student['stud_year'] == '3' ? 'selected' : '' ?>>3</option>
                    <option value="4" <?= $student['stud_year'] == '4' ? 'selected' : '' ?>>4</option>
                    <option value="5" <?= $student['stud_year'] == '5' ? 'selected' : '' ?>>5</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Gender:</label>
                <select name="stud_gender" class="form-select" required>
                    <option value="Male" <?= $student['stud_gender'] == 'Male' ? 'selected' : '' ?>>Male</option>
                    <option value="Female" <?= $student['stud_gender'] == 'Female' ? 'selected' : '' ?>>Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Age:</label>
                <input type="text" name="stud_age" class="form-control" value="<?= htmlspecialchars($student['stud_age']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Image:</label>
                <input type="file" name="stud_image" class="form-control" accept="image/*">
                <small class="form-text text-muted">Upload a new image (JPG, PNG, GIF, WebP, max 2MB). Leave blank to keep current image.</small>
            </div>
            <div class="mb-3">
                <label class="form-label">Height (cm):</label>
                <input type="number" step="0.01" id="heightCm" name="record_height" class="form-control" value="<?= htmlspecialchars($student['record_height'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <a href="https://www.thecalculatorsite.com/conversions/common/height-converter.php" target="_blank">Height Converter</a>
            </div>
            <div class="mb-3">
                <label class="form-label">Weight (kg):</label>
                <input type="number" step="0.01" id="weight" name="record_weight" class="form-control" value="<?= htmlspecialchars($student['record_weight'] ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <a href="https://www.thecalculatorsite.com/conversions/common/weight-converter.php" target="_blank">Weight Converter</a>
            </div>
            <div class="mb-3">
                <label class="form-label">BMI:</label>
                <input type="number" step="0.01" id="bmi" name="record_bmi" class="form-control" value="<?= htmlspecialchars($student['record_bmi'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">BMI Category:</label>
                <input type="text" id="bmiCategory" name="record_bmiCategory" class="form-control" value="<?= htmlspecialchars($student['record_bmiCategory'] ?? ''); ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Health Issues:</label>
                <textarea name="record_healthissues" class="form-control" rows="3"><?= htmlspecialchars($student['record_healthissues'] ?? ''); ?></textarea>
            </div>

            <div class="btn-group-custom">
                <a href="admin_StudentsRecord.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const heightCmInput = document.getElementById('heightCm');
const weightInput = document.getElementById('weight');
const bmiInput = document.getElementById('bmi');
const bmiCategoryInput = document.getElementById('bmiCategory');

function calculateBMI() {
    const heightCm = parseFloat(heightCmInput.value) || 0;
    const weight = parseFloat(weightInput.value) || 0;

    if (heightCm > 0 && weight > 0) {
        const heightMeters = heightCm / 100;
        const bmi = (weight / (heightMeters ** 2)).toFixed(2);
        bmiInput.value = bmi;

        let category = '';
        if (bmi < 18.5) category = 'Underweight';
        else if (bmi < 25) category = 'Healthy Weight';
        else if (bmi < 30) category = 'Overweight';
        else category = 'Obesity';

        bmiCategoryInput.value = category;
    } else {
        bmiInput.value = '';
        bmiCategoryInput.value = '';
    }
}

heightCmInput.addEventListener('input', calculateBMI);
weightInput.addEventListener('input', calculateBMI);
calculateBMI();
</script>

</body>
</html>
