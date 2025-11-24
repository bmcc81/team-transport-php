<?php 
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}
include __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../services/config.php';

$loadId = (int) $_GET['id'];
$userId = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'];


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

function loadStatusBadge($status) {

    $status = strtolower($status);

    return match ($status) {

        'delivered'     => '<span class="badge bg-success">Delivered</span>',
        'pending'       => '<span class="badge bg-warning text-dark">Pending</span>',
        'in_transit'    => '<span class="badge bg-primary">In Transit</span>',
        'assigned'      => '<span class="badge bg-purple" style="background:#6f42c1;">Assigned</span>',
        'cancelled'     => '<span class="badge bg-danger">Cancelled</span>',

        default         => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>'
    };
}

?>
<!DOCTYPE html>
<html>
<head>
    <link href="../../styles/css/bootstrap.min.css" rel="stylesheet">
    <link href="../../styles/css/bootstrap-icons/bootstrap-icons.css" rel="stylesheet" >
    <link href="../../styles/shared.css" rel="stylesheet">
    <link href="../../styles/load_view.css" rel="stylesheet">

    <title>Load #<?= $loadId ?></title>
</head>

<body>

<a href="loads_list.php" class="btn btn-primary mb-3">&larr; Back to Load List</a>


<p class="mb-4 font-header marg-btm">
    <strong>Customer:</strong> <?= safe($load['customer_company_name']) ?>
    <div class="row text-muted">
        <div class="col-6 load-details">Load Details - Load #<?= $loadId ?></div>
        <div class="col-6 edit-load">
            <?php if ($userRole === 'admin' || $userRole === 'dispatcher'): ?>
                <a href="edit_load.php?id=<?= $loadId ?>" class="btn btn-warning mb-3">Edit Load</a>
            <?php endif; ?>
        </div>
    </div>
</p>

<div class="accordion mt-3" id="loadDetailsAccordion">

    <!-- Overview -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingOverview">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOverview" aria-expanded="true">
                <i class="bi bi-info-circle-fill me-2"></i> Overview
            </button>
        </h2>
        <div id="collapseOverview" class="accordion-collapse collapse show" aria-labelledby="headingOverview" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <table class="table table-striped">
                    <tr><th>Reference Number</th><td><?= safe($load['reference_number']) ?></td></tr>
                    <tr><th>Assigned Driver</th><td><?= safe($load['driver_name'] ?? 'Not Assigned') ?></td></tr>
                    <tr><th>Status</th><td><?= loadStatusBadge($load['load_status']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Pickup -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingPickup">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePickup">
                <i class="bi bi-box-arrow-in-up me-2"></i> Pickup Information
            </button>
        </h2>
        <div id="collapsePickup" class="accordion-collapse collapse" aria-labelledby="headingPickup" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <table class="table table-striped">
                    <tr><th>Contact Name</th><td><?= safe($load['pickup_contact_name']) ?></td></tr>
                    <tr><th>Address</th><td><?= safe($load['pickup_address']) ?></td></tr>
                    <tr><th>City</th><td><?= safe($load['pickup_city']) ?></td></tr>
                    <tr><th>Postal Code</th><td><?= safe($load['pickup_postal_code']) ?></td></tr>
                    <tr><th>Pickup Date</th><td><?= safe($load['pickup_date']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Delivery -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingDelivery">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDelivery">
                <i class="bi bi-box-arrow-in-down me-2"></i> Delivery Information
            </button>
        </h2>
        <div id="collapseDelivery" class="accordion-collapse collapse" aria-labelledby="headingDelivery" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <table class="table table-striped">
                    <tr><th>Contact Name</th><td><?= safe($load['delivery_contact_name']) ?></td></tr>
                    <tr><th>Address</th><td><?= safe($load['delivery_address']) ?></td></tr>
                    <tr><th>City</th><td><?= safe($load['delivery_city']) ?></td></tr>
                    <tr><th>Postal Code</th><td><?= safe($load['delivery_postal_code']) ?></td></tr>
                    <tr><th>Delivery Date</th><td><?= safe($load['delivery_date']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Rates -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingRates">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRates">
                <i class="bi bi-currency-dollar me-2"></i> Weight & Rate Information
            </button>
        </h2>
        <div id="collapseRates" class="accordion-collapse collapse" aria-labelledby="headingRates" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <table class="table table-striped">
                    <tr><th>Total Weight (kg)</th><td><?= safe($load['total_weight_kg']) ?></td></tr>
                    <tr><th>Rate Amount</th><td><?= safe($load['rate_amount']) ?></td></tr>
                    <tr><th>Rate Currency</th><td><?= safe($load['rate_currency']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingNotes">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNotes">
                <i class="bi bi-card-text me-2"></i> Notes
            </button>
        </h2>
        <div id="collapseNotes" class="accordion-collapse collapse" aria-labelledby="headingNotes" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <div class="p-2 bg-light border rounded">
                    <?= nl2br(safe($load['notes'])) ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Timestamps -->
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingTimestamps">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTimestamps">
                <i class="bi bi-clock-history me-2"></i> Timestamps
            </button>
        </h2>
        <div id="collapseTimestamps" class="accordion-collapse collapse" aria-labelledby="headingTimestamps" data-bs-parent="#loadDetailsAccordion">
            <div class="accordion-body">
                <table class="table table-striped">
                    <tr><th>Created At</th><td><?= safe($load['created_at']) ?></td></tr>
                    <tr><th>Updated At</th><td><?= safe($load['updated_at'] ?? '--') ?></td></tr>
                </table>
            </div>
        </div>
    </div>

</div>


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
        <a href="../../<?= $d['file_path'] ?>" 
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
<script src="../../styles/js/bootstrap.bundle.min.js"></script>
</html>
