<?php
require_once __DIR__ . '/toast_helper.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerId = $_POST['customer_id'] ?? null;
    $tripId = $_POST['trip_id'] ?? null;

    if (!$customerId || !$tripId) {
        toast('error', 'Please select both customer and trip.');
        header("Location: ../views/bookings_view.php");
        exit();
    }

    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO bookings (customer_id, trip_id, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$customerId, $tripId]);

    toast('success', 'Booking created successfully.');
    header("Location: ../views/bookings_view.php");
    exit();
}
