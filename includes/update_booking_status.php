<?php
require_once __DIR__ . '/toast_helper.php';
session_start();

$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? 'pending';

if (!$id) {
    toast('error', 'Missing booking ID.');
    header("Location: ../views/bookings_view.php");
    exit();
}

$pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
$stmt->execute([$status, $id]);

toast('info', "Booking status updated to $status.");
header("Location: ../views/bookings_view.php");
exit();
