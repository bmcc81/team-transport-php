<?php
require_once __DIR__ . '/../../services/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {   // your user ID in session
    header("Location: index.php");
    exit;
}

$loggedInUserId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'driver';

/* -------------------------
   FETCH CUSTOMERS
-------------------------- */
$customers = [];
$custQuery = "SELECT id, customer_company_name FROM customers ORDER BY customer_company_name ASC";
$custRes = $conn->query($custQuery);
while ($row = $custRes->fetch_assoc()) {
    $customers[] = $row;
}

/* -------------------------
   FETCH DRIVERS
-------------------------- */
$drivers = [];
$driverQuery = "SELECT id, username FROM users WHERE role = 'driver' ORDER BY username ASC";
$driverRes = $conn->query($driverQuery);
while ($row = $driverRes->fetch_assoc()) {
    $drivers[] = $row;
}

/* -------------------------
   FETCH LOADS
-------------------------- */
if ($userRole === 'admin' || $userRole === 'dispatcher') {
    $loadQuery = "
        SELECT l.*, 
               c.customer_company_name,
               u.username AS driver_name
        FROM loads l
        JOIN customers c ON l.customer_id = c.id
        LEFT JOIN users u ON l.assigned_driver_id = u.id
        ORDER BY l.created_at DESC
    ";
} else {
    // Only loads assigned to driver
    $loadQuery = "
        SELECT l.*, 
               c.customer_company_name,
               u.username AS driver_name
        FROM loads l
        JOIN customers c ON l.customer_id = c.id
        LEFT JOIN users u ON l.assigned_driver_id = u.id
        WHERE l.assigned_driver_id = $loggedInUserId
        ORDER BY l.created_at DESC
    ";
}

$loads = [];
$loadRes = $conn->query($loadQuery);
while ($row = $loadRes->fetch_assoc()) {
    $loads[] = $row;
}

function loadStatusBadge($status) {

    $status = strtolower($status);

    return match ($status) {

        'delivered'     => '<span class="badge bg-success">Delivered</span>',
        'pending'       => '<span class="badge bg-warning text-dark">Pending</span>',
        'in_transit'    => '<span class="badge bg-primary">In Transit</span>',
        'assigned'      => '<span class="badge" style="background:#6f42c1;">Assigned</span>',
        'cancelled'     => '<span class="badge bg-danger">Cancelled</span>',

        default         => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>'
    };
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Loads</title>
    <link href="../../styles/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-3">Loads</h2>

    <?php if ($userRole === 'admin' || $userRole === 'dispatcher'): ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="collapse" data-bs-target="#createForm">
            + Create Load
        </button>

        <div id="createForm" class="collapse card card-body mb-4">
            <form action="create_load.php" method="POST" class="p-3 border rounded bg-light">

                <h4 class="mb-3">Create New Load</h4>

                <!-- Customer -->
                <div class="mb-3">
                    <label class="form-label">Customer</label>
                    <select name="customer_id" class="form-control" required>
                        <option value="">Select customer</option>
                        <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= $c['customer_company_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reference Number -->
                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" name="reference_number" class="form-control" placeholder="LD-2025-010" required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label">Load Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>

                <hr>

                <!-- PICKUP DETAILS -->
                <h5 class="mt-3">Pickup Information</h5>

                <div class="mb-3">
                    <label class="form-label">Pickup Contact Name</label>
                    <input type="text" name="pickup_contact_name" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Pickup Address</label>
                    <input type="text" name="pickup_address" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pickup City</label>
                        <input type="text" name="pickup_city" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pickup Postal Code</label>
                        <input type="text" name="pickup_postal_code" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pickup Date/Time</label>
                        <input type="datetime-local" name="pickup_date" class="form-control" required>
                    </div>
                </div>

                <hr>

                <!-- DELIVERY DETAILS -->
                <h5 class="mt-3">Delivery Information</h5>

                <div class="mb-3">
                    <label class="form-label">Delivery Contact Name</label>
                    <input type="text" name="delivery_contact_name" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Delivery Address</label>
                    <input type="text" name="delivery_address" class="form-control" required>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Delivery City</label>
                        <input type="text" name="delivery_city" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Delivery Postal Code</label>
                        <input type="text" name="delivery_postal_code" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Delivery Date/Time</label>
                        <input type="datetime-local" name="delivery_date" class="form-control" required>
                    </div>
                </div>

                <hr>

                <!-- LOAD DETAILS -->
                <h5 class="mt-3">Load & Pricing Details</h5>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Total Weight (kg)</label>
                        <input type="number" step="0.01" name="total_weight_kg" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Rate Amount</label>
                        <input type="number" step="0.01" name="rate_amount" class="form-control">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="rate_currency" value="CAD" class="form-control">
                    </div>
                </div>

                <hr>

                <!-- DRIVER -->
                <div class="mb-3">
                    <label class="form-label">Assigned Driver</label>
                    <select name="assigned_driver_id" class="form-control">
                        <option value="">No driver assigned</option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= $d['full_name'] ?> (<?= $d['username'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Status -->
                <div class="mb-3">
                    <label class="form-label">Load Status</label>
                    <select name="load_status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="assigned">Assigned</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                    </select>
                </div>

                <!-- Notes -->
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"></textarea>
                </div>

                <button type="submit" class="btn btn-success w-100 mt-3">Create Load</button>

            </form>
        </div>
    <?php endif; ?>

    <!-- LOAD LIST -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Reference #</th>
                    <th>Customer</th>
                    <th>Driver</th>
                    <th>Pickup</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Total Weight (kg)</th>
                    <th>Rate</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($loads as $l): ?>
                <tr>

                    <td><?= $l['load_id'] ?></td>

                    <td><?= htmlspecialchars($l['reference_number']) ?></td>

                    <td><?= htmlspecialchars($l['customer_company_name']) ?></td>

                    <td>
                        <?= htmlspecialchars($l['driver_name'] ?? '—') ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($l['pickup_city']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($l['pickup_date']) ?></small>
                    </td>

                    <td>
                        <?= htmlspecialchars($l['delivery_city']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($l['delivery_date']) ?></small>
                    </td>

                    <td>
                        <?= loadStatusBadge($l['load_status']) ?>
                    </td>

                    <td><?= htmlspecialchars($l['total_weight_kg'] ?? '—') ?></td>

                    <td>
                        <?= htmlspecialchars($l['rate_amount']) ?>
                        <?= htmlspecialchars($l['rate_currency']) ?>
                    </td>

                    <td>
                        <a href="load_view.php?id=<?= $l['load_id'] ?>" 
                        class="btn btn-sm btn-primary">
                            View
                        </a>
                    </td>

                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        </div>
    </div>

</div>

<script src="../../styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
