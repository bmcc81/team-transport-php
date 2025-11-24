<!-- INDEX.php -->
<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Team Transport - Login</title>
    <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
    <script src="./styles/js/bootstrap.bundle.min.js"></script>

    <!-- Manifest -->
    <link rel="manifest" href="./site.webmanifest">

    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="./images/icons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="./images/icons/favicon-16x16.png">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-4">Login</h3>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger">
        <div class="alert alert-danger">
      <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8'); unset($_SESSION['error']); ?>
    </div>

      </div>
    <?php endif; ?>

    <form method="POST" action="includes/login.php">
        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" autocomplete="on" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="pwd"  autocomplete="on" required>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="rememberMe" name="remember">
            <label class="form-check-label" for="rememberMe">Remember Me</label>
        </div>

      <button type="submit" class="btn btn-primary w-100">Login</button>

      <!-- Later when I create a logout or registration page, you can show success messages the same way -->
      <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>
      
    </form>
  </div>
</div>

</body>
</html>
