<?php
session_start();

if (!isset($_SESSION['user_id'])) die("Not logged in");

$userRole = $_SESSION['role'] ?? 'driver';

if ($userRole !== 'admin' && $userRole !== 'dispatcher') {
    die("Unauthorized");
}

include __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../services/config.php';

$loadId = (int)($_GET['id'] ?? 0);
if ($loadId <= 0) die("Invalid Load ID");

// LOAD DETAILS
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

// CUSTOMERS
$customers = $conn->query("
    SELECT id, customer_company_name 
    FROM customers 
    ORDER BY customer_company_name
")->fetch_all(MYSQLI_ASSOC);

// DRIVERS
$drivers = $conn->query("
    SELECT id, username 
    FROM users 
    WHERE role = 'driver'
    ORDER BY username
")->fetch_all(MYSQLI_ASSOC);

// EXISTING DOCUMENTS
$docs = $conn->query("
    SELECT id, file_name, original_name, uploaded_at
    FROM load_documents
    WHERE load_id = $loadId
")->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/../../includes/header.php';
?>

<div class="container mt-4">
    <h3 class="mb-4">Edit Load #<?= $loadId ?></h3>

    <form method="POST" action="/includes/loads/handle_update_load.php" enctype="multipart/form-data">
        <input type="hidden" name="load_id" value="<?= $loadId ?>">

        <?php include __DIR__ . '/partials/load_form_fields.php'; ?>

        <!-- EXISTING DOCUMENTS -->
        <h5 class="mt-4">Existing Documents</h5>
        <?php include __DIR__ . '/partials/load_documents_list.php'; ?>

        <div class="mt-4">
            <button type="submit" class="btn btn-success px-4">
                <i class="bi bi-check-circle"></i> Save Changes
            </button>
            <a href="/views/loads/load_view.php?id=<?= $loadId ?>" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
