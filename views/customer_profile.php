<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../services/config.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid customer ID.";
    header("Location: ../dashboard.php");
    exit;
}

$customerId = (int) $_GET['id'];
$userRole   = $_SESSION['role'] ?? 'user';
$loggedUser = $_SESSION['user_id'];

try {
    // --- Fetch customer ---
    if ($userRole === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM customers 
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':id'  => $customerId,
            ':uid' => $loggedUser
        ]);
    }

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['error'] = "Customer not found or access denied.";
        header("Location: ../dashboard.php");
        exit;
    }

    // --- Fetch last 10 loads ---
    $stmt = $pdo->prepare("
        SELECT load_id, reference_number, pickup_city, delivery_city, load_status, created_at
        FROM loads 
        WHERE customer_id = :cid
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([':cid' => $customerId]);
    $recentLoads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Profile</title>
    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="container py-4">

    <!-- Back -->
    <a href="../dashboard.php" class="btn btn-outline-secondary mb-3">← Back to Dashboard</a>

    <div class="row g-4">

        <!-- LEFT COLUMN: Customer Information -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <?= htmlspecialchars($customer['customer_company_name']); ?>
                    </h4>
                    <small class="text-white-50">Customer Profile</small>
                </div>

                <div class="card-body">

                    <h5 class="text-primary">Contact Details</h5>
                    <p>
                        <strong>Name:</strong>
                        <?= htmlspecialchars($customer['customer_contact_first_name']); ?>
                        <?= htmlspecialchars($customer['customer_contact_last_name']); ?>
                        <br>

                        <strong>Email:</strong> 
                        <a href="mailto:<?= htmlspecialchars($customer['customer_email']); ?>">
                            <?= htmlspecialchars($customer['customer_email']); ?>
                        </a><br>

                        <strong>Phone:</strong> 
                        <?= htmlspecialchars($customer['customer_phone'] ?: 'N/A'); ?><br>

                        <strong>Fax:</strong> 
                        <?= htmlspecialchars($customer['customer_fax'] ?: 'N/A'); ?>
                    </p>

                    <hr>

                    <h5 class="text-primary">Address</h5>
                    <p class="mb-0">
                        <?= htmlspecialchars($customer['customer_contact_address']); ?><br>
                        <?= htmlspecialchars($customer['customer_contact_city']); ?>,
                        <?= htmlspecialchars($customer['customer_contact_state_or_province']); ?><br>
                        <?= htmlspecialchars($customer['customer_contact_country']); ?>
                    </p>

                    <hr>

                    <h5 class="text-primary">Meta</h5>
                    <p>
                        <strong>Client Owner:</strong>
                        <?= htmlspecialchars($customer['customer_internal_handler_name'] ?: 'Unassigned'); ?><br>

                        <strong>Created At:</strong>
                        <?= htmlspecialchars($customer['created_at']); ?><br>

                        <strong>Customer ID:</strong> #<?= $customerId; ?><br>

                        <strong>Website:</strong>
                        <?php if ($customer['customer_website']): ?>
                            <a href="<?= htmlspecialchars($customer['customer_website']); ?>" 
                               target="_blank" rel="noopener">
                                <?= htmlspecialchars($customer['customer_website']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">N/A</span>
                        <?php endif; ?>
                    </p>

                </div>
            </div>

            <!-- Actions -->
            <div class="mt-3 d-flex gap-2">
                <a href="update_client_view.php?id=<?= $customerId; ?>" class="btn btn-primary">
                    Edit Customer
                </a>

                <a href="loads_by_client.php?customer_id=<?= $customerId; ?>" class="btn btn-info">
                    View All Loads
                </a>

                <!-- Delete button with modal -->
                <button class="btn btn-danger" data-bs-toggle="modal"
                        data-bs-target="#deleteModal">
                    Delete Customer
                </button>
            </div>

        </div>

        <!-- RIGHT COLUMN: Recent Loads -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Recent Loads</h5>
                </div>

                <div class="card-body">

                    <?php if (!empty($recentLoads)): ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($recentLoads as $load): ?>
                                <li class="list-group-item">
                                    <strong>#<?= $load['load_id']; ?></strong><br>
                                    <?= htmlspecialchars($load['pickup_city']); ?>
                                    → <?= htmlspecialchars($load['delivery_city']); ?><br>
                                    <span class="badge bg-info text-dark"><?= htmlspecialchars($load['load_status']); ?></span><br>
                                    <small class="text-muted"><?= $load['created_at']; ?></small>

                                    <div class="mt-1">
                                        <a href="load_view.php?id=<?= $load['load_id']; ?>"
                                           class="btn btn-sm btn-outline-primary">
                                            View Load
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    <?php else: ?>
                        <p class="text-muted">No loads found for this customer.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                Are you sure you want to delete 
                <strong><?= htmlspecialchars($customer['customer_company_name']); ?></strong>?
                <br><br>
                <small class="text-muted">This action cannot be undone.</small>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>

                <form method="POST" action="../includes/delete_customer.php">
                    <input type="hidden" name="id" value="<?= $customerId; ?>">
                    <button type="submit" class="btn btn-danger">
                        Confirm Delete
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
