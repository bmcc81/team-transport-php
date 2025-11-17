<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h1>You are logged out!</h1>
  <a href="../index.php" class="btn btn-success">Go to Login</a>
</body>
</html>
