<?php
require_once __DIR__ . '/../../services/config.php';
session_start();

if (!isset($_SESSION['id'])) {   // your user ID in session
    header("Location: index.php");
    exit;
}

$loggedInUserId = (int) $_SESSION['id'];
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

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Loads</title>
    <link href="styles/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-3">Loads</h2>

    <?php if ($userRole === 'admin' || $userRole === 'dispatcher'): ?>
        <button class="btn btn-primary mb-3" data-bs-toggle="collapse" data-bs-target="#createForm">
            + Create Load
        </button>

        <div id="createForm" class="collapse card card-body mb-4">
            <form method="POST" action="create_load.php">

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= $c['customer_company_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Reference #</label>
                        <input type="text" name="reference_number" class="form-control" required>
                    </div>

                    <div class="col-md-4">
                        <label>Assign Driver</label>
                        <select name="assigned_driver_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= $d['username'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h5>Pickup</h5>
                <div class="row mb-3">
                    <div class="col-md-6"><input required type="text" name="pickup_address" class="form-control" placeholder="Pickup Address"></div>
                    <div class="col-md-3"><input required type="text" name="pickup_city" class="form-control" placeholder="Pickup City"></div>
                    <div class="col-md-3"><input required type="datetime-local" name="pickup_date" class="form-control"></div>
                </div>

                <h5>Delivery</h5>
                <div class="row mb-3">
                    <div class="col-md-6"><input required type="text" name="delivery_address" class="form-control" placeholder="Delivery Address"></div>
                    <div class="col-md-3"><input required type="text" name="delivery_city" class="form-control" placeholder="Delivery City"></div>
                    <div class="col-md-3"><input required type="datetime-local" name="delivery_date" class="form-control"></div>
                </div>

                <button type="submit" class="btn btn-success">Create Load</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- LOAD LIST -->
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Ref #</th>
                        <th>Customer</th>
                        <th>Driver</th>
                        <th>Pickup</th>
                        <th>Delivery</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($loads as $load): ?>
                        <tr>
                            <td><?= $load['load_id'] ?></td>
                            <td><?= $load['reference_number'] ?></td>
                            <td><?= $load['customer_company_name'] ?></td>
                            <td><?= $load['driver_name'] ?? '—' ?></td>
                            <td><?= $load['pickup_city'] ?></td>
                            <td><?= $load['delivery_city'] ?></td>
                            <td><?= $load['load_status'] ?></td>
                            <td>
                                <a class="btn btn-sm btn-primary" href="load_view.php?id=<?= $load['load_id'] ?>">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>

<script src="styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
