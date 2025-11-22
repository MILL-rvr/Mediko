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
$available_users = $conn->query("SELECT user_id, user_username, user_email FROM tb_users WHERE user_role = 'Student' AND user_id NOT IN (SELECT user_id FROM tb_students)");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_user_id = intval($_POST['user_id']);
    $stud_lname = trim($_POST['stud_lname']);
    $stud_fname = trim($_POST['stud_fname']);
    $stud_mname = trim($_POST['stud_mname']);
    $stud_course = $_POST['stud_course'];
    $stud_year = $_POST['stud_year'];
    $stud_gender = $_POST['stud_gender'];
    $stud_age = trim($_POST['stud_age']);
    $stud_imageurl = "images/students/default_image.jpg"; 

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
        $new_filename = "student_new_" . time() . "." . $file_ext;
        $file_path = $upload_dir . $new_filename;

        if (!in_array($file_type, $allowed_types)) {
            $error = "Invalid file type. Only JPG, PNG, GIF, WebP allowed.";
        } elseif ($file_size > $max_size) {
            $error = "File too large. Max 2MB.";
        } elseif (move_uploaded_file($file_tmp, $file_path)) {
            $stud_imageurl = "../../images/students/" . $new_filename;
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

    if (empty($selected_user_id) || empty($stud_lname) || empty($stud_fname) || empty($stud_course) || empty($stud_year) || empty($stud_gender) || empty($stud_age)) {
        $error = "All required fields are required.";
    } elseif (isset($error)) {
    } else {
        $check = $conn->prepare("SELECT user_id FROM tb_students WHERE user_id = ?");
        $check->bind_param("i", $selected_user_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "This user has already been added as a student.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_students (user_id, stud_lname, stud_fname, stud_mname, stud_course, stud_year, stud_gender, stud_age, stud_imageurl) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $selected_user_id, $stud_lname, $stud_fname, $stud_mname, $stud_course, $stud_year, $stud_gender, $stud_age, $stud_imageurl);

            if ($stmt->execute()) {
                $new_stud_id = $conn->insert_id; 
                if ($stud_imageurl !== "images/students/default_image.jpg") {
                    $old_path = "../../" . $stud_imageurl;
                    $new_filename = "student_" . $new_stud_id . "_" . time() . "." . pathinfo($old_path, PATHINFO_EXTENSION);
                    $new_path = "../../images/students/" . $new_filename;
                    if (rename($old_path, $new_path)) {
                        $stud_imageurl = "../../images/students/" . $new_filename;
                        $conn->query("UPDATE tb_students SET stud_imageurl = '$stud_imageurl' WHERE stud_id = $new_stud_id");
                    }
                }
                if (!empty($record_height) || !empty($record_weight) || !empty($record_bmi) || !empty($record_bmiCategory) || !empty($record_healthissues)) {
                    $stmt_record = $conn->prepare("INSERT INTO tb_record (stud_id, record_height, record_weight, record_bmi, record_bmiCategory, record_healthissues) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_record->bind_param("isssss", $new_stud_id, $record_height, $record_weight, $record_bmi, $record_bmiCategory, $record_healthissues);
                    $stmt_record->execute();
                    $stmt_record->close();
                }

                $success = "Student added successfully.";
            } else {
                $error = "Error adding student: " . $stmt->error;
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
<title>Mediko | Admin Add Student</title>

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
    margin-left: 250px; 
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

@media (max-width: 768px) {
    .main-content {
        margin-left: 0; 
        padding-top: 100px; 
    }
    .sidebar {
        width: 200px; 
    }
}
</style>
</head>

<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">

    <div class="form-container">

        <h4 class="page-title">Add New Student</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="mb-3">
                <label class="form-label">Select User (Available Students):</label>
                <select name="user_id" class="form-select" required>
                    <?php while ($user = $available_users->fetch_assoc()): ?>
                        <option value="<?= $user['user_id'] ?>"><?= htmlspecialchars($user['user_username']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" name="stud_lname" class="form-control" placeholder="Enter last name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" name="stud_fname" class="form-control" placeholder="Enter first name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Middle Name:</label>
                <input type="text" name="stud_mname" class="form-control" placeholder="Enter middle name">
            </div>

            <div class="mb-3">
                <label class="form-label">Course:</label>
                <select name="stud_course" class="form-select" required>
                    <option value="BS IT">BS IT</option>
                    <option value="BS Architecture">BS Architecture</option>
                    <option value="BS CoE">BS CoE</option>
                    <option value="BS CE">BS CE</option>
                    <option value="BS EE">BS EE</option>
                    <option value="BS ME">BS ME</option>
                    <option value="BSED">BSED</option>
                    <option value="BEED">BEED</option>
                    <option value="BS Mathemathics">BS Mathemathics</option>
                    <option value="ABEL">ABEL</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Year:</label>
                <select name="stud_year" class="form-select" required>
                    <option value="">Select Year</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4</option>
                    <option value="5">5</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Gender:</label>
                <select name="stud_gender" class="form-select" required>
                    <option value="">Select Gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Age:</label>
                <input type="text" name="stud_age" class="form-control" placeholder="Enter age" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Image:</label>
                <input type="file" name="stud_image" class="form-control" accept="image/*">
                <small class="form-text text-muted">Upload an image (JPG, PNG, GIF, WebP, max 2MB). Leave blank for default.</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Height (cm):</label>
                <input type="number" step="0.01" id="heightCm" name="record_height" class="form-control" placeholder="Enter height">
            </div>
            <div class="mb-3">
                <a href="https://www.thecalculatorsite.com/conversions/common/height-converter.php" target="_blank">Height Converter</a>
            </div>
            <div class="mb-3">
                <label class="form-label">Weight (kg):</label>
                <input type="number" step="0.01" id="weight" name="record_weight" class="form-control" placeholder="Enter weight">
            </div>
            <div class="mb-3">
                <a href="https://www.thecalculatorsite.com/conversions/common/weight-converter.php" target="_blank">Weight Converter</a>
            </div>
            <div class="mb-3">
                <label class="form-label">BMI:</label>
                <input type="number" step="0.01" id="bmi" name="record_bmi" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">BMI Category:</label>
                <input type="text" id="bmiCategory" name="record_bmiCategory" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Health Issues:</label>
                <textarea name="record_healthissues" class="form-control" rows="3" placeholder="Enter health issues"></textarea>
            </div>
            <div class="btn-group-custom">
                <a href="admin_StudentsRecord.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Add Student</button>
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
</script>

</body>
</html>
