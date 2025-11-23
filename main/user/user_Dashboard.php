<?php 
session_start();
include("../../config/database.php"); 
include("../../template/header.php");
$user_id = $_SESSION['user_id'] ?? 0;

$stud_fname = "Student";
$course = "";
$year = "";
$stud_id = 0;

if ($user_id) {
    $stmt = $conn->prepare("
      SELECT stud_id, stud_fname, stud_course, stud_year 
      FROM tb_students 
      WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stud_id = $row['stud_id'];
        $stud_fname = $row['stud_fname'];
        $course = $row['stud_course'];
        $year = $row['stud_year'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mediko | User Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body { 
        background-color: #f8f9fa; 
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        margin: 0;
        flex-direction: column;
    }

    .user-info {
        text-align: center;
        margin-bottom: 50px;
    }
    .user-info h4 { 
        color: #0b1e4a; 
        font-weight: bold; 
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    .user-info p { 
        color: #333; 
        font-size: 1.2rem;
    }

    .icon-links {
        display: flex;
        gap: 30px;
        justify-content: center;
        flex-wrap: wrap;
    }

    .icon-links a {
        display: flex;
        flex-direction: column; 
        align-items: center;
        justify-content: center;
        background-color: #0b1e4a;
        color: #ffffff;
        text-decoration: none;
        padding: 25px 30px;
        border-radius: 12px;
        transition: all 0.3s;
        font-weight: bold;
        font-size: 1.2rem;
        min-width: 150px;
    }

    .icon-links a:hover {
        background-color: #143c91;
    }

    .icon-links i {
        font-size: 3rem; 
        margin-bottom: 12px; 
    }
  </style>
</head>
<body>
    <div class="user-info">
        <h4>Hi, <?= htmlspecialchars($stud_fname); ?>!</h4>
        <p><strong>Course & Year:</strong> <?= htmlspecialchars(($course && $year) ? "$course - $year" : "Not available"); ?></p>
    </div>
    <div class="icon-links">
        <a href="../user/user_profile.php">
            <i class="fa-solid fa-user"></i>
            <span>Profile</span>
            
        </a>
        <a href="../user/user_HealthRecord.php">
            <i class="fa-solid fa-notes-medical"></i>
            <span>Health Record</span>
        </a>
        <a href="../user/user_chatbot.php">
            <i class="fa-solid fa-robot"></i>
            <span>AI Chatbot</span>
        </a>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include("../../template/footer.php"); ?>
