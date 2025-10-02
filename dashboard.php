<?php
// dashboard.php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

include "includes/user_dashboard_customers.php";
?>

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

<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="./styles/shared.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="logout-btn mb-3">
    <a href="views/logout.php" class="btn btn-danger">Logout</a>
  </div>

  <header class="py-3 mb-4 border-bottom"> <div class="flex-wrap justify-content"> 
    <h1><?= htmlspecialchars(ucfirst($_SESSION['username'])); ?>'s Client Dashboard</h1>
  </header>


  <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">Customer deleted successfully.</div>
  <?php endif; ?>

  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th>Company Name</th>
        <th>Client Owner</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Address</th>
        <th>City</th>
        <th>State / Province</th>
        <th>Country</th>
        <th>Phone</th>
        <th>Fax</th>
        <th>Website</th>
        <th>Created At</th>
        <th>Delete</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($customers)): ?>
        <?php foreach ($customers as $customer): ?>
          <tr>
            <td><?= htmlspecialchars($customer['customer_company_name']); ?></td>
            <td><?= htmlspecialchars($customer['customer_internal_handler_name']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_first_name']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_last_name']); ?></td>
            <td><?= htmlspecialchars($customer['customer_email']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_address']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_city']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_state_or_province']); ?></td>
            <td><?= htmlspecialchars($customer['customer_contact_country']); ?></td>
            <td><?= htmlspecialchars($customer['customer_phone']); ?></td>
            <td><?= htmlspecialchars($customer['customer_fax']); ?></td>
            <td><?= htmlspecialchars($customer['customer_website']); ?></td>
            <td><?= htmlspecialchars($customer['created_at']); ?></td>
            <td>
              <form method="post" action="includes/delete_customer.php" onsubmit="return confirm('Are you sure you want to delete this customer?');">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']); ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
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
  <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'admin'): ?>
      <a href="views/create_user_by_admin_view.php" class="btn btn-primary">Create user</a>
  <?php endif; ?>
  
</body>
</html>
