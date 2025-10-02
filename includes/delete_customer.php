<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to manage customers.");
}

if (!isset($_POST['id'])) {
    die("No customer ID specified.");
}

$customer_id = (int) $_POST['id'];
$loggedInUserId = $_SESSION['user_id'];
$adminUserId = 10; // admin ID

try {
    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if the customer belongs to this user OR if current user is admin
    $stmt = $pdo->prepare("SELECT id, user_id FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        die("Customer not found.");
    }

    if ($loggedInUserId === $adminUserId) {
        // Admin: delete permanently
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => $customer_id]);
        header("Location: ../dashboard.php?deleted=1");
    } else {
        // Regular user: only reassign if they own it
        if ($customer['user_id'] != $loggedInUserId) {
            die("You don't have permission to reassign this customer.");
        }

        // Reassign to admin
        $stmt = $pdo->prepare("UPDATE customers SET user_id = :admin_id WHERE id = :id");
        $stmt->execute([
            ':admin_id' => $adminUserId,
            ':id' => $customer_id
        ]);
        header("Location: ../dashboard.php?reassigned=1");
    }

    exit;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
