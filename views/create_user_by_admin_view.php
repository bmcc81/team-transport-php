<!-- INDEX.php -->
<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Customer</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="../styles/shared.css" rel="stylesheet">
</head>
<body class="bg-light">

<a href="../dashboard.php" class="btn btn-primary m-3">Back</a>  

<div class="container d-flex justify-content-center align-items-center">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-4">Create User (Admin)</h3>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['success'])): ?>
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Customer form -->
    <form method="POST" action="../includes/create_user.php" onsubmit="return checkPasswords()">
        <div class="mb-3">
            <label for="username" class="form-label">
              User Name <span class="text-danger">(Required)</span>
            </label>
            <input type="text" class="form-control" id="username" name="username" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label">
              email <span class="text-danger">(Required)</span>
            </label>
            <input type="text" class="form-control" id="email" name="email" required>
        </div>

        <div class="mb-3">
            <label for="create_password" class="form-label">
              Create Password <span class="text-danger">(Required)</span>
            </label>
            <input type="password" class="form-control" id="create_password" name="create_password" required>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">
              Confirm Password <span class="text-danger">(Required)</span>
            </label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
        </div>

        <div id="passwordError" class="text-danger mb-2" style="display:none;">
          Passwords do not match!
        </div>

        <button type="submit" class="btn btn-success w-100">Create User</button>
    </form>
  </div>
</div>

<!-- Bootstrap JS for alerts -->
<script src="../styles/js/bootstrap.bundle.min.js"></script>

<script>
function checkPasswords() {
    const pw1 = document.getElementById("create_password").value;
    const pw2 = document.getElementById("confirm_password").value;
    const errorDiv = document.getElementById("passwordError");

    if (pw1 !== pw2) {
        errorDiv.style.display = "block";
        return false; // prevent submission
    }
    errorDiv.style.display = "none";
    return true;
}
</script>

</body>
</html>
