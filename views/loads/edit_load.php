<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userRole = $_SESSION['role'];
if ($userRole !== 'admin' && $userRole !== 'dispatcher') {
    die("Unauthorized");
}

include __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../services/config.php';

$loadId = (int) $_GET['id'];

/* FETCH LOAD */
$stmt = $conn->prepare("
    SELECT *
    FROM loads
    WHERE load_id = ?
");
$stmt->bind_param("i", $loadId);
$stmt->execute();
$load = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$load) die("Load not found");

/* FETCH CUSTOMERS */
$customers = $conn->query("SELECT id, customer_company_name FROM customers ORDER BY customer_company_name ASC");


/* FETCH DRIVERS */
$drivers = $conn->query("SELECT id, username FROM users WHERE role = 'driver' ORDER BY username ASC");

?>
<!DOCTYPE html>
<html>
<head>
    <link href="../../styles/css/bootstrap.min.css" rel="stylesheet">
    <title>Edit Load #<?= $loadId ?></title>
</head>
<body>

<a href="load_view.php?id=<?= $loadId ?>" class="btn btn-secondary mb-3">&larr; Back to Load Details</a>

<h3>Edit Load #<?= $loadId ?></h3>

<form method="POST" action="update_load.php" class="mt-3">

    <input type="hidden" name="load_id" value="<?= $loadId ?>">

    <!-- CUSTOMER -->
    <div class="mb-3">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select" required>
            <option value="">Select customer...</option>
            <?php while ($c = $customers->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" 
                <?= $c['id'] == $load['customer_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['customer_company_name']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- DRIVER -->
    <div class="mb-3">
        <label class="form-label">Assigned Driver</label>
        <select name="assigned_driver_id" class="form-select">
            <option value="">Unassigned</option>
            <?php while ($d = $drivers->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>" 
                    <?= $d['id'] == $load['assigned_driver_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['username']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- REFERENCE -->
    <div class="mb-3">
        <label class="form-label">Reference Number</label>
        <input type="text" name="reference_number" class="form-control"
               value="<?= htmlspecialchars($load['reference_number']) ?>" required>
    </div>

    <h5 class="mt-4">Pickup Information</h5>

    <div class="mb-3">
        <label class="form-label">Pickup Contact Name</label>
        <input type="text" name="pickup_contact_name" class="form-control"
               value="<?= htmlspecialchars($load['pickup_contact_name']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Pickup Address</label>
        <input type="text" name="pickup_address" class="form-control"
               value="<?= htmlspecialchars($load['pickup_address']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Pickup City</label>
        <input type="text" name="pickup_city" class="form-control"
               value="<?= htmlspecialchars($load['pickup_city']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Pickup Postal Code</label>
        <input type="text" name="pickup_postal_code" class="form-control"
               value="<?= htmlspecialchars($load['pickup_postal_code']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Pickup Date</label>
        <input type="datetime-local" name="pickup_date" class="form-control"
               value="<?= date('Y-m-d\TH:i', strtotime($load['pickup_date'])) ?>" required>
    </div>


    <h5 class="mt-4">Delivery Information</h5>

    <div class="mb-3">
        <label class="form-label">Delivery Contact Name</label>
        <input type="text" name="delivery_contact_name" class="form-control"
               value="<?= htmlspecialchars($load['delivery_contact_name']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Delivery Address</label>
        <input type="text" name="delivery_address" class="form-control"
               value="<?= htmlspecialchars($load['delivery_address']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Delivery City</label>
        <input type="text" name="delivery_city" class="form-control"
               value="<?= htmlspecialchars($load['delivery_city']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Delivery Postal Code</label>
        <input type="text" name="delivery_postal_code" class="form-control"
               value="<?= htmlspecialchars($load['delivery_postal_code']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Delivery Date</label>
        <input type="datetime-local" name="delivery_date" class="form-control"
               value="<?= date('Y-m-d\TH:i', strtotime($load['delivery_date'])) ?>" required>
    </div>


    <h5 class="mt-4">Rates & Weight</h5>

    <div class="mb-3">
        <label class="form-label">Total Weight (kg)</label>
        <input type="number" step="0.01" name="total_weight_kg" class="form-control"
               value="<?= htmlspecialchars($load['total_weight_kg']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Rate Amount</label>
        <input type="number" step="0.01" name="rate_amount" class="form-control"
               value="<?= htmlspecialchars($load['rate_amount']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Rate Currency</label>
        <input type="text" name="rate_currency" class="form-control"
               value="<?= htmlspecialchars($load['rate_currency']) ?>">
    </div>


    <h5 class="mt-4">Notes</h5>

    <div class="mb-3">
        <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($load['notes']) ?></textarea>
    </div>


    <button class="btn btn-primary">Save Changes</button>

</form>

</body>
</html>
