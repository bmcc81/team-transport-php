<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to manage customers.";
    header("Location: ../index.php");
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? null;
$customerId = (int)($_POST['id'] ?? 0);

if ($customerId <= 0) {
    $_SESSION['error'] = "No customer ID specified.";
    header("Location: ../dashboard.php");
    exit();
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch target customer
    $stmt = $pdo->prepare("SELECT id, user_id FROM customers WHERE id = :id");
    $stmt->execute([':id' => $customerId]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['error'] = "Customer not found.";
        header("Location: ../dashboard.php");
        exit();
    }

    // Find admin dynamically instead of hardcoding ID
    $adminQuery = $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
    $adminId = (int)$adminQuery->fetchColumn();

    if ($userRole === 'admin') {
        // 🧹 Admin deletes permanently
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = :id");
        $stmt->execute([':id' => $customerId]);

        // Log deletion
        $log = $pdo->prepare("
            INSERT INTO customer_activity_log (user_id, customer_id, action, details)
            VALUES (:user_id, :customer_id, 'DELETE', 'Customer deleted permanently by admin')
        ");
        $log->execute([
            ':user_id' => $loggedInUserId,
            ':customer_id' => $customerId
        ]);

        $_SESSION['success'] = "Customer deleted successfully by admin.";
        header("Location: ../dashboard.php?deleted=1");
        exit();
    } else {
        // 🧠 Regular user: can only reassign their own customers
        if ($customer['user_id'] != $loggedInUserId) {
            $_SESSION['error'] = "You don’t have permission to reassign this customer.";
            header("Location: ../dashboard.php");
            exit();
        }

        // Reassign to admin
        $stmt = $pdo->prepare("UPDATE customers SET user_id = :admin_id WHERE id = :id");
        $stmt->execute([
            ':admin_id' => $adminId,
            ':id' => $customerId
        ]);

        // Log reassignment
        $log = $pdo->prepare("
            INSERT INTO customer_activity_log (user_id, customer_id, action, details)
            VALUES (:user_id, :customer_id, 'UPDATE', 'Customer reassigned to admin')
        ");
        $log->execute([
            ':user_id' => $loggedInUserId,
            ':customer_id' => $customerId
        ]);

        $_SESSION['success'] = "Customer reassigned to admin.";
        header("Location: ../dashboard.php?reassigned=1");
        exit();
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: ../dashboard.php");
    exit();
}
