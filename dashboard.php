<?php
// dashboard.php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
} else {
    include "includes/user_dashboard_customers.php";
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="./styles/shared.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="logout-btn">
    <a href="views/logout.php" class="btn btn-danger">Logout</a>
  </div>
  <div>
  <h1><?php echo $_SESSION['username'];?>'s Client Dashboard</h1>
  <table class="table table-striped table-hover">
    <thead>
      <tr>
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
      <?php if (!empty($customers)): ?>
      <?php foreach ($customers as $customer): ?>
        <tr>
            <td><?php echo htmlspecialchars($customer['customer_company_name']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_internal_handler_name']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_first_name']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_last_name']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_email']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_address']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_city']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_state_or_province']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_contact_country']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_phone']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_fax']); ?></td>
            <td><?php echo htmlspecialchars($customer['customer_website']); ?></td>
            <td><?php echo $customer['created_at']; ?></td>
        </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="14">No customers found.</td>
        </tr>
      <?php endif; ?>
    </tbody>

  </table>
  <a href="views/create_customer_view.php" class="btn btn-success">Create Customer</a>
</body>
</html>
