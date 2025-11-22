<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    body {
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
      justify-content: space-between;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 10;
      box-shadow: 0 2px 10px rgba(0,0,0,0.4);
    }

    .logo {
      width: 50px;
      height: 50px;
      border-radius: 50%;
      object-fit: cover;
      margin-right: 15px;
      border: 2px solid white;
    }

    .brand-name {
      font-size: 1.8rem;
      font-weight: bold;
      color: #ffffff;
      letter-spacing: 1px;
    }

    .logout-btn {
      background-color: #ffffff;
      border: 2px solid #ffffff;
      color: #0b1e4a;
      padding: 0.5rem 1rem;
      border-radius: 5px;
      font-weight: bold;
      text-decoration: none;
      transition: background-color 0.3s, color 0.3s;
    }

    .logout-btn:hover {
      background-color: #f0f0f0;
      color: #0b1e4a;
      text-decoration: none;
    }

    @media (max-width: 768px) {
      .brand-name {
        font-size: 1.5rem;
      }

      .logo {
        width: 35px;
        height: 35px;
      }

      .logout-btn {
        padding: 0.4rem 0.8rem;
        font-size: 0.9rem;
      }
    }
</style>
</head>
<body>
  <header class="d-flex align-items-center justify-content-between px-3 py-2" style="background-color: rgba(11, 30, 74, 0.85);">
    <div class="d-flex align-items-center">
      <img src="../../images/logo.png" alt="Mediko Logo" class="rounded-circle me-2" style="width:50px; height:50px; border:2px solid white; object-fit:cover;">
      <span class="text-white fs-4 fw-bold">Mediko</span>
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="../user/user_Dashboard.php" class="text-white text-decoration-none">Dashboard</a>
      <a href="../user/user_Profile.php" class="text-white text-decoration-none">Profile</a>
      <a href="../user/user_HealthRecord.php" class="text-white text-decoration-none">Health Record</a>
      <a href="../user/user_chatbot.php" class="text-white text-decoration-none">AI Chatbot</a>
      <a href="/Mediko/main/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </header>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>