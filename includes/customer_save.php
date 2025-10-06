<?php
session_start();
require_once __DIR__ . '/validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
    $errors = validateCustomerData($_POST);

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: ../views/create_customer_view.php?id=" . urlencode($_POST['id']));
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=localhost;dbname=team_transport;charset=utf8", "root", "");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $pdo->prepare("
            UPDATE customers SET
                customer_company_name = ?,
                customer_internal_handler_name = ?,
                customer_contact_first_name = ?,
                customer_contact_last_name = ?,
                customer_email = ?,
                customer_contact_address = ?,
                customer_contact_city = ?,
                customer_contact_state_or_province = ?,
                customer_contact_zip_or_postal_code = ?,
                customer_contact_country = ?,
                customer_phone = ?,
                customer_fax = ?,
                customer_website = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['customer_company_name'],
            $_POST['customer_internal_handler_name'],
            $_POST['customer_contact_first_name'],
            $_POST['customer_contact_last_name'],
            $_POST['customer_email'],
            $_POST['customer_contact_address'],
            $_POST['customer_contact_city'],
            $_POST['customer_contact_state_or_province'],
            $_POST['customer_contact_zip_or_postal_code'],
            $_POST['customer_contact_country'],
            $_POST['customer_phone'],
            $_POST['customer_fax'] ?? null,
            $_POST['customer_website'] ?? null,
            $_POST['id']
        ]);

        $_SESSION['success'] = "Customer updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
}

header("Location: ../views/create_customer_view.php?id=" . urlencode($_POST['id'] ?? ''));
exit;
