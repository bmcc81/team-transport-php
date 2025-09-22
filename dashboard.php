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

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th scope="col">User</th>
        <th scope="col">Company Name</th>
        <th scope="col">Client Owner</th>
        <th scope="col">First Name</th>
        <th scope="col">Last Name</th>
        <th scope="col">email</th>
        <th scope="col">Address</th>
        <th scope="col">City</th>
        <th scope="col">State / Province</th>
        <th scope="col">Country</th>
        <th scope="col">Phone</th>
        <th scope="col">Fax</th>
        <th scope="col">Web Site</th>
        <th scope="col">Created At</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <th scope="row">1</th>
        <td>Walmart</td>
        <td>Brandon</td>
        <td>Tim</td>
        <td>Shoniker</td>
        <td>tim@gmail.com</td>
        <td>123 Street</td>
        <td>Toronto</td>
        <td>Ontario</td>
        <td>Canada</td>
        <td>514-555-9999</td>
        <td>514-555-9991</td>
        <td>www.tim.com</td>
        <td>2025-09-21 17:44:08</td>
      </tr>
    </tbody>
  </table>
  <a href="views/create_customer_view.php" class="btn btn-success">Create Customer</a>
  <a href="views/logout.php" class="btn btn-danger">Logout</a>
</body>
</html>
