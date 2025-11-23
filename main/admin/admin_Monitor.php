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
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$filter_course = $_GET['course'] ?? '';

$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$where = [];
$params = [];
$types = '';

if ($filter_year !== '') {
    $where[] = "YEAR(m.monitor_datechecked) = ?";
    $params[] = $filter_year;
    $types .= 'i';
}
if ($filter_month !== '') {
    $where[] = "MONTH(m.monitor_datechecked) = ?";
    $params[] = $filter_month;
    $types .= 'i';
}
if ($filter_course !== '') {
    $where[] = "s.stud_course = ?";
    $params[] = $filter_course;
    $types .= 's';
}

$where_sql = '';
if (!empty($where)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where);
}

$count_sql = "SELECT COUNT(*) AS total 
              FROM tb_monitor m
              LEFT JOIN tb_students s ON m.stud_id = s.stud_id
              LEFT JOIN tb_users u ON s.user_id = u.user_id
              $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$sql = "SELECT m.*, s.stud_fname, s.stud_mname, s.stud_lname, s.stud_course, s.stud_year, u.user_username
        FROM tb_monitor m
        LEFT JOIN tb_students s ON m.stud_id = s.stud_id
        LEFT JOIN tb_users u ON s.user_id = u.user_id
        $where_sql
        ORDER BY m.monitor_id ASC
        LIMIT ?, ?";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $types .= 'ii';
    $params[] = $offset;
    $params[] = $limit;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();
if (isset($_POST['delete_monitor_id'])) {
    $del_id = intval($_POST['delete_monitor_id']);
    $stmt_del = $conn->prepare("DELETE FROM tb_monitor WHERE monitor_id=?");
    $stmt_del->bind_param("i", $del_id);
    $stmt_del->execute();
    header("Location: admin_Monitor.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin Monitor</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI'; background: #f8f9fa; margin: 0; }
.sidebar { position: fixed; width: 250px; height: 100vh; left: 0; top: 0; background: rgba(11, 30, 74, 0.85); color: #fff; padding: 20px; overflow-y: auto; }
.main-content { margin-left: 270px; padding: 30px 20px; }
.table-container { max-width: 1200px; margin: auto; }
.table th, .table td { vertical-align: middle; text-align: justify; }
.btn-icon { padding: 4px 8px; }
</style>
</head>
<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">
    <div class="table-container">
        <h4 style="color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px;">Monitor Records</h4>

        <div class="mb-3">
            <a href="../admin/admin_Monitor_Add.php" class="btn btn-primary">Add New Monitor Record</a>
        </div>
        <form method="GET" class="row g-3 mb-4">
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
            <div class="col-md-3">
                <label for="course" class="form-label">Course</label>
                <select name="course" id="course" class="form-select">
                    <option value="">All</option>
                    <?php
                    $courses = ['BS IT','BS Architecture','BS CoE','BS CE','BS EE','BS ME','BSED','BEED','BS Mathemathics','ABEL']; 
                    foreach ($courses as $course) {
                        $selected = ($filter_course == $course) ? 'selected' : '';
                        echo "<option value='$course' $selected>$course</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3 align-self-end">
                <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Filter</button>
                <a href="../admin/admin_Monitor.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>No.</th>
                    <th>Username</th>
                    <th>Student Name</th>
                    <th>Course - Year</th>
                    <th>Date Checked</th>
                    <th>Temperature</th>
                    <th>Symptoms/Findings</th>
                    <th>Remarks</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['monitor_id']; ?></td>
                        <td><?= htmlspecialchars($row['user_username'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']); ?></td>
                        <td><?= htmlspecialchars($row['stud_course'] . ' - ' . $row['stud_year']); ?></td>
                        <td><?= htmlspecialchars($row['monitor_datechecked']); ?></td>
                        <td><?= htmlspecialchars($row['monitor_temperature']); ?></td>
                        <td><?= htmlspecialchars($row['monitor_symptoms']); ?></td>
                        <td><?= htmlspecialchars($row['monitor_remarks']); ?></td>
                        <td>
                            <a href="../admin/admin_Monitor_Edit.php?monitor_id=<?= $row['monitor_id']; ?>" class="btn btn-warning btn-sm btn-icon"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#deleteModal" data-monitorid="<?= $row['monitor_id']; ?>" data-student="<?= htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_lname']); ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center">No monitor records found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Monitor page navigation">
            <ul class="pagination justify-content-end mt-3">
                <?php if($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page-1 ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&course=<?= $filter_course ?>">Previous</a></li>
                <?php endif; ?>
                <?php for($p=1; $p<=$total_pages; $p++): ?>
                    <li class="page-item <?= ($p==$page)?'active':'' ?>"><a class="page-link" href="?page=<?= $p ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&course=<?= $filter_course ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?= $page+1 ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&course=<?= $filter_course ?>">Next</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Delete Monitor Record</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete monitor record of: <strong id="modal-student"></strong>?
          <input type="hidden" name="delete_monitor_id" id="delete-monitor-id">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Delete</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var deleteModal = document.getElementById('deleteModal');
deleteModal.addEventListener('show.bs.modal', function(event) {
    var button = event.relatedTarget;
    var monitorId = button.getAttribute('data-monitorid');
    var studentName = button.getAttribute('data-student');
    deleteModal.querySelector('#modal-student').textContent = studentName;
    deleteModal.querySelector('#delete-monitor-id').value = monitorId;
});
</script>

</body>
</html>
