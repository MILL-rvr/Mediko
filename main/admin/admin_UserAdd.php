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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        $check = $conn->prepare("SELECT user_id FROM tb_users WHERE user_username = ? OR user_email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tb_users (user_username, user_email, user_password, user_role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $password, $role);

            if ($stmt->execute()) {
                $success = "User added successfully.";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin Add User</title>

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

        <h4 class="page-title">Add New User</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Username: </label>
                <input type="text" name="username" class="form-control" placeholder="Enter username" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email: </label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password: </label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Role: </label>
                <select name="role" class="form-select" required>
                    <option value="Student">Student</option>
                    <option value="Admin">Admin</option>
                </select>
            </div>
            <div class="btn-group-custom" make this right side>
                <a href="../admin/admin_Users.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Add User</button>
            </div>

        </form>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
