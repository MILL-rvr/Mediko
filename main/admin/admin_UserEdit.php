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

$edit_id = $_GET['user_id'] ?? 0;
$stmt = $conn->prepare("SELECT * FROM tb_users WHERE user_id = ?");
$stmt->bind_param("i", $edit_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("User not found.");
}
$user = $result->fetch_assoc();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email.";
    } else {
        $check = $conn->prepare(
            "SELECT user_id FROM tb_users WHERE (user_username = ? OR user_email = ?) AND user_id != ?"
        );
        $check->bind_param("ssi", $username, $email, $edit_id);
        $check->execute();
        $dup = $check->get_result();

        if ($dup->num_rows > 0) {
            $error = "Username or Email already exists.";
        } else {
            $stmt = $conn->prepare(
                "UPDATE tb_users SET user_username=?, user_email=?, user_password=?, user_role=? WHERE user_id=?"
            );
            $stmt->bind_param("ssssi", $username, $email, $password, $role, $edit_id);

            if ($stmt->execute()) {
                $success = "User updated successfully!";
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
<title>Admin | Edit User</title>

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

        <h4 class="page-title">Edit User</h4>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="mb-3">
                <label class="form-label">Username:</label>
                <input type="text" name="username" class="form-control"
                       value="<?= htmlspecialchars($user['user_username']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email:</label>
                <input type="email" name="email" class="form-control"
                       value="<?= htmlspecialchars($user['user_email']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password:</label>
                <input type="text" name="password" class="form-control"
                       value="<?= htmlspecialchars($user['user_password']); ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Role:</label>
                <select name="role" class="form-select" required>
                    <option value="Admin" <?= $user['user_role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
                    <option value="Student" <?= $user['user_role'] == 'Student' ? 'selected' : '' ?>>Student</option>
                </select>
            </div>

            <div class="btn-group-custom">
                <a href="../admin/admin_Users.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>

        </form>

    </div>

</div>

</body>
</html>