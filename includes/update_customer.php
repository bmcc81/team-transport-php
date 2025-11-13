<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to update a customer.";
    header("Location: ../index.php");
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'driver';

try {
    $conn = new mysqli("localhost", "root", "", "team_transport");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
        $customerId = (int) $_POST['id'];

        // Collect & sanitize input
        $customerCompanyName = trim($_POST['customer_company_name'] ?? '');
        $customerInternalHandlerName = trim($_POST['customer_internal_handler_name'] ?? '');
        $customerContactFirstName = trim($_POST['customer_contact_first_name'] ?? '');
        $customerContactLastName = trim($_POST['customer_contact_last_name'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $customerContactAddress = trim($_POST['customer_contact_address'] ?? '');
        $customerContactCity = trim($_POST['customer_contact_city'] ?? '');
        $customerContactStateOrProvince = trim($_POST['customer_contact_state_or_province'] ?? '');
        $customerContactZipOrPostalCode = trim($_POST['customer_contact_zip_or_postal_code'] ?? '');
        $customerContactCountry = trim($_POST['customer_contact_country'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $customerFax = trim($_POST['customer_fax'] ?? '');
        $customerWebsite = trim($_POST['customer_website'] ?? '');

        // ✅ Validate required fields
        if (
            empty($customerCompanyName) || empty($customerInternalHandlerName) ||
            empty($customerContactFirstName) || empty($customerContactLastName) ||
            empty($customerEmail) || empty($customerContactAddress) ||
            empty($customerContactCity) || empty($customerContactStateOrProvince) ||
            empty($customerContactZipOrPostalCode) || empty($customerContactCountry) ||
            empty($customerPhone)
        ) {
            $_SESSION['error'] = "Please fill in all required fields.";
            header("Location: ../views/create_customer_view.php?id=$customerId");
            exit();
        }

        // ✅ Validate email format
        if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format.";
            header("Location: ../views/create_customer_view.php?id=$customerId");
            exit();
        }

        // ✅ Check ownership (non-admin users can only update their own customers)
        if ($userRole !== 'admin') {
            $checkStmt = $conn->prepare("SELECT user_id FROM customers WHERE id = ?");
            $checkStmt->bind_param("i", $customerId);
            $checkStmt->execute();
            $checkStmt->bind_result($ownerId);
            $checkStmt->fetch();
            $checkStmt->close();

            if ($ownerId !== $loggedInUserId) {
                $_SESSION['error'] = "You don't have permission to update this customer.";
                header("Location: ../dashboard.php");
                exit();
            }
        }

        // ✅ Perform update
        $stmt = $conn->prepare("
            UPDATE customers 
            SET 
                customer_company_name=?, 
                customer_internal_handler_name=?, 
                customer_contact_first_name=?, 
                customer_contact_last_name=?, 
                customer_email=?, 
                customer_contact_address=?, 
                customer_contact_city=?, 
                customer_contact_state_or_province=?, 
                customer_contact_zip_or_postal_code=?, 
                customer_contact_country=?, 
                customer_phone=?, 
                customer_fax=?, 
                customer_website=?
            WHERE id=?
        ");

        $stmt->bind_param(
            "sssssssssssssi",
            $customerCompanyName,
            $customerInternalHandlerName,
            $customerContactFirstName,
            $customerContactLastName,
            $customerEmail,
            $customerContactAddress,
            $customerContactCity,
            $customerContactStateOrProvince,
            $customerContactZipOrPostalCode,
            $customerContactCountry,
            $customerPhone,
            $customerFax,
            $customerWebsite,
            $customerId
        );

        if ($stmt->execute()) {
            // ✅ Log update
            $log = $conn->prepare("
                INSERT INTO customer_activity_log (user_id, customer_id, action, details)
                VALUES (?, ?, 'UPDATE', 'Customer details updated')
            ");
            $log->bind_param("ii", $loggedInUserId, $customerId);
            $log->execute();
            $log->close();

            $_SESSION['success'] = "Customer updated successfully!";
            header("Location: ../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Database error: " . $stmt->error;
            header("Location: ../views/create_customer_view.php?id=$customerId");
            exit();
        }

        $stmt->close();
    } else {
        $_SESSION['error'] = "Invalid request.";
        header("Location: ../dashboard.php");
        exit();
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: ../dashboard.php");
    exit();
}
