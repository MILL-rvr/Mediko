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

$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$count = $conn->query("SELECT COUNT(*) AS total FROM tb_students");
$total = $count->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$sql = "SELECT s.*, u.user_username, u.user_email, r.record_height, r.record_weight, r.record_bmi, r.record_bmiCategory, r.record_healthissues
        FROM tb_students s
        LEFT JOIN tb_users u ON s.user_id = u.user_id
        LEFT JOIN tb_record r ON s.stud_id = r.stud_id
        ORDER BY s.stud_id ASC
        LIMIT $offset, $limit";
$result = $conn->query($sql);

if (isset($_POST['delete_stud_id'])) {
    $del_id = intval($_POST['delete_stud_id']);
    $stmt = $conn->prepare("DELETE FROM tb_students WHERE stud_id=?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header("Location: ../admin/admin_StudentsRecord.php"); 
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin Students</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: 'Segoe UI'; background: #f8f9fa; margin: 0; }
.sidebar { position: fixed; width: 250px; height: 100vh; left: 0; top: 0; background: rgba(11, 30, 74, 0.85); color: #fff; padding: 20px; overflow-y: auto; }
.main-content { margin-left: 270px; padding: 30px 20px; }
.table-container { max-width: 1200px; margin: auto; }
.table th, .table td { vertical-align: middle; text-align: justify; }
.btn-icon { padding: 4px 8px; }
img.profile-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 50%; margin-right: 8px; }
</style>
</head>
<body>

<?php include('admin_Sidebar.php'); ?>

<div class="main-content">
    <div class="table-container">
        <h4 style="color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px;">Student Records</h4>

        <a href="../admin/admin_StudentsRecord_Add.php" class="btn btn-primary mb-3">Add New Student</a>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>No.</th>
                    <th>Image</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Full Name</th>
                    <th>Course</th>
                    <th>Year</th>
                    <th>Gender</th>
                    <th>Age</th>
                    <th>Height</th>
                    <th>Weight</th>
                    <th>BMI</th>
                    <th>BMI Category</th>
                    <th>Health Issues</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['stud_id']; ?></td>
                        <td>
                            <?php
                                $imagePath = !empty($row['stud_imageurl']) ? '../../' . $row['stud_imageurl'] : '../../images/students/default_image.jpg';
                            ?>
                            <img src="<?= $imagePath ?>" alt="Profile Image" class="profile-thumb">
                        </td>
                        <td><?= htmlspecialchars($row['user_username']); ?></td>
                        <td><?= htmlspecialchars($row['user_email']); ?></td>
                        <td><?= htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_mname'] . ' ' . $row['stud_lname']); ?></td>
                        <td><?= htmlspecialchars($row['stud_course']); ?></td>
                        <td><?= htmlspecialchars($row['stud_year']); ?></td>
                        <td><?= htmlspecialchars($row['stud_gender']); ?></td>
                        <td><?= htmlspecialchars($row['stud_age']); ?></td>
                        <td><?= htmlspecialchars($row['record_height'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($row['record_weight'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($row['record_bmi'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($row['record_bmiCategory'] ?? ''); ?></td>
                        <td><?= htmlspecialchars($row['record_healthissues'] ?? ''); ?></td>
                        <td>
                            <a href="../admin/admin_StudentsRecord_Edit.php?stud_id=<?= $row['stud_id']; ?>" class="btn btn-warning btn-sm btn-icon"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#deleteModal" data-studid="<?= $row['stud_id']; ?>" data-fullname="<?= htmlspecialchars($row['stud_fname'] . ' ' . $row['stud_lname']); ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="15" class="text-center">No students found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if($total_pages > 1): ?>
        <nav aria-label="Student page navigation">
            <ul class="pagination justify-content-end mt-3">
                <?php if($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                    </li>
                <?php endif; ?>
                <?php for($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <?php if($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                    </li>
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
          <h5 class="modal-title" id="deleteModalLabel">Delete Student</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete student: <strong id="modal-fullname"></strong>?
          <input type="hidden" name="delete_stud_id" id="delete-stud-id">
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
deleteModal.addEventListener('show.bs.modal', function (event) {
  var button = event.relatedTarget;
  var studid = button.getAttribute('data-studid');
  var fullname = button.getAttribute('data-fullname');

  var modalFullname = deleteModal.querySelector('#modal-fullname');
  var inputStudId = deleteModal.querySelector('#delete-stud-id');

  modalFullname.textContent = fullname;
  inputStudId.value = studid;
});
</script>

</body>
</html>
