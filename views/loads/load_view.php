<?php
session_start();

if (!isset($_SESSION['id'])) {
    die("Not logged in");
}

$loadId = (int) $_GET['id'];
$userId = (int) $_SESSION['id'];
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
$d = $conn->query("SELECT * FROM load_documents WHERE load_id = $loadId");
while ($r = $d->fetch_assoc()) $docs[] = $r;

?>
<!DOCTYPE html>
<html>
<head>
    <link href="styles/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-3 bg-light">

<h3>Load #<?= $loadId ?></h3>
<p><strong>Customer:</strong> <?= $load['customer_company_name'] ?></p>

<hr>

<!-- POD UPLOAD -->
<form action="upload_pod.php" method="POST" enctype="multipart/form-data" class="mb-3">
    <input type="hidden" name="load_id" value="<?= $loadId ?>">
    <label>Upload POD</label>
    <input type="file" name="pod_file" class="form-control mb-2" required>
    <button class="btn btn-success">Upload</button>
</form>

<h4>Documents</h4>
<ul class="list-group">
    <?php if (empty($docs)): ?>
        <li class="list-group-item">No documents</li>
    <?php endif; ?>

    <?php foreach ($docs as $d): ?>
        <li class="list-group-item d-flex justify-content-between">
            <?= $d['document_type'] ?>
            <a href="<?= $d['file_path'] ?>" target="_blank" class="btn btn-sm btn-primary">View</a>
        </li>
    <?php endforeach; ?>
</ul>

</body>
</html>
