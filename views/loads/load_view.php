<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$loadId = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'];

require_once __DIR__ . '/../../services/config.php';

/* LOAD DETAILS */
$stmt = $conn->prepare("
    SELECT l.*, 
           c.customer_company_name,
           u.username AS driver_name
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON l.assigned_driver_id = u.id
    WHERE l.load_id = ?
");
$stmt->bind_param("i", $loadId);
$stmt->execute();
$load = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$load) die("Load not found");

/* DOCUMENTS */
$docs = [];
$d = $conn->query("SELECT * FROM load_documents WHERE load_id = $loadId ORDER BY uploaded_at DESC");
while ($r = $d->fetch_assoc()) $docs[] = $r;

function safe($v) { return htmlspecialchars($v ?? ''); }

?>
<!DOCTYPE html>
<html>
<head>
    <link href="../../styles/css/bootstrap.min.css" rel="stylesheet">
    <title>Load #<?= $loadId ?></title>

    <style>
        .section-title { font-weight: bold; margin-top: 20px; }
        .value-box { padding: 8px; background:#f8f9fa; border-radius:6px; }
        .label-box { font-size:13px; font-weight:600; color:#6c757d; margin-bottom:3px; }
    </style>
</head>

<body class="p-4 bg-light">

<a href="loads_list.php" class="btn btn-primary mb-3">&larr; Back to Load List</a>

<h3 class="mb-1">Load #<?= $loadId ?></h3>
<p class="text-muted mb-4">
    <strong>Customer:</strong> <?= safe($load['customer_company_name']) ?>
</p>

<h4 class="mt-4">Load Details</h4>

    <table class="table table-bordered table-striped bg-white mt-2">

        <tbody>

            <!-- GENERAL -->
            <tr>
                <th style="width: 250px;">Reference Number</th>
                <td><?= safe($load['reference_number']) ?></td>
            </tr>

            <tr>
                <th>Assigned Driver</th>
                <td><?= safe($load['driver_name'] ?? 'Not Assigned') ?></td>
            </tr>

            <tr>
                <th>Status</th>
                <td class="text-capitalize"><?= safe($load['load_status']) ?></td>
            </tr>

            <!-- PICKUP SECTION -->
            <tr class="table-primary">
                <th colspan="2">Pickup Information</th>
            </tr>

            <tr>
                <th>Pickup Contact Name</th>
                <td><?= safe($load['pickup_contact_name']) ?></td>
            </tr>

            <tr>
                <th>Pickup Address</th>
                <td><?= safe($load['pickup_address']) ?></td>
            </tr>

            <tr>
                <th>Pickup City</th>
                <td><?= safe($load['pickup_city']) ?></td>
            </tr>

            <tr>
                <th>Pickup Postal Code</th>
                <td><?= safe($load['pickup_postal_code']) ?></td>
            </tr>

            <tr>
                <th>Pickup Date</th>
                <td><?= safe($load['pickup_date']) ?></td>
            </tr>

            <!-- DELIVERY SECTION -->
            <tr class="table-primary">
                <th colspan="2">Delivery Information</th>
            </tr>

            <tr>
                <th>Delivery Contact Name</th>
                <td><?= safe($load['delivery_contact_name']) ?></td>
            </tr>

            <tr>
                <th>Delivery Address</th>
                <td><?= safe($load['delivery_address']) ?></td>
            </tr>

            <tr>
                <th>Delivery City</th>
                <td><?= safe($load['delivery_city']) ?></td>
            </tr>

            <tr>
                <th>Delivery Postal Code</th>
                <td><?= safe($load['delivery_postal_code']) ?></td>
            </tr>

            <tr>
                <th>Delivery Date</th>
                <td><?= safe($load['delivery_date']) ?></td>
            </tr>

            <!-- WEIGHT & RATES -->
            <tr class="table-primary">
                <th colspan="2">Weight & Rate Information</th>
            </tr>

            <tr>
                <th>Total Weight (kg)</th>
                <td><?= safe($load['total_weight_kg']) ?></td>
            </tr>

            <tr>
                <th>Rate Amount</th>
                <td><?= safe($load['rate_amount']) ?></td>
            </tr>

            <tr>
                <th>Rate Currency</th>
                <td><?= safe($load['rate_currency']) ?></td>
            </tr>

            <!-- NOTES -->
            <tr class="table-primary">
                <th colspan="2">Notes</th>
            </tr>

            <tr>
                <td colspan="2"><?= nl2br(safe($load['notes'])) ?></td>
            </tr>

            <!-- TIMESTAMP INFORMATION -->
            <tr class="table-primary">
                <th colspan="2">Timestamps</th>
            </tr>

            <tr>
                <th>Created At</th>
                <td><?= safe($load['created_at']) ?></td>
            </tr>

            <tr>
                <th>Updated At</th>
                <td><?= safe($load['updated_at'] ?? '--') ?></td>
            </tr>

        </tbody>
    </table>


<hr>

<!-- POD UPLOAD -->
<h4>Upload POD</h4>
<form action="upload_pod.php" method="POST" enctype="multipart/form-data" class="mb-3">
    <input type="hidden" name="load_id" value="<?= $loadId ?>">
    <input type="file" name="pod_file" class="form-control mb-2" required>
    <button class="btn btn-success">Upload POD</button>
</form>

<!-- DOCUMENTS -->
<h4>Documents</h4>

<ul class="list-group mb-3">
<?php foreach ($docs as $d): ?>
    <li class="list-group-item d-flex justify-content-between align-items-center">
        <div>
            <strong><?= strtoupper($d['document_type']) ?></strong>
            <br><small class="text-muted">Uploaded at: <?= $d['uploaded_at'] ?></small>
        </div>
        <a href="/TeamTransport/<?= $d['file_path'] ?>" 
           target="_blank"
           class="btn btn-sm btn-primary">View</a>
    </li>
<?php endforeach; ?>
</ul>

<!-- PDF GENERATION -->
<div class="d-flex gap-2 mb-4">

    <a class="btn btn-outline-primary" 
       href="../../services/generate_pdf.php?type=pod&load_id=<?= $loadId ?>">
       Generate POD PDF
    </a>

    <a class="btn btn-outline-secondary" 
       href="../../services/generate_pdf.php?type=bol&load_id=<?= $loadId ?>">
       Generate BOL PDF
    </a>

    <a class="btn btn-outline-success" 
       href="../../services/generate_pdf.php?type=summary&load_id=<?= $loadId ?>">
       Generate Summary PDF
    </a>

</div>

</body>
</html>
