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

$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$sql = "SELECT c.chatbot_id, u.user_username, c.chatbot_userMessage, c.chatbot_aiResponse, c.chatbot_created
        FROM tb_chatbot c
        LEFT JOIN tb_students s ON c.stud_id = s.stud_id
        LEFT JOIN tb_users u ON s.user_id = u.user_id
        WHERE 1=1";

if ($filter_year) {
    $sql .= " AND YEAR(c.chatbot_created) = " . intval($filter_year);
}

if ($filter_month) {
    $sql .= " AND MONTH(c.chatbot_created) = " . intval($filter_month);
}

$total_result = $conn->query($sql);
$total_records = $total_result->num_rows;

$sql .= " ORDER BY c.chatbot_created DESC LIMIT $offset, $limit";
$result = $conn->query($sql);
$total_pages = ceil($total_records / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin AI ChatBot</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8f9fa; margin: 0; }
.sidebar { position: fixed; width: 250px; height: 100vh; left: 0; top: 0; background: rgba(11, 30, 74, 0.85); color: #fff; padding: 20px; overflow-y: auto; }
.main-content { margin-left: 270px; padding: 30px 20px; }
.table-container { max-width: 1200px; margin: auto; }
.table th, .table td { vertical-align: middle; text-align: justify; }
.filters { margin-bottom: 20px; }
</style>
</head>
<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">
    <div class="table-container">
        <h4 style="color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px;">Chatbot Logs</h4>
        <form method="GET" class="row g-3 mb-4 filters">
            <div class="col-md-3">
                <label for="year" class="form-label">Year</label>
                <select name="year" id="year" class="form-select">
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
                <label for="month" class="form-label">Month</label>
                <select name="month" id="month" class="form-select">
                    <option value="">All</option>
                    <?php
                    for ($m=1; $m<=12; $m++) {
                        $monthName = date('F', mktime(0,0,0,$m,1));
                        $selected = ($filter_month == $m) ? 'selected' : '';
                        echo "<option value='$m' $selected>$monthName</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                <a href="../admin/admin_Chatbot.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Student Message</th>
                    <th>AI Response</th>
                    <th>Date Created</th>
                </tr>
            </thead>
            <tbody>
                <?php if($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['chatbot_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['user_username']); ?></td>
                            <td><?php echo htmlspecialchars($row['chatbot_userMessage']); ?></td>
                            <td><?php echo htmlspecialchars($row['chatbot_aiResponse']); ?></td>
                            <td><?php echo htmlspecialchars($row['chatbot_created']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&page=<?php echo $page-1; ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for($p=1; $p<=$total_pages; $p++): ?>
                    <li class="page-item <?php if($p==$page) echo 'active'; ?>">
                        <a class="page-link" href="?year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?year=<?php echo $filter_year; ?>&month=<?php echo $filter_month; ?>&page=<?php echo $page+1; ?>">Next</a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
