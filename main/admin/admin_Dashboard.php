<?php
session_start();
include("../../config/database.php");
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = '';

if ($user_id) {
    $stmt = $conn->prepare("SELECT user_role FROM tb_users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_role = $row['user_role'];
    }
    $stmt->close();
}

if ($user_role !== 'Admin') {
    header("Location: index.php");
    exit;
}

$total_students = 0;
$total_checkups = 0;
$total_ai_queries = 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_students");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_students = $row['total'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_monitor");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_checkups = $row['total'];
}
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tb_chatbot");
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_ai_queries = $row['total'];
}
$stmt->close();

$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';

$courses = [];
$result = $conn->query("SELECT DISTINCT stud_course FROM tb_students");
while($row = $result->fetch_assoc()){
    $courses[] = $row['stud_course'];
}

$checkups_data = [];
$ai_queries_data = [];

foreach($courses as $course){
    $sql = "SELECT COUNT(*) AS total FROM tb_monitor m
            JOIN tb_students s ON m.stud_id = s.stud_id
            WHERE s.stud_course = ?";
    if($filter_year) $sql .= " AND YEAR(m.monitor_datechecked) = $filter_year";
    if($filter_month) $sql .= " AND MONTH(m.monitor_datechecked) = $filter_month";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $course);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $checkups_data[] = (int)$row['total'];
    $stmt->close();

    $sql2 = "SELECT COUNT(*) AS total FROM tb_chatbot c
             JOIN tb_students s ON c.stud_id = s.stud_id
             WHERE s.stud_course = ?";
    if($filter_year) $sql2 .= " AND YEAR(c.chatbot_created) = $filter_year";
    if($filter_month) $sql2 .= " AND MONTH(c.chatbot_created) = $filter_month";

    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("s", $course);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    $row2 = $result2->fetch_assoc();
    $ai_queries_data[] = (int)$row2['total'];
    $stmt2->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { 
    background-color: #f8f9fa; 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
    margin: 0;
}
.dashboard-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
.sidebar { background: rgba(11, 30, 74, 0.85); border-radius: 0; padding: 20px; height: 100vh; position: fixed; width: 250px; left: 0; top: 0; overflow-y: auto; color: #fff; }
.sidebar .logo { display: flex; align-items: center; margin-bottom: 30px; }
.sidebar .logo img { width: 50px; height: 50px; object-fit: cover; margin-right: 10px; border: 2px solid white; }
.sidebar .logo h4 { margin: 0; font-size: 1.5rem; font-weight: bold; color: #fff; }
.sidebar h4 { color: #fff; font-weight: bold; margin-bottom: 20px; }
.sidebar .nav-link { color: #fff; font-weight: 500; margin-bottom: 10px; }
.sidebar .nav-link:hover { background-color: rgba(255, 255, 255, 0.1); border-radius: 5px; }
.main-content { margin-left: 270px; padding: 30px 20px; }
.card { background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; margin-bottom: 20px; }
.card-icon { margin-bottom: 15px; }
.card h3 { color: #0b1e4a; font-weight: bold; }
.card p { font-size: 2rem; font-weight: bold; color: #28a745; }
.filters { margin-bottom: 20px; }
@media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 100px; } .sidebar { width: 200px; } }
</style>
</head>
<body>
<div class="dashboard-container">
    <?php include('admin_Sidebar.php'); ?>

    <div class="main-content">
        <h4 style="color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px;">Dashboard</h4>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-users fa-3x" style="color: #0b1e4a;"></i></div>
                    <h3>Total Students</h3>
                    <p><?php echo htmlspecialchars($total_students); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-stethoscope fa-3x" style="color: #0b1e4a;"></i></div>
                    <h3>Total Check-ups</h3>
                    <p><?php echo htmlspecialchars($total_checkups); ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-icon"><i class="fas fa-robot fa-3x" style="color: #0b1e4a;"></i></div>
                    <h3>Total AI Queries</h3>
                    <p><?php echo htmlspecialchars($total_ai_queries); ?></p>
                </div>
            </div>
        </div>

        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="filter_year" class="form-label">Year</label>
                <select name="year" id="filter_year" class="form-control">
                    <option value="">All</option>
                    <?php
                    $currentYear = date('Y');
                    for ($y = $currentYear; $y >= 2021; $y--) {
                        $selected = ($filter_year == $y) ? 'selected' : '';
                        echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_month" class="form-label">Month</label>
                <select name="month" id="filter_month" class="form-control">
                    <option value="">All</option>
                    <?php
                    for ($m = 1; $m <= 12; $m++) {
                        $monthName = date('F', mktime(0, 0, 0, $m, 1));
                        $selected = ($filter_month == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>$monthName</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                <a href="../admin/admin_Dashboard.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <div class="card">
            <canvas id="barChart" height="100"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('barChart').getContext('2d');

const courses = <?php echo json_encode($courses); ?>;
const checkups = <?php echo json_encode($checkups_data); ?>;
const ai_queries = <?php echo json_encode($ai_queries_data); ?>;

const barChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: courses,
        datasets: [
            {
                label: 'Total Check-ups',
                data: checkups,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            },
            {
                label: 'Total AI Queries',
                data: ai_queries,
                backgroundColor: 'rgba(255, 99, 132, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Check-ups vs AI Queries per Course' }
        },
        scales: { y: { beginAtZero: true, precision: 0 } }
    }
});
</script>
</body>
</html>
