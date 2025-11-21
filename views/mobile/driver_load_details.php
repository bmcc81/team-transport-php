<?php
require_once __DIR__ . '/../../services/config.php';
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION['id'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'driver') {
    die("Access denied.");
}

$loadId = (int) ($_GET['id'] ?? 0);
if ($loadId <= 0) {
    die("Invalid load.");
}

/* LOAD CHECK — must belong to the driver */
$stmt = $conn->prepare("
    SELECT l.*, 
           c.customer_company_name
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    WHERE l.load_id = ? AND l.assigned_driver_id = ?
");
$stmt->bind_param("ii", $loadId, $userId);
$stmt->execute();
$load = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$load) die("This load is not assigned to you.");

/* Fetch documents */
$docs = [];
$res = $conn->query("SELECT * FROM load_documents WHERE load_id = $loadId ORDER BY uploaded_at DESC");
while ($row = $res->fetch_assoc()) {
    $docs[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Load Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="styles/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .btn-lg { width: 100%; padding: 16px; font-size: 1.2rem; }
        .card { border-radius: 14px; }
    </style>
</head>

<body class="p-3 bg-light">

<h3>Load #<?= $loadId ?></h3>
<p><strong>Customer:</strong> <?= htmlspecialchars($load['customer_company_name']) ?></p>

<hr>

<h4>Pickup</h4>
<p>
    <?= htmlspecialchars($load['pickup_address']) ?><br>
    <?= htmlspecialchars($load['pickup_city']) ?><br>
    <?= htmlspecialchars($load['pickup_date']) ?>
</p>

<h4>Delivery</h4>
<p>
    <?= htmlspecialchars($load['delivery_address']) ?><br>
    <?= htmlspecialchars($load['delivery_city']) ?><br>
    <?= htmlspecialchars($load['delivery_date']) ?>
</p>

<hr>

<h4>Update Status</h4>
<form action="update_load_status.php" method="POST" class="mb-4">
    <input type="hidden" name="load_id" value="<?= $loadId ?>">

    <select class="form-select mb-2" name="load_status">
        <option value="assigned"    <?= $load['load_status']=='assigned'?'selected':'' ?>>Assigned</option>
        <option value="in_transit"  <?= $load['load_status']=='in_transit'?'selected':'' ?>>In Transit</option>
        <option value="delivered"   <?= $load['load_status']=='delivered'?'selected':'' ?>>Delivered</option>
        <option value="cancelled"   <?= $load['load_status']=='cancelled'?'selected':'' ?>>Cancelled</option>
    </select>
    <button class="btn btn-success btn-lg">Save Status</button>
</form>

<hr>

<h4>Upload POD (Proof of Delivery)</h4>
<form action="upload_pod.php" method="POST" enctype="multipart/form-data" class="mb-4">
    <input type="hidden" name="load_id" value="<?= $loadId ?>">
    <input type="file" name="pod_file" class="form-control mb-2" accept=".jpg,.jpeg,.png,.pdf" required>
    <button class="btn btn-primary btn-lg">Upload POD</button>
</form>

<h4>Documents</h4>
<ul class="list-group">
    <?php if (empty($docs)): ?>
        <li class="list-group-item">No documents uploaded yet.</li>
    <?php endif; ?>

    <?php foreach ($docs as $d): ?>
        <li class="list-group-item d-flex justify-content-between">
            <?= strtoupper($d['document_type']) ?>  
            <a href="<?= $d['file_path'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>
