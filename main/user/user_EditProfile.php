<?php
session_start();
include("../../config/database.php"); 
include("../../template/header.php");
$user_id = $_SESSION['user_id'] ?? 0;

$default_image = "../../images/students/default_image.jpg"; 
$stud_imageurl = $default_image;
$stud_id = $stud_fname = $stud_mname = $stud_lname = "";
$course = $year = $stud_gender = $stud_age = "";
$user_username = $user_email = $user_role = "";
$health_record = [];

if ($user_id) {
    $stmt = $conn->prepare("
        SELECT 
            s.stud_id, s.stud_fname, s.stud_mname, s.stud_lname,
            s.stud_course, s.stud_year, s.stud_gender, s.stud_age, s.stud_imageurl,
            u.user_username, u.user_email, u.user_role
        FROM tb_students AS s
        INNER JOIN tb_users AS u ON s.user_id = u.user_id
        WHERE s.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        $stud_id = $row['stud_id'];
        $stud_fname = $row['stud_fname'];
        $stud_mname = $row['stud_mname'];
        $stud_lname = $row['stud_lname'];

        $course = $row['stud_course'];
        $year = $row['stud_year'];
        $stud_gender = $row['stud_gender'];
        $stud_age = $row['stud_age'];

        $user_username = $row['user_username'];
        $user_email = $row['user_email'];
        $user_role = $row['user_role'];

        if (!empty($row['stud_imageurl'])) {
            $path = $row['stud_imageurl'];
            if (strpos($path, "../../") !== 0) {
                $path = "../../" . ltrim($path, "/");
            }
            $stud_imageurl = $path;
        }
    }
    $stmt->close();
    $stmt = $conn->prepare("
        SELECT record_height, record_weight, record_bmi, record_bmiCategory, record_healthissues
        FROM tb_record
        WHERE stud_id = ?
        ORDER BY record_id DESC LIMIT 1
    ");
    $stmt->bind_param("i", $stud_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $health_record = $result->fetch_assoc();
    }
    $stmt->close();
}

$image_to_show = htmlspecialchars($stud_imageurl);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Edit User Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { 
        background-color: #f8f9fa; 
        font-family: 'Segoe UI'; 
        min-height: 100vh;
        padding: 20px 0;
    }
    .profile-container {
        max-width: 900px;
        margin: 80px auto 0;
        padding: 0 20px;
        padding-bottom: 120px; 
    }
    .user-info {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 20px;
    }
    .user-info img {
        width: 120px; 
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        border: 3px solid #0b1e4a;
    }
    .user-info h4 { 
        color: #0b1e4a; 
        font-weight: bold; 
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    .card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        margin-top: 20px;
    }
    .card h5 {
        margin-bottom: 20px;
    }
</style>
</head>

<body>

<div class="profile-container">
    <div class="user-info">
        <img src="<?= $image_to_show; ?>" alt="Student Image" 
             onerror="this.src='<?= htmlspecialchars($default_image); ?>';">
        <div>
            <h4><?= htmlspecialchars("$stud_fname $stud_mname $stud_lname"); ?></h4>
            <p><strong>Username:</strong> <?= htmlspecialchars($user_username); ?></p>
        </div>
    </div>

    <?php if(isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Profile and health data saved successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <form action="user_SaveProfile.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="stud_id" value="<?= htmlspecialchars($stud_id); ?>">

    <div class="card">
        <h5>Profile</h5>
        <div class="mb-3">
            <label>Profile Image:</label>
            <input type="file" name="stud_image" class="form-control" accept="image/*">
            <small class="form-text text-muted">Upload a new image (JPG, PNG, GIF, WebP, max 2MB). Leave blank to keep current image.</small>
        </div>
        <div class="mb-3">
            <label>First Name:</label>
            <input type="text" name="stud_fname" class="form-control" value="<?= htmlspecialchars($stud_fname); ?>" required>
        </div>
        <div class="mb-3">
            <label>Middle Name:</label>
            <input type="text" name="stud_mname" class="form-control" value="<?= htmlspecialchars($stud_mname); ?>" required>
        </div>
        <div class="mb-3">
            <label>Last Name:</label>
            <input type="text" name="stud_lname" class="form-control" value="<?= htmlspecialchars($stud_lname); ?>" required>
        </div>
  
        <div class="mb-3">
            <label>Course:</label>
            <select name="stud_course" class="form-control" required>
                <?php 
                $courses = ['BS IT','BS Architecture','BS CoE','BS CE','BS EE','BS ME','BSED','BEED','BS Mathemathics','ABEL'];
                foreach ($courses as $c) {
                    $selected = ($c == $course) ? 'selected' : '';
                    echo "<option value=\"$c\" $selected>$c</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Year Level:</label>
            <select name="stud_year" class="form-control" required>
                <?php 
                for ($i=1; $i<=5; $i++) {
                    $selected = ($i == $year) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Gender:</label>
            <select name="stud_gender" class="form-control" required>
                <?php 
                $genders = ['Male','Female'];
                foreach ($genders as $g) {
                    $selected = ($g == $stud_gender) ? 'selected' : '';
                    echo "<option value=\"$g\" $selected>$g</option>";
                }
                ?>
            </select>
        </div>
        <div class="mb-3">
            <label>Age:</label>
            <input type="number" name="stud_age" class="form-control" value="<?= htmlspecialchars($stud_age); ?>" required>
        </div>
        <div class="mb-3">
            <label>Email:</label>
            <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($user_email); ?>" required>
        </div>
    </div>

    <div class="card">
        <h5>Health Data</h5>
        <div class="mb-3">
            <label>Height (cm):</label>
            <input type="number" step="0.01" id="heightCm" name="height_cm" class="form-control" value="<?= htmlspecialchars($health_record['record_height'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <a href="https://www.thecalculatorsite.com/conversions/common/height-converter.php" target="_blank">Height Converter</a>
        </div>
        <div class="mb-3">
            <label>Weight (kg):</label>
            <input type="number" step="0.01" id="weight" name="record_weight" class="form-control" value="<?= htmlspecialchars($health_record['record_weight'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <a href="https://www.thecalculatorsite.com/conversions/common/weight-converter.php" target="_blank">Weight Converter</a>
        </div>
        <div class="mb-3">
            <label>BMI:</label>
            <input type="number" step="0.01" id="bmi" name="record_bmi" class="form-control" value="<?= htmlspecialchars($health_record['record_bmi'] ?? ''); ?>" readonly>
        </div>
        <div class="mb-3">
            <label>BMI Category:</label>
            <input type="text" id="bmiCategory" name="record_bmiCategory" class="form-control" value="<?= htmlspecialchars($health_record['record_bmiCategory'] ?? ''); ?>" readonly>
        </div>
        <div class="mb-3">
            <label>Health Issues:</label>
            <textarea name="record_healthissues" class="form-control"><?= htmlspecialchars($health_record['record_healthissues'] ?? ''); ?></textarea>
        </div>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-success"><i class="fa fa-save"></i> Save Changes</button>
    </div>

    </form>
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

<?php include("../../template/footer.php"); ?>
