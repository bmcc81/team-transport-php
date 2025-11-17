<?php
require_once __DIR__ . '/../services/config.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in.");
}

$loggedInUserId = $_SESSION['user_id'];
$loggedInUsername = $_SESSION['username'];

try {
    // ✅ Admins see ALL customers, others see only their own
    if ($_SESSION['role'] === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM customers ORDER BY created_at DESC");
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM customers
            WHERE user_id = :user_id
            ORDER BY created_at DESC
        ");
        $stmt->execute([':user_id' => $loggedInUserId]);
    }

    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
