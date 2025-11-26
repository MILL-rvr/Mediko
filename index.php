<?php
session_start();
include("config/database.php"); 
$msg_user = '';
$msg_pass = '';
$msg_general = '';
$username = '';
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username)) $msg_user = "Please enter your username.";
    if (empty($password)) $msg_pass = "Please enter your password.";

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT user_id, user_password, user_role FROM tb_users WHERE user_username=?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['user_password'] === $password) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $user['user_role'];
                header("Location: " . ($user['user_role'] === 'Admin'
                    ? "/Mediko/main/admin/admin_Dashboard.php"
                    : "/Mediko/main/user/user_Dashboard.php"));
                exit;
            } else {
                $msg_pass = "Incorrect password.";
            }
        } else {
            $msg_user = "Username not found.";
        }
    }  
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Log In</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
        background: url('/Mediko/images/bg_login.jpg') no-repeat center center fixed;
        background-size: cover;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        height: 100vh;
        margin: 0;
        color: #ffffff;
    }
    header {
        background-color: rgba(11, 30, 74, 0.85);
        padding: 10px 30px;
        display: flex;
        align-items: center;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        z-index: 10;
        box-shadow: 0 2px 10px rgba(0,0,0,0.4);
    }
    .logo { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; margin-right: 15px; border: 2px solid white; }
    .brand-name { font-size: 1.8rem; font-weight: bold; color: #ffffff; letter-spacing: 1px; }
    .overlay {
        background-color: rgba(11, 30, 74, 0.75);
        height: 100vh;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        padding-left: 12%;
        padding-top: 60px;
    }
    .form-box {
        background-color: #ffffff;
        color: #0b1e4a;
        padding: 2rem 3rem;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        max-width: 400px;
        width: 100%;
    }
    .form-box h2 {
        text-align: center;
        margin-bottom: 1.5rem;
        color: #0b1e4a;
        font-weight: bold;
    }
    .form-label { color: #0b1e4a; font-weight: 500; }
    .form-control { background-color: #f5f7fb; border: 1px solid #ccc; }
    .form-control:focus { border-color: #0d3c91; box-shadow: 0 0 5px rgba(13, 60, 145, 0.4); }
    button {
        background-color: #0d3c91;
        border: none;
        color: white;
        width: 100%;
        padding: 0.6rem;
        border-radius: 5px;
        font-weight: bold;
        transition: background-color 0.3s;
    }
    button:hover { background-color: #1452c0; }
    .text-error { color: red; font-size: 0.9rem; margin-top: 5px; }
    .msg-general { text-align: center; color: red; margin-bottom: 10px; }
    @media (max-width: 768px) {
        .overlay { justify-content: center; padding-left: 0; }
        .brand-name { font-size: 1.5rem; }
        .logo { width: 45px; height: 45px; }
        .form-box { padding: 1.5rem 2rem; }
    }
</style>
</head>
<body>
<header>
    <img src="/Mediko/images/logo.png" alt="Mediko Logo" class="logo"
         onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
    <span style="display:none; font-size:1.5rem; color:white; margin-right:15px;">M</span>
    <span class="brand-name">Mediko</span>
</header>
<div class="overlay">
    <div class="form-box">
        <form method="post">
            <h2>Log In</h2>
            <?php if($msg_general) echo "<div class='msg-general'>$msg_general</div>"; ?>

            <div class="mb-3">
                <label for="username" class="form-label">Username:</label>
                <input type="text" class="form-control" id="username" name="username"
                       placeholder="Enter your username" value="<?= htmlspecialchars($username) ?>">
                <?php if($msg_user) echo "<div class='text-error'>$msg_user</div>"; ?>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password:</label>
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="Enter your password">
                <?php if($msg_pass) echo "<div class='text-error'>$msg_pass</div>"; ?>
            </div>

            <button type="submit" name="login">Log In</button>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include("../template/footer.php"); ?>
</body>
</html>