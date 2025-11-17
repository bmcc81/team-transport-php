<?php
require_once __DIR__ . '/../services/config.php';
require_once __DIR__ . '/toast_helper.php';
session_start();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? 'pending';

if (!$id) {
    toast('error', 'Missing booking ID.');
    header("Location: ../views/bookings_view.php");
    exit();
}

$stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

toast('info', "Booking status updated to $status.");
header("Location: ../views/bookings_view.php");
exit();
