<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to delete a customer.");
}

if (!isset($_POST['id'])) {
    die("No customer ID specified.");
}

$customer_id = (int) $_POST['id'];
$loggedInUserId = $_SESSION['user_id'];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check customer belongs to this user
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $customer_id,
        ':user_id' => $loggedInUserId
    ]);

    if ($stmt->rowCount() === 0) {
        die("Customer not found or you don't have permission to delete.");
    }

    // Delete the customer
    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $customer_id,
        ':user_id' => $loggedInUserId
    ]);

    header("Location: ../dashboard.php?deleted=1");
    exit;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
