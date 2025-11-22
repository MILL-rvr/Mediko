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
<title>Mediko | User Profile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { 
    background-color: #f8f9fa; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
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
    margin-bottom: 40px;
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

            <a href="../user/user_EditProfile.php" class="btn btn-primary mt-3">
                <i class="fa fa-edit"></i> Edit Profile
            </a>
        </div>
    </div>

    <div class="card">
        <h5>Student Information</h5>
        <p><strong>Last Name:</strong> <?= htmlspecialchars($stud_lname); ?></p>
        <p><strong>First Name:</strong> <?= htmlspecialchars($stud_fname); ?></p>
        <p><strong>Middle Name:</strong> <?= htmlspecialchars($stud_mname); ?></p>
        <p><strong>Course:</strong> <?= htmlspecialchars($course); ?></p>
        <p><strong>Year Level:</strong> <?= htmlspecialchars($year); ?></p>
        <p><strong>Gender:</strong> <?= htmlspecialchars($stud_gender); ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($stud_age); ?></p>
    </div>

    <div class="card">
        <h5>User Account Information</h5>
        <p><strong>Username:</strong> <?= htmlspecialchars($user_username); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user_email); ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($user_role); ?></p>
    </div>

    <div class="card">
        <h5>Student Health Data</h5>
        <?php if (!empty($health_record)): ?>
            <p><strong>Height (cm):</strong> <?= htmlspecialchars($health_record['record_height']); ?></p>
            <p><strong>Weight (kg):</strong> <?= htmlspecialchars($health_record['record_weight']); ?></p>
            <p><strong>BMI:</strong> <?= htmlspecialchars($health_record['record_bmi']); ?></p>
            <p><strong>BMI Category:</strong> 
                <?php 
                    $category = $health_record['record_bmiCategory'] ?? '';
                    if (empty($category)) {
                        $bmi = floatval($health_record['record_bmi'] ?? 0);
                        if ($bmi > 0) {
                            if ($bmi < 18.5) $category = 'Underweight';
                            elseif ($bmi < 25) $category = 'Healthy Weight';
                            elseif ($bmi < 30) $category = 'Overweight';
                            else $category = 'Obesity';
                        }
                    }
                    echo htmlspecialchars($category);
                ?>
            </p>
            <p><strong>Health Issues:</strong> <?= htmlspecialchars($health_record['record_healthissues']); ?></p>
        <?php else: ?>
            <p>No health record available.</p>
        <?php endif; ?>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include("../../template/footer.php"); ?>