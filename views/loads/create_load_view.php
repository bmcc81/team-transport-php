<?php
session_start();

if (!isset($_SESSION['user_id'])) die("Not logged in");

$userRole = $_SESSION['role'] ?? 'driver';
if ($userRole !== 'admin' && $userRole !== 'dispatcher') {
    die("Unauthorized");
}

include __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../services/config.php';

// Fetch customers
$customers = $conn->query("SELECT id, customer_company_name FROM customers ORDER BY customer_company_name ASC")->fetch_all(MYSQLI_ASSOC);

// Fetch drivers
$drivers = $conn->query("SELECT id, username FROM users WHERE role = 'driver' ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);
?>

<div class="container mt-4">
    <h3 class="mb-4">Create New Load</h3>

    <form id="createLoadForm" method="POST" action="/views/loads/handle_create_load.php" enctype="multipart/form-data">
        
        <?php include __DIR__ . '/partials/load_form_fields.php'; ?>

        <div class="mt-4">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-plus-circle"></i> Create Load
            </button>
            <a href="/views/loads/loads_list.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/partials/load_form_scripts.php'; ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
