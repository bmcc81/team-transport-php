<?php

if (!isset($_SESSION['username'])) {
    die("You must be logged in.");
}

$loggedInUserId = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all customers for this user
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE user_id = :user_id ORDER BY created_at DESC");
    $stmt->execute([':user_id' => $loggedInUserId]);

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
