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

$customer_id = (int) $_POST['customer_id'];
$reference = $_POST['reference_number'];
$driver_id = !empty($_POST['assigned_driver_id']) ? (int) $_POST['assigned_driver_id'] : null;

$pickup_address = $_POST['pickup_address'];
$pickup_city = $_POST['pickup_city'];
$pickup_date = str_replace('T',' ', $_POST['pickup_date']);

$delivery_address = $_POST['delivery_address'];
$delivery_city = $_POST['delivery_city'];
$delivery_date = str_replace('T',' ', $_POST['delivery_date']);

$stmt = $conn->prepare("
    INSERT INTO loads
    (customer_id, created_by_user_id, assigned_driver_id, reference_number,
     pickup_address, pickup_city, pickup_date,
     delivery_address, delivery_city, delivery_date)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "iiisssssss",
    $customer_id,
    $creatorId,
    $driver_id,
    $reference,
    $pickup_address,
    $pickup_city,
    $pickup_date,
    $delivery_address,
    $delivery_city,
    $delivery_date
);

$stmt->execute();
$stmt->close();
$conn->close();

header("Location: loads_list.php");
exit;
