<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userRole = $_SESSION['role'];
if ($userRole !== 'admin' && $userRole !== 'dispatcher') {
    die("Unauthorized");
}

require_once __DIR__ . '/../../services/config.php';

//
// -----------------------------------------------------------------------------
// VALIDATE REQUIRED VALUES
// -----------------------------------------------------------------------------
$loadId = isset($_POST['load_id']) ? (int) $_POST['load_id'] : 0;

if ($loadId === 0) {
    die("Invalid load ID.");
}

$customer_id = isset($_POST['customer_id']) ? (int) $_POST['customer_id'] : 0;
if ($customer_id === 0) {
    die("Error: customer_id missing or invalid.");
}

//
// -----------------------------------------------------------------------------
// ASSIGN INPUTS TO REAL VARIABLES (required for bind_param)
// -----------------------------------------------------------------------------
$assigned_driver_id     = !empty($_POST['assigned_driver_id']) ? (int) $_POST['assigned_driver_id'] : null;

$reference_number       = $_POST['reference_number'] ?? '';
$pickup_contact_name    = $_POST['pickup_contact_name'] ?? '';
$pickup_address         = $_POST['pickup_address'] ?? '';
$pickup_city            = $_POST['pickup_city'] ?? '';
$pickup_postal_code     = $_POST['pickup_postal_code'] ?? '';
$pickup_date            = $_POST['pickup_date'] ?? '';

$delivery_contact_name  = $_POST['delivery_contact_name'] ?? '';
$delivery_address       = $_POST['delivery_address'] ?? '';
$delivery_city          = $_POST['delivery_city'] ?? '';
$delivery_postal_code   = $_POST['delivery_postal_code'] ?? '';
$delivery_date          = $_POST['delivery_date'] ?? '';

$total_weight_kg        = $_POST['total_weight_kg'] ?? null;
$rate_amount            = $_POST['rate_amount'] ?? null;
$rate_currency          = $_POST['rate_currency'] ?? '';
$notes                  = $_POST['notes'] ?? '';

//
// Convert numeric fields safely
//
$total_weight_kg = ($total_weight_kg !== '') ? floatval($total_weight_kg) : null;
$rate_amount     = ($rate_amount !== '') ? floatval($rate_amount) : null;

//
// -----------------------------------------------------------------------------
// PREPARE SQL UPDATE STATEMENT
// -----------------------------------------------------------------------------
$stmt = $conn->prepare("
UPDATE loads SET
    customer_id = ?,
    assigned_driver_id = ?,
    reference_number = ?,
    pickup_contact_name = ?,
    pickup_address = ?,
    pickup_city = ?,
    pickup_postal_code = ?,
    pickup_date = ?,
    delivery_contact_name = ?,
    delivery_address = ?,
    delivery_city = ?,
    delivery_postal_code = ?,
    delivery_date = ?,
    total_weight_kg = ?,
    rate_amount = ?,
    rate_currency = ?,
    notes = ?,
    updated_at = NOW()
WHERE load_id = ?
");

if (!$stmt) {
    die("SQL Prepare failed: " . $conn->error);
}
//
// FINAL, CORRECT BIND STRING:
//
$stmt->bind_param(
    "iisssssssssssddssi",
    $customer_id,
    $assigned_driver_id,
    $reference_number,
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
    $notes,
    $loadId
);

//
// -----------------------------------------------------------------------------
// EXECUTE UPDATE
// -----------------------------------------------------------------------------
if (!$stmt->execute()) {
    echo "<pre>";
    echo "SQL ERROR:\n";
    print_r($stmt->error);
    echo "\nPOST DATA:\n";
    print_r($_POST);
    echo "</pre>";
    exit;
}
header("Location: load_view.php?id=" . $loadId);
$stmt->close();
$conn->close();

//
// ----------------------------------------------------------
