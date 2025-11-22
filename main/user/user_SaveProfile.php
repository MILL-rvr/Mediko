<?php
session_start();
include("../../config/database.php");

$user_id = $_SESSION['user_id'] ?? 0;
if (!$user_id) {
    header("Location: ../login.php"); 
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stud_id = $_POST['stud_id'] ?? 0;
    $lname = $_POST['stud_lname'] ?? '';
    $fname = $_POST['stud_fname'] ?? '';
    $mname = $_POST['stud_mname'] ?? '';
    $course = $_POST['stud_course'] ?? '';
    $year = $_POST['stud_year'] ?? '';
    $gender = $_POST['stud_gender'] ?? '';
    $age = $_POST['stud_age'] ?? '';
    $email = $_POST['user_email'] ?? '';
    $height = $_POST['height_cm'] ?? 0;
    $weight = $_POST['record_weight'] ?? 0;
    $healthissues = $_POST['record_healthissues'] ?? '';

    $heightMeters = $height / 100;
    $bmi = $heightMeters > 0 ? round($weight / ($heightMeters ** 2), 2) : 0;

    if ($bmi < 18.5) $bmiCategory = 'Underweight';
    else if ($bmi < 25) $bmiCategory = 'Healthy Weight';
    else if ($bmi < 30) $bmiCategory = 'Overweight';
    else $bmiCategory = 'Obesity';

    $upload_dir = "../../images/students/";
    $stud_imageurl = null; 
    if (isset($_FILES['stud_image']) && $_FILES['stud_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['stud_image'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; 

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        if (!is_writable($upload_dir)) {
            $_SESSION['error'] = 'Upload directory is not writable.';
            header("Location: user_EditProfile.php");
            exit;
        }

        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = "user_{$user_id}_" . time() . "." . $extension;
            $full_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file['tmp_name'], $full_path)) {
                $stud_imageurl = "images/students/" . $new_filename; 
            } else {
                $_SESSION['error'] = 'Failed to upload image.';
                header("Location: user_EditProfile.php");
                exit;
            }
        } else {
            $_SESSION['error'] = 'Invalid image file. Please upload a JPG, PNG, GIF, or WebP under 2MB.';
            header("Location: user_EditProfile.php");
            exit;
        }
    }

    $update_image = $stud_imageurl ? ", stud_imageurl = ?" : "";
    $stmt = $conn->prepare("UPDATE tb_students SET stud_fname=?, stud_mname=?, stud_lname=?, stud_course=?, stud_year=?, stud_gender=?, stud_age=?" . $update_image . " WHERE stud_id=?");
    if ($stud_imageurl) {
        $stmt->bind_param("ssssssssi", $fname, $mname, $lname, $course, $year, $gender, $age, $stud_imageurl, $stud_id);
    } else {
        $stmt->bind_param("sssssssi", $fname, $mname, $lname, $course, $year, $gender, $age, $stud_id);
    }
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("UPDATE tb_users u INNER JOIN tb_students s ON u.user_id = s.user_id SET u.user_email=? WHERE s.stud_id=?");
    $stmt->bind_param("si", $email, $stud_id);
    $stmt->execute();
    $stmt->close();
    
    $stmt = $conn->prepare("
        INSERT INTO tb_record (stud_id, record_height, record_weight, record_bmi, record_bmiCategory, record_healthissues)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            record_height = VALUES(record_height),
            record_weight = VALUES(record_weight),
            record_bmi = VALUES(record_bmi),
            record_bmiCategory = VALUES(record_bmiCategory),
            record_healthissues = VALUES(record_healthissues)
    ");
    $stmt->bind_param("idddss", $stud_id, $height, $weight, $bmi, $bmiCategory, $healthissues);
    $stmt->execute();
    $stmt->close();

    header("Location: user_EditProfile.php?success=1");
    exit;
}
?>