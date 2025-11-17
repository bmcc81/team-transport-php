<?php
session_start();

// Fetch all users (drivers/admins) to populate the handler dropdown
require_once __DIR__ . '/../services/config.php';

$usersResult = $conn->query("SELECT id, username, role FROM users ORDER BY role, username ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Customer - Team Transport</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="../styles/shared.css" rel="stylesheet">
</head>
<body class="bg-light p-4">

  <div class="container">
    <div class="card shadow-lg p-4">
      <h2 class="text-center mb-4">➕ Create New Customer</h2>

      <!-- Display Messages -->
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
      <?php endif; ?>

      <form action="../includes/create_customer.php" method="POST" novalidate>
        <div class="row g-3">

          <!-- Company Info -->
          <div class="col-md-6">
            <label class="form-label fw-bold">Company Name *</label>
            <input type="text" name="customer_company_name" class="form-control" required>
          </div>

          <!-- Internal Handler Dropdown -->
          <div class="col-md-6">
            <label class="form-label fw-bold">Assign To (Handler) *</label>
            <select name="user_id" class="form-select" required>
              <option value="">-- Select Handler --</option>
              <?php while ($u = $usersResult->fetch_assoc()): ?>
                <option value="<?= $u['id'] ?>">
                  <?= htmlspecialchars(ucfirst($u['username'])) ?> (<?= htmlspecialchars($u['role']) ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Contact Info -->
          <div class="col-md-6">
            <label class="form-label fw-bold">Contact First Name *</label>
            <input type="text" name="customer_contact_first_name" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Contact Last Name *</label>
            <input type="text" name="customer_contact_last_name" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Email *</label>
            <input type="email" name="customer_email" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Phone *</label>
            <input type="tel" name="customer_phone" class="form-control" required>
          </div>

          <!-- Address Info -->
          <div class="col-12">
            <label class="form-label fw-bold">Address *</label>
            <input type="text" name="customer_contact_address" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">City *</label>
            <input type="text" name="customer_contact_city" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">State/Province *</label>
            <input type="text" name="customer_contact_state_or_province" class="form-control" required>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">ZIP/Postal Code *</label>
            <input type="text" name="customer_contact_zip_or_postal_code" class="form-control" required>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-bold">Country *</label>
            <input type="text" name="customer_contact_country" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Fax</label>
            <input type="text" name="customer_fax" class="form-control">
          </div>

          <!-- Website -->
          <div class="col-12">
            <label class="form-label fw-bold">Website</label>
            <input type="url" name="customer_website" class="form-control" placeholder="https://example.com">
          </div>
        </div>

        <!-- Buttons -->
        <div class="d-flex justify-content-between mt-4">
          <a href="../dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
          <button type="submit" class="btn btn-primary">Save Customer</button>
        </div>
      </form>
    </div>
  </div>

</body>
</html>
