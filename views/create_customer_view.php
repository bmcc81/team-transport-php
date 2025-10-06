<?php
session_start();


$pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$customer = null;
$isEdit = false;

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($customer) {
        $isEdit = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $isEdit ? 'Edit' : 'Create' ?> Customer</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="../styles/shared.css" rel="stylesheet">
</head>
<body class="bg-light">

<a href="../dashboard.php" class="btn btn-success m-3">Back</a>  

<div class="container d-flex justify-content-center align-items-center">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-4"><?= $isEdit ? 'Edit Customer' : 'Create Customer Form' ?></h3>

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
    <form method="POST" action="../includes/<?= $isEdit ? 'customer_save.php' : 'create_customer.php' ?>">
        <?php if ($isEdit): ?>
          <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']) ?>">
        <?php endif; ?>

        <div class="mb-3">
            <label for="customer_company_name" class="form-label">
              Customer Company Name <span class="text-danger">(Required)</span>
            </label>
            <input type="text" class="form-control" id="customer_company_name" 
                   name="customer_company_name" required
                   value="<?= htmlspecialchars($customer['customer_company_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="username" class="form-label">
                Customer Internal Handler Name <span class="text-danger">(Required)</span>
            </label>

            <?php if ($_SESSION['username'] !== 'admin'): ?>
                <input type="text" class="form-control" id="username"
                       value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled>
                <input type="hidden" name="customer_internal_handler_name"
                       value="<?= htmlspecialchars($_SESSION['username']) ?>">
            <?php else: ?>
                <input type="text" class="form-control" id="username" name="customer_internal_handler_name"
                       value="<?= htmlspecialchars($customer['customer_internal_handler_name'] ?? '') ?>">
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="customer_contact_first_name" class="form-label">Customer Contact First Name</label>
            <input type="text" class="form-control" id="customer_contact_first_name" name="customer_contact_first_name"
                   required value="<?= htmlspecialchars($customer['customer_contact_first_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_last_name" class="form-label">Customer Contact Last Name</label>
            <input type="text" class="form-control" id="customer_contact_last_name" name="customer_contact_last_name"
                   required value="<?= htmlspecialchars($customer['customer_contact_last_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_email" class="form-label">Customer Email</label>
            <input type="email" class="form-control" id="customer_email" name="customer_email"
                   required value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_address" class="form-label">Address</label>
            <input type="text" class="form-control" id="customer_contact_address" name="customer_contact_address"
                   required value="<?= htmlspecialchars($customer['customer_contact_address'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_city" class="form-label">City</label>
            <input type="text" class="form-control" id="customer_contact_city" name="customer_contact_city"
                   required value="<?= htmlspecialchars($customer['customer_contact_city'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_state_or_province" class="form-label">State/Province</label>
            <input type="text" class="form-control" id="customer_contact_state_or_province" name="customer_contact_state_or_province"
                   required value="<?= htmlspecialchars($customer['customer_contact_state_or_province'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_zip_or_postal_code" class="form-label">Zip/Postal Code</label>
            <input type="text" class="form-control" id="customer_contact_zip_or_postal_code" name="customer_contact_zip_or_postal_code"
                   required value="<?= htmlspecialchars($customer['customer_contact_zip_or_postal_code'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_contact_country" class="form-label">Country</label>
            <input type="text" class="form-control" id="customer_contact_country" name="customer_contact_country"
                   required value="<?= htmlspecialchars($customer['customer_contact_country'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="customer_phone" name="customer_phone"
                   required value="<?= htmlspecialchars($customer['customer_phone'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_fax" class="form-label">Fax</label>
            <input type="text" class="form-control" id="customer_fax" name="customer_fax"
                   value="<?= htmlspecialchars($customer['customer_fax'] ?? '') ?>">
        </div>

        <div class="mb-3">
            <label for="customer_website" class="form-label">Website</label>
            <input type="text" class="form-control" id="customer_website" name="customer_website"
                   value="<?= htmlspecialchars($customer['customer_website'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-<?= $isEdit ? 'warning' : 'primary' ?> w-100">
            <?= $isEdit ? 'Update Customer' : 'Create Customer' ?>
        </button>
    </form>
  </div>
</div>

<script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
