<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit;
}

$userId = (int) $_SESSION['id'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'driver') {
    die("Access denied. Drivers only.");
}

require_once __DIR__ . '/../../services/config.php';

// GET LOADS ASSIGNED TO DRIVER
$stmt = $conn->prepare("
    SELECT load_id, reference_number, pickup_city, delivery_city, load_status
    FROM loads
    WHERE assigned_driver_id = ?
    ORDER BY pickup_date ASC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$loads = [];
while ($row = $res->fetch_assoc()) {
    $loads[] = $row;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Loads</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="styles/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .card {
            font-size: 1.1rem;
            border-radius: 14px;
        }
        .btn-load {
            width: 100%;
            padding: 14px;
            font-size: 1.2rem;
        }
    </style>
</head>

<body class="bg-light p-3">

<h3 class="mb-3">My Assigned Loads</h3>

<?php if (empty($loads)): ?>
    <p>No loads assigned.</p>
<?php endif; ?>

<?php foreach ($loads as $l): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">

            <h4>Ref: <?= htmlspecialchars($l['reference_number']) ?></h4>

            <p class="mb-1">
                <strong><?= htmlspecialchars($l['pickup_city']) ?></strong>
                → 
                <strong><?= htmlspecialchars($l['delivery_city']) ?></strong>
            </p>

            <span class="badge bg-secondary mb-3">
                <?= htmlspecialchars($l['load_status']) ?>
            </span>

            <a href="driver_load_details.php?id=<?= $l['load_id'] ?>" 
               class="btn btn-primary btn-load">
               Open Load
            </a>

        </div>
    </div>
<?php endforeach; ?>

</body>
</html>
