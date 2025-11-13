<?php
session_start();

// Only admins can access this page
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
  $_SESSION['error'] = "Access denied: Admins only.";
  header("Location: ../dashboard.php");
  exit();
}

if (!isset($_GET['id'])) {
  $_SESSION['error'] = "No user ID provided.";
  header("Location: ../dashboard.php");
  exit();
}

$userId = (int) $_GET['id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "team_transport");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT id, full_name, username, email, role FROM users WHERE id=?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  $_SESSION['error'] = "User not found.";
  header("Location: ../dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Edit User</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../styles/shared.css" rel="stylesheet" />
</head>
<body class="bg-light p-4">

  <div class="container" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="../dashboard.php" class="btn btn-secondary">← Back</a>
      <h2 class="m-0">Edit User</h2>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="../includes/update_user.php" class="card shadow-sm p-4">
      <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>" />

      <div class="mb-3">
        <label for="full_name" class="form-label">Full Name *</label>
        <input type="text" id="full_name" name="full_name" value="<?= htmlspecialchars($user['full_name']); ?>" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="username" class="form-label">Username *</label>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username']); ?>" class="form-control" required />
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email (optional)</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" class="form-control" />
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">Role *</label>
        <select id="role" name="role" class="form-select" required>
          <option value="driver" <?= $user['role'] === 'driver' ? 'selected' : '' ?>>Driver</option>
          <option value="dispatcher" <?= $user['role'] === 'dispatcher' ? 'selected' : '' ?>>Dispatcher</option>
          <option value="customer" <?= $user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
          <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">New Password (optional)</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep existing password" />
          <button type="button" class="btn btn-outline-secondary" id="togglePwd">Show</button>
        </div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="../dashboard.php" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>

  <script src="../styles/js/bootstrap.bundle.min.js"></script>
  <script>
    // Toggle password visibility
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePwd');
    toggle.addEventListener('click', () => {
      const isText = pwd.getAttribute('type') === 'text';
      pwd.setAttribute('type', isText ? 'password' : 'text');
      toggle.textContent = isText ? 'Show' : 'Hide';
    });
  </script>
</body>
</html>
