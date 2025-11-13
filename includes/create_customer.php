<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create a customer.");
}

require_once __DIR__ . '/validation.php';

$loggedInUserId = $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'driver';

// Database connection
$conn = new mysqli("localhost", "root", "", "team_transport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the handler user ID from dropdown
    $user_id = intval($_POST['user_id'] ?? 0);

    // Fetch the handler’s username for customer_internal_handler_name
    $customerInternalHandlerName = '';
    if ($user_id > 0) {
        $handlerStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
        $handlerStmt->bind_param("i", $user_id);
        $handlerStmt->execute();
        $handlerStmt->bind_result($customerInternalHandlerName);
        $handlerStmt->fetch();
        $handlerStmt->close();
    }

    // Sanitize remaining inputs
    $customerCompanyName = trim($_POST['customer_company_name'] ?? '');
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
        header("Location: ../views/create_customer_view.php");
        exit();
    }

    // ✅ Validate email format
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../views/create_customer_view.php");
        exit();
    }

    // ✅ Check for duplicates
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE customer_company_name=? OR customer_email=?");
    $checkStmt->bind_param("ss", $customerCompanyName, $customerEmail);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "Customer with this name or email already exists.";
        $checkStmt->close();
        header("Location: ../views/create_customer_view.php");
        exit();
    }
    $checkStmt->close();

    // ✅ Insert new customer
    $stmt = $conn->prepare("
        INSERT INTO customers (
            user_id,
            customer_company_name,
            customer_internal_handler_name,
            customer_contact_first_name,
            customer_contact_last_name,
            customer_email,
            customer_contact_address,
            customer_contact_city,
            customer_contact_state_or_province,
            customer_contact_country,
            customer_contact_zip_or_postal_code,
            customer_phone,
            customer_fax,
            customer_website
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $_SESSION['error'] = "SQL error: " . $conn->error;
        header("Location: ../views/create_customer_view.php");
        exit();
    }

    $stmt->bind_param(
        "isssssssssssss",
        $user_id,
        $customerCompanyName,
        $customerInternalHandlerName,
        $customerContactFirstName,
        $customerContactLastName,
        $customerEmail,
        $customerContactAddress,
        $customerContactCity,
        $customerContactStateOrProvince,
        $customerContactCountry,
        $customerContactZipOrPostalCode,
        $customerPhone,
        $customerFax,
        $customerWebsite
    );

    if ($stmt->execute()) {
        // ✅ Log the action
        $customerId = $stmt->insert_id;
        $log = $conn->prepare("
            INSERT INTO customer_activity_log (user_id, customer_id, action, details)
            VALUES (?, ?, 'CREATE', 'Customer created successfully')
        ");
        $log->bind_param("ii", $loggedInUserId, $customerId);
        $log->execute();
        $log->close();

        $_SESSION['success'] = "Customer created successfully!";
        header("Location: ../dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Database error: " . $stmt->error;
        $_SESSION['old'] = $_POST;
        header("Location: ../views/create_customer_view.php");
        exit();
    }

    $stmt->close();
    $conn->close();
}
?>
