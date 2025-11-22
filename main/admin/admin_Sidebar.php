<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Sidebar</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .sidebar {
        background: rgba(11, 30, 74, 0.85);
        padding: 20px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        height: 100vh;
        position: fixed;
        width: 300px;
        left: 0;
        top: 0;
        overflow-y: auto;
        color: #fff;
        transition: all 0.3s ease;
        z-index: 1000;
        border-radius: 0;
    }

    .sidebar .logo {
        display: flex;
        align-items: center;
        justify-content: flex-start; 
        margin-bottom: 30px;
    }

    .sidebar .logo img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 15px;
        border: 2px solid white;
    }

    .sidebar .logo h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: bold;
        color: #fff;
    }

    .sidebar h4 {
        color: #fff;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .sidebar .nav-link {
        color: #fff;
        font-weight: 500;
        margin-bottom: 10px;
    }

    .sidebar .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-radius: 5px;
    }
    .main-content {
        margin-left: 270px;
        padding: 20px;
    }

    @media (max-width: 768px) {
        .sidebar {
            width: 200px;
        }
        .main-content {
            margin-left: 0;
            padding-top: 80px;
        }
    }
</style>
</head>

<body>
<div class="sidebar">
    <div class="logo">
        <img src="../../images/logo.png" alt="Mediko Logo" class="img-fluid">
        <h4>Mediko</h4>
    </div>
    <nav class="nav flex-column">
        <a class="nav-link" href="../admin/admin_Dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link" href="../admin/admin_Users.php">
            <i class="fas fa-users"></i> Users
        </a>
        <a class="nav-link" href="../admin/admin_StudentsRecord.php">
            <i class="fas fa-users"></i> Students Record
        </a>
        <a class="nav-link" href="../admin/admin_Monitor.php">
            <i class="fas fa-stethoscope"></i> Monitor
        </a>
        <a class="nav-link" href="../admin/admin_Chatbot.php">
            <i class="fas fa-robot"></i> Chatbot Logs
        </a>
        <a class="nav-link" href="/Mediko/main/logout.php">
            <i class="fas fa-sign-out-alt"></i> Log out
        </a>
    </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>