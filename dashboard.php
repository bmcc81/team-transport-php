<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <h1>Welcome to your dashboard!</h1>
  <a href="views/logout.php" class="btn btn-danger">Logout</a>
</body>
</html>
