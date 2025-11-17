<?php
require_once __DIR__ . '/../services/config.php';
require_once __DIR__ . '/toast_helper.php';

session_start();

$id = $_POST['id'] ?? null;
if (!$id) {
    toast('error', 'Missing booking ID.');
    header("Location: ../views/bookings_view.php");
    exit();
}

$stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
$stmt->execute([$id]);

toast('success', 'Booking deleted successfully.');
header("Location: ../views/bookings_view.php");
exit();
