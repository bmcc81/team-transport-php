<?php
require_once __DIR__ . '/../../services/config.php';
session_start();

if (!isset($_SESSION['id'])) {
    die("Not logged in");
}

$userRole = $_SESSION['role'];
if ($userRole !== 'admin' && $userRole !== 'dispatcher') {
    die("Unauthorized");
}

$creatorId = (int) $_SESSION['id'];

// Sanitize and collect POST fields ----------------------------
$customer_id            = (int) $_POST['customer_id'];
$reference_number       = $_POST['reference_number'] ?? null;
$assigned_driver_id     = !empty($_POST['assigned_driver_id']) ? (int) $_POST['assigned_driver_id'] : null;

$description            = $_POST['description'] ?? null;
$pickup_contact_name    = $_POST['pickup_contact_name'] ?? null;
$pickup_address         = $_POST['pickup_address'];
$pickup_city            = $_POST['pickup_city'];
$pickup_postal_code     = $_POST['pickup_postal_code'] ?? null;

$pickup_date            = str_replace('T', ' ', $_POST['pickup_date']);

$delivery_contact_name  = $_POST['delivery_contact_name'] ?? null;
$delivery_address       = $_POST['delivery_address'];
$delivery_city          = $_POST['delivery_city'];
$delivery_postal_code   = $_POST['delivery_postal_code'] ?? null;

$delivery_date          = str_replace('T', ' ', $_POST['delivery_date']);

$total_weight_kg        = !empty($_POST['total_weight_kg']) ? floatval($_POST['total_weight_kg']) : null;
$rate_amount            = !empty($_POST['rate_amount']) ? floatval($_POST['rate_amount']) : null;
$rate_currency          = $_POST['rate_currency'] ?? 'CAD';

$load_status            = $_POST['load_status'] ?? 'pending';
$notes                  = $_POST['notes'] ?? null;


// Insert SQL ---------------------------------------------------
$stmt = $conn->prepare("
    INSERT INTO loads
    (
        customer_id,
        created_by_user_id,
        assigned_driver_id,
        reference_number,
        description,
        pickup_contact_name,
        pickup_address,
        pickup_city,
        pickup_postal_code,
        pickup_date,
        delivery_contact_name,
        delivery_address,
        delivery_city,
        delivery_postal_code,
        delivery_date,
        total_weight_kg,
        rate_amount,
        rate_currency,
        load_status,
        notes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiisssssss" . "sssssdssss",
    $customer_id,
    $creatorId,
    $assigned_driver_id,
    $reference_number,
    $description,
    $pickup_contact_name,
    $pickup_address,
    $pickup_city,
    $pickup_postal_code,
    $pickup_date,
    $delivery_contact_name,
    $delivery_address,
    $delivery_city,
    $delivery_postal_code,
    $delivery_date,
    $total_weight_kg,
    $rate_amount,
    $rate_currency,
    $load_status,
    $notes
);

$stmt->execute();
$stmt->close();
$conn->close();

header("Location: loads_list.php");
exit;
?>
