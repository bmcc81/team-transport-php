<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

require_once __DIR__ . '/../services/config.php';

$redirect = $_POST['redirect'] ?? '../dashboard.php';
$action   = $_POST['bulk_action'] ?? '';
$ids      = $_POST['selected_ids'] ?? [];

if (empty($ids)) {
    $_SESSION['error'] = "No customers selected.";
    header("Location: $redirect");
    exit;
}

$ids = array_map('intval', $ids);
$idList = implode(",", $ids);

try {

    if ($action === 'delete') {

        $stmt = $pdo->prepare("DELETE FROM customers WHERE id IN ($idList)");
        $stmt->execute();

        $_SESSION['success'] = "Selected customers deleted.";
        header("Location: $redirect");
        exit;

    }

    if ($action === 'assign_owner') {

        $owner = trim($_POST['bulk_owner'] ?? '');

        if ($owner === '') {
            $_SESSION['error'] = "Enter an owner name.";
        } else {
            $stmt = $pdo->prepare("
                UPDATE customers SET customer_internal_handler_name = :owner 
                WHERE id IN ($idList)
            ");
            $stmt->execute([':owner' => $owner]);

            $_SESSION['success'] = "Owner assigned to selected customers.";
        }

        header("Location: $redirect");
        exit;
    }

    if ($action === 'export_selected') {

        $_SESSION['export_ids'] = $ids;

        header("Location: ../includes/export_customers.php?selected=1");
        exit;
    }

} catch (PDOException $e) {
    $_SESSION['error'] = "DB Error: " . $e->getMessage();
    header("Location: $redirect");
    exit;
}
