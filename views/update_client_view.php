<?php

session_start();

// Only admin can update users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access denied: Admins only.");
}
require_once __DIR__ . '/../services/config.php';
include __DIR__ . '/../includes/header.php';

// Get user id from URL
$id = intval($_GET['id'] ?? 0);
// $id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();

if ($id <= 0) {
    die("Invalid user ID.");
}

// Load all handler names (users with role 'dispatcher' or 'admin', or whatever you want)
$handlerQuery = $conn->query("
    SELECT id, full_name 
    FROM users
    ORDER BY full_name ASC
");

$handlers = $handlerQuery->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Client</title>
    <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div>
    <div class="card shadow p-4" style="margin:auto;">
        <h2 class="mb-4 text-center">Update Client</h2>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="../includes/update_client.php">
            <div class="mb-3">
                <label class="form-label">Company Name</label>
                <input type="hidden" name="id" value="<?= $id; ?>">
                <input type="text" name="customer_company_name" class="form-control" 
                    value="<?= htmlspecialchars($customer['customer_company_name'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Internal Handler Name</label>
                <select name="customer_internal_handler_name" class="form-select" required>
                    <option value="">-- Select Handler --</option>

                    <?php foreach ($handlers as $handler): ?>
                        <option 
                            value="<?= htmlspecialchars($handler['full_name']) ?>"
                            <?= ($customer['customer_internal_handler_name'] ?? '') === $handler['full_name'] ? 'selected' : '' ?>
                        >
                            <?= htmlspecialchars($handler['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact First Name</label>
                    <input type="text" name="customer_contact_first_name" class="form-control"
                        value="<?= htmlspecialchars($customer['customer_contact_first_name'] ?? '') ?>">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Contact Last Name</label>
                    <input type="text" name="customer_contact_last_name" class="form-control"
                        value="<?= htmlspecialchars($customer['customer_contact_last_name'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="customer_email" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <input type="text" name="customer_contact_address" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_contact_address'] ?? '') ?>">
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">City</label>
                    <input type="text" name="customer_contact_city" class="form-control"
                        value="<?= htmlspecialchars($customer['customer_contact_city'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">State/Province</label>
                    <input type="text" name="customer_contact_state_or_province" class="form-control"
                        value="<?= htmlspecialchars($customer['customer_contact_state_or_province'] ?? '') ?>">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Postal Code</label>
                    <input type="text" name="customer_contact_zip_or_postal_code" class="form-control"
                        value="<?= htmlspecialchars($customer['customer_contact_zip_or_postal_code'] ?? '') ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Country</label>
                <input type="text" name="customer_contact_country" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_contact_country'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="customer_phone" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_phone'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Fax</label>
                <input type="text" name="customer_fax" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_fax'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Website</label>
                <input type="text" name="customer_website" class="form-control"
                    value="<?= htmlspecialchars($customer['customer_website'] ?? '') ?>">
            </div>

            <button class="btn btn-primary w-100">Update Customer</button>
        </form>

        <a href="../dashboard.php" class="btn btn-secondary w-100 mt-3">Back to Users</a>
    </div>
</div>

</body>
</html>
