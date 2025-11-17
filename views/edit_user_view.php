<?php
require_once "../includes/admin_protect.php";
require_once __DIR__ . '/../services/config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: manage_users.php");
    exit();
}

$userId = intval($_GET['id']);
$stmt = $conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$userData = $result->fetch_assoc();
$stmt->close();

if (!$userData) {
    $_SESSION['error'] = "User not found.";
    header("Location: manage_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit User</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <a href="manage_users.php" class="btn btn-secondary mb-3">← Back</a>
  <h2>Edit User: <?= htmlspecialchars($userData['username']); ?></h2>

  <form method="POST" action="../includes/update_user.php">
      <input type="hidden" name="id" value="<?= htmlspecialchars($userData['id']); ?>">

      <div class="mb-3">
          <label>Email</label>
          <input type="email" name="email" class="form-control" 
                 value="<?= htmlspecialchars($userData['email']); ?>">
      </div>

      <div class="mb-3">
          <label>Role</label>
          <select name="role" class="form-select">
              <?php
              $roles = ['admin', 'driver', 'dispatcher', 'customer'];
              foreach ($roles as $r) {
                  $selected = $userData['role'] === $r ? 'selected' : '';
                  echo "<option value='$r' $selected>" . ucfirst($r) . "</option>";
              }
              ?>
          </select>
      </div>

      <div class="mb-3">
          <label>New Password (optional)</label>
          <input type="password" name="new_password" class="form-control" placeholder="Leave blank to keep existing">
      </div>

      <button type="submit" class="btn btn-primary">Save Changes</button>
  </form>
</body>
</html>
