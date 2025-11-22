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
$count = $conn->query("SELECT COUNT(*) AS total FROM tb_users");
$total = $count->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);
$sql = "SELECT u.*, s.stud_id, s.stud_fname, s.stud_lname, s.stud_mname, s.stud_course, s.stud_year
        FROM tb_users u
        LEFT JOIN tb_students s ON u.user_id = s.user_id
        ORDER BY u.user_id ASC
        LIMIT $offset, $limit";
$result = $conn->query($sql);

if (isset($_POST['delete_user_id'])) {
    $del_id = intval($_POST['delete_user_id']);
    $stmt = $conn->prepare("DELETE FROM tb_users WHERE user_id=?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    header("Location: admin_Users.php"); 
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mediko | Admin Users</title>
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
        <h4 style="color: #0b1e4a; font-weight: bold; font-size: 2.5rem; margin-bottom: 30px;">Users</h4>

        <a href="../admin/admin_UserAdd.php" class="btn btn-primary mb-3">Add New User</a>

        <table class="table table-bordered table-striped">
            <thead class="table-primary">
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['user_id']; ?></td>
                        <td><?= htmlspecialchars($row['user_username']); ?></td>
                        <td><?= htmlspecialchars($row['user_email']); ?></td>
                        <td><?= htmlspecialchars($row['user_password']); ?></td>
                        <td><?= htmlspecialchars($row['user_role']); ?></td>
                        <td>
                            <a href="../admin/admin_UserEdit.php?user_id=<?= $row['user_id']; ?>" class="btn btn-warning btn-sm btn-icon"><i class="fa fa-edit"></i></a>
                            <button class="btn btn-danger btn-sm btn-icon" data-bs-toggle="modal" data-bs-target="#deleteModal" data-userid="<?= $row['user_id']; ?>" data-username="<?= htmlspecialchars($row['user_username']); ?>">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" class="text-center">No users found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php if($total_pages > 1): ?>
        <nav aria-label="User page navigation">
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
          <h5 class="modal-title" id="deleteModalLabel">Delete User</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          Are you sure you want to delete user: <strong id="modal-username"></strong>?
          <input type="hidden" name="delete_user_id" id="delete-user-id">
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
  var userid = button.getAttribute('data-userid');
  var username = button.getAttribute('data-username');

  var modalUser = deleteModal.querySelector('#modal-username');
  var inputUserId = deleteModal.querySelector('#delete-user-id');

  modalUser.textContent = username;
  inputUserId.value = userid;
});
</script>

</body>
</html>
