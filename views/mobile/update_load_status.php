<?php
session_start();

if (!isset($_SESSION['id'])) die("Not logged in");

$userId = (int) $_SESSION['id'];
$role = $_SESSION['role'] ?? '';

if ($role !== 'driver' && $role !== 'dispatcher' && $role !== 'admin') {
    die("Unauthorized");
}

$loadId = (int) ($_POST['load_id'] ?? 0);
$newStatus = $_POST['load_status'] ?? '';

$allowed = ['assigned','in_transit','delivered','cancelled'];

if (!in_array($newStatus, $allowed)) die("Invalid status.");

require_once __DIR__ . '/../../services/config.php';

if ($role === 'driver') {
    // Ensure driver owns this load
    $check = $conn->prepare("SELECT load_id FROM loads WHERE load_id = ? AND assigned_driver_id = ?");
    $check->bind_param("ii", $loadId, $userId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        die("You cannot update this load.");
    }
}

$stmt = $conn->prepare("UPDATE loads SET load_status = ? WHERE load_id = ?");
$stmt->bind_param("si", $newStatus, $loadId);
$stmt->execute();

header("Location: driver_load_details.php?id=" . $loadId);
exit;
