<?php
require_once __DIR__ . '/toast_helper.php';
session_start();

$id = $_POST['id'] ?? null;
if (!$id) {
    toast('error', 'Missing booking ID.');
    header("Location: ../views/bookings_view.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
$stmt->execute([$id]);

toast('success', 'Booking deleted successfully.');
header("Location: ../views/bookings_view.php");
exit();
