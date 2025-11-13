<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to access this page.";
    header("Location: ../index.php");
    exit();
}

$editing = isset($_GET['id']);
$customer = [];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($editing) {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $_SESSION['error'] = "Customer not found.";
            header("Location: ../dashboard.php");
            exit();
        }
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $editing ? "Edit Customer" : "Create Customer" ?></title>
    <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">

<a href="../dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

<h2 class="mb-4"><?= $editing ? "Edit Customer" : "Create New Customer" ?></h2>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
<?php endif; ?>

<form method="POST" action="../includes/<?= $editing ? 'update_customer.php' : 'create_customer.php' ?>">
    <?php if ($editing): ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($customer['id']); ?>">
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Company Name *</label>
            <input type="text" name="customer_company_name" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_company_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Internal Handler *</label>
            <input type="text" name="customer_internal_handler_name" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_internal_handler_name'] ?? '') ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">First Name *</label>
            <input type="text" name="customer_contact_first_name" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_contact_first_name'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Last Name *</label>
            <input type="text" name="customer_contact_last_name" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_contact_last_name'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Email *</label>
        <input type="email" name="customer_email" class="form-control" required
               value="<?= htmlspecialchars($customer['customer_email'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Address *</label>
        <input type="text" name="customer_contact_address" class="form-control" required
               value="<?= htmlspecialchars($customer['customer_contact_address'] ?? '') ?>">
    </div>

    <div class="row mb-3">
        <div class="col-md-4">
            <label class="form-label">City *</label>
            <input type="text" name="customer_contact_city" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_contact_city'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">State / Province *</label>
            <input type="text" name="customer_contact_state_or_province" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_contact_state_or_province'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Postal Code *</label>
            <input type="text" name="customer_contact_zip_or_postal_code" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_contact_zip_or_postal_code'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Country *</label>
        <input type="text" name="customer_contact_country" class="form-control" required
               value="<?= htmlspecialchars($customer['customer_contact_country'] ?? '') ?>">
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label">Phone *</label>
            <input type="text" name="customer_phone" class="form-control" required
                   value="<?= htmlspecialchars($customer['customer_phone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">Fax</label>
            <input type="text" name="customer_fax" class="form-control"
                   value="<?= htmlspecialchars($customer['customer_fax'] ?? '') ?>">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label">Website</label>
        <input type="text" name="customer_website" class="form-control"
               value="<?= htmlspecialchars($customer['customer_website'] ?? '') ?>">
    </div>

    <button type="submit" class="btn btn-primary">
        <?= $editing ? "Update Customer" : "Create Customer" ?>
    </button>
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
