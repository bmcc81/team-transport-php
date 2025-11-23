<?php
// views/loads/bulk_actions.php
session_start();
require_once __DIR__ . '/../../services/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userRole = $_SESSION['role'] ?? 'driver';

$bulkAction  = $_POST['bulk_action'] ?? '';
$loadIds     = $_POST['load_ids'] ?? [];
$returnQuery = $_POST['return_query'] ?? '';

if (empty($bulkAction) || empty($loadIds) || !is_array($loadIds)) {
    header("Location: loads_list.php" . ($returnQuery ? "?$returnQuery" : ""));
    exit;
}

$ids = array_map('intval', $loadIds);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

// Assign driver / mark status
if ($bulkAction === 'assign_driver') {
    $driverId = !empty($_POST['bulk_driver_id']) ? (int) $_POST['bulk_driver_id'] : 0;
    if ($driverId > 0) {
        $sql = "UPDATE loads 
                SET assigned_driver_id = ?, 
                    load_status = IF(load_status='pending', 'assigned', load_status)
                WHERE load_id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $bindTypes = 'i' . $types;
        $params = array_merge([$driverId], $ids);
        $stmt->bind_param($bindTypes, ...$params);
        $stmt->execute();
        $stmt->close();
    }
} elseif (in_array($bulkAction, ['mark_in_transit', 'mark_delivered'], true)) {
    $newStatus = $bulkAction === 'mark_in_transit' ? 'in_transit' : 'delivered';
    $sql = "UPDATE loads SET load_status = ? WHERE load_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $bindTypes = 's' . $types;
    $params = array_merge([$newStatus], $ids);
    $stmt->bind_param($bindTypes, ...$params);
    $stmt->execute();
    $stmt->close();
} elseif ($bulkAction === 'delete' && $userRole === 'admin') {
    $sql = "DELETE FROM loads WHERE load_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $stmt->close();
}

header("Location: loads_list.php" . ($returnQuery ? "?$returnQuery" : ""));
exit;
