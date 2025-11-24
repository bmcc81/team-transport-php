<?php
// dashboard.php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}
include __DIR__ . '/includes/header.php';
include "includes/user_dashboard_customers.php";
?>

<!DOCTYPE html>
<html>
<head>
  <title>Dashboard</title>
  <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="./styles/shared.css" rel="stylesheet">
</head>
<body>
  <header class="py-3 border-bottom">
    <div class="row">
      <div class="col-6 font-lg">
        <span><b>Customers Dashboard</b></span> 
      </div>
  </header>

  <div class="container-fluid">
    <table class="table table-sm table-striped table-hover shadow-sm">
      <thead class="table-theme">
        <tr>
          <th scope="col">Company Name</th>
          <th scope="col">Client Owner</th>
          <th scope="col">First Name</th>
          <th scope="col">Last Name</th>
          <th scope="col">Email</th>
          <th scope="col">Address</th>
          <th scope="col">City</th>
          <th scope="col">State / Province</th>
          <th scope="col">Country</th>
          <th scope="col">Phone</th>
          <th scope="col">Fax</th>
          <th scope="col">Website</th>
          <th scope="col">Created At</th>
          <th scope="col">Actions</th>
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

              <td class="text-nowrap">
                <!-- Update Button -->
                <form method="GET" action="views/update_client_view.php" style="display:inline;">
                    <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']); ?>">
                    <button type="submit" class="btn btn-primary btn-sm">Update</button>
                </form>

                <!-- Delete Button triggers Modal -->
                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal"
                        data-bs-target="#deleteCustomerModal<?= $customer['id']; ?>">
                    Delete
                </button>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteCustomerModal<?= $customer['id']; ?>" tabindex="-1"
                    aria-labelledby="deleteCustomerModalLabel<?= $customer['id']; ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="deleteCustomerModalLabel<?= $customer['id']; ?>">
                          Confirm Delete
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        Are you sure you want to permanently delete
                        <strong><?= htmlspecialchars($customer['customer_company_name']); ?></strong>?
                        <p class="text-muted mt-2 mb-0"><small>This action cannot be undone.</small></p>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" action="includes/delete_customer.php">
                            <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']); ?>">
                            <button type="submit" class="btn btn-danger">Confirm Delete</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="14" class="text-center">No customers found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    
    <a href="views/bookings_view.php" class="btn btn-warning mt-2">Manage Bookings</a>

  </div>

  <!-- ✅ Toast Notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080;">
  <?php if (isset($_SESSION['success'])): ?>
    <div id="successToast" class="toast align-items-center text-bg-success border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body"><strong>✅ <?= htmlspecialchars($_SESSION['success']); ?></strong></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
      <div id="errorToast" class="toast align-items-center text-bg-danger border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body"><strong>⚠️ <?= htmlspecialchars($_SESSION['error']); ?></strong></div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
      </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['info'])): ?>
    <div id="infoToast" class="toast align-items-center text-bg-info border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body text-dark"><strong>ℹ️ <?= htmlspecialchars($_SESSION['info']); ?></strong></div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>
    </div>
    <?php endif; ?>

    <?php
    // ✅ Auto-clear toast messages after rendering
    unset($_SESSION['success'], $_SESSION['error'], $_SESSION['info']);
    ?>
  </div>

  <script src="styles/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      ['successToast', 'errorToast', 'infoToast'].forEach(id => {
        const el = document.getElementById(id);
        if (el) new bootstrap.Toast(el, { delay: 2000 }).show();
      });
    });
  </script>

</body>
</html>
