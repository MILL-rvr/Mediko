<?php
session_start();
include("../../config/database.php"); 
include("../../template/header.php");

$user_id = $_SESSION['user_id'] ?? 0;
$stud_id = 0;
$stud_fname = "Student";

if ($user_id) {
    $stmt = $conn->prepare("SELECT stud_id, stud_fname FROM tb_students WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stud_id = $row['stud_id'];
        $stud_fname = $row['stud_fname'];
    }
    $stmt->close();
}

$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['monitor_id'])) {
    $monitor_id = (int)$_POST['monitor_id'];
    $temperature = $_POST['temperature'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    $stmt = $conn->prepare("UPDATE tb_monitor SET monitor_temperature = ?, monitor_remarks = ? WHERE monitor_id = ? AND stud_id = ?");
    $stmt->bind_param("ssii", $temperature, $remarks, $monitor_id, $stud_id);
    if ($stmt->execute()) {
        $update_success = true;
    }
    $stmt->close();
}

$recordsPerPage = 5;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $recordsPerPage;

$whereClauses = ["stud_id = ?"];
$params = [$stud_id];
$types = "i";

if (!empty($_GET['year'])) {
    $whereClauses[] = "YEAR(monitor_datechecked) = ?";
    $params[] = (int)$_GET['year'];
    $types .= "i";
}
if (!empty($_GET['month'])) {
    $whereClauses[] = "MONTH(monitor_datechecked) = ?";
    $params[] = (int)$_GET['month'];
    $types .= "i";
}

$count_sql = "SELECT COUNT(*) as total FROM tb_monitor WHERE " . implode(" AND ", $whereClauses);
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$totalRecords = $result->fetch_assoc()['total'];
$stmt->close();
$totalPages = ceil($totalRecords / $recordsPerPage);

$sql = "SELECT * FROM tb_monitor WHERE " . implode(" AND ", $whereClauses) . " ORDER BY monitor_datechecked DESC LIMIT ? OFFSET ?";
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$records = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Health Records</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { 
        background-color: #f8f9fa; 
        font-family: 'Segoe UI'; 
        padding: 40px 0; 
    }
    .container { 
        max-width: 1000px; 
        margin: auto; 
    }
    h2 { 
        color: #0b1e4a; 
        font-weight: bold;
        font-size: 2.5rem;
        text-align: center;
        margin-top: 80px; 
        margin-bottom: 50px;
    }
    .table td input[type="text"] {
        width: 80px; 
    }
    .table td .form-control {
        width: 100%;
    }
    .table td.symptoms {
        width: 300px; 
    }
</style>
</head>
<body>

<div class="container">
    <h2>Health Record</h2>

    <?php if ($update_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Record updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="filter_year" class="form-label">Year</label>
            <select name="year" id="filter_year" class="form-control">
                <option value="">All</option>
                <?php
                $currentYear = date('Y');
                for ($y = $currentYear; $y >= 2021; $y--) {
                    $selected = (isset($_GET['year']) && $_GET['year'] == $y) ? 'selected' : '';
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
                    $selected = (isset($_GET['month']) && $_GET['month'] == $m) ? 'selected' : '';
                    echo "<option value='$m' $selected>$monthName</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-3 align-self-end">
            <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>Date Checked</th>
                    <th>Temperature(°C)</th>
                    <th>Symptoms/Findings</th>
                    <th>Remarks</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($records)): ?>
                    <?php foreach ($records as $rec): ?>
                        <tr>
                            <form method="POST">
                                <td><?= htmlspecialchars($rec['monitor_datechecked']); ?></td>
                                <td>
                                    <input type="text" name="temperature" value="<?= htmlspecialchars($rec['monitor_temperature']); ?>" class="form-control" required>
                                </td>
                                <td class="symptoms">
                                    <?= htmlspecialchars($rec['monitor_symptoms']); ?>
                                </td>
                                <td>
                                    <select name="remarks" class="form-control" required>
                                        <?php 
                                        $options = ['Good','Sick','Recovering','Critical'];
                                        foreach($options as $opt) {
                                            $sel = ($rec['monitor_remarks'] === $opt) ? 'selected' : '';
                                            echo "<option value='$opt' $sel>$opt</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="hidden" name="monitor_id" value="<?= $rec['monitor_id']; ?>">
                                    <button type="submit" class="btn btn-success btn-sm"><i class="fa fa-save"></i> Update</button>
                                </td>
                            </form>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center">No records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for($p=1; $p<=$totalPages; $p++): ?>
                <li class="page-item <?= ($p==$page)?'active':''; ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page'=>$p])) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include("../../template/footer.php"); ?>