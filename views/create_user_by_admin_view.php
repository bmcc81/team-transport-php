<?php
session_start();

// Admin-only gate
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
  $_SESSION['error'] = "Access denied: Admins only.";
  header("Location: ../dashboard.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Create User (Admin)</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet" />
  <link href="../styles/shared.css" rel="stylesheet" />
</head>
<body class="bg-light p-4">

  <div class="container" style="max-width: 720px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <a href="../dashboard.php" class="btn btn-secondary">← Back</a>
      <h2 class="m-0">Create User by Admin (Only)</h2>
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

    <form method="POST" action="../includes/create_user.php" class="card shadow-sm p-4">
      <!-- Full Name -->
      <div class="mb-3">
        <label for="full_name" class="form-label">Full Name *</label>
        <input type="text" id="full_name" name="full_name" class="form-control" required autocomplete="off" />
      </div>

      <!-- Username -->
      <div class="mb-3">
        <label for="username" class="form-label">Username *</label>
        <input type="text" id="username" name="username" class="form-control" required autocomplete="off" />
      </div>

      <!-- Email -->
      <div class="mb-3">
        <label for="email" class="form-label">Email (optional)</label>
        <input type="email" id="email" name="email" class="form-control" autocomplete="off" />
        <div class="form-text">If provided, it must be a valid email address.</div>
      </div>

      <!-- Password -->
      <div class="mb-3">
        <label for="password" class="form-label">Password *</label>
        <div class="input-group">
          <input type="password" id="password" name="password" class="form-control" required />
          <button type="button" class="btn btn-outline-secondary" id="togglePwd">Show</button>
          <button type="button" class="btn btn-outline-primary" id="genPwd">Generate</button>
        </div>
        <div class="form-text">Use the generator to quickly create a strong temporary password.</div>
      </div>

      <!-- Role -->
      <div class="mb-3">
        <label for="role" class="form-label">Role *</label>
        <select id="role" name="role" class="form-select" required>
          <option value="driver">Driver</option>
          <option value="dispatcher">Dispatcher</option>
          <option value="customer">Customer</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success">Create User</button>
        <button type="reset" class="btn btn-outline-secondary">Clear</button>
      </div>
    </form>
  </div>

  <script src="../styles/js/bootstrap.bundle.min.js"></script>
  <script>
    // Show/Hide Password
    const pwd = document.getElementById('password');
    const toggle = document.getElementById('togglePwd');
    toggle.addEventListener('click', () => {
      const isText = pwd.getAttribute('type') === 'text';
      pwd.setAttribute('type', isText ? 'password' : 'text');
      toggle.textContent = isText ? 'Show' : 'Hide';
    });

    // Generate Strong Password
    const genBtn = document.getElementById('genPwd');
    genBtn.addEventListener('click', () => {
      const U='ABCDEFGHIJKLMNOPQRSTUVWXYZ';
      const L='abcdefghijklmnopqrstuvwxyz';
      const D='0123456789';
      const S='!@#$%^&*()_+-=[]{};:,.<>?';
      const all = U+L+D+S;
      let out = [
        U[Math.floor(Math.random()*U.length)],
        L[Math.floor(Math.random()*L.length)],
        D[Math.floor(Math.random()*D.length)],
        S[Math.floor(Math.random()*S.length)]
      ];
      while (out.length < 12) {
        out.push(all[Math.floor(Math.random()*all.length)]);
      }
      for (let i = out.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [out[i], out[j]] = [out[j], out[i]];
      }
      pwd.value = out.join('');
      pwd.setAttribute('type','text');
      toggle.textContent = 'Hide';
    });
  </script>
</body>
</html>
