<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create a customer.");
}

$loggedInUserId = $_SESSION['user_id'];

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "team_transport");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    // Validate required fields
    if (
        empty($customerCompanyName) || empty($customerInternalHandlerName) ||
        empty($customerContactFirstName) || empty($customerContactLastName) ||
        empty($customerEmail) || empty($customerContactAddress) ||
        empty($customerContactCity) || empty($customerContactStateOrProvince) ||
        empty($customerContactZipOrPostalCode) || empty($customerContactCountry) ||
        empty($customerPhone)
    ) {
        $_SESSION['error'] = "Please fill in all required fields";
        header(header: "Location: ../create_customer_view.php");
        exit;
    }

    // Validate email
    if (!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: ../views/create_customer_view.php");
        exit;
    }

    // Check for duplicates
    $checkStmt = $conn->prepare("SELECT id FROM customers WHERE customer_company_name=? OR customer_email=?");
    $checkStmt->bind_param("ss", $customerCompanyName, $customerEmail);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "Customer with this name or email already exists.";
        header("Location: ../views/create_customer_view.php");
        exit;
    } else {
        $stmt = $conn->prepare("
            INSERT INTO customers (
                customer_company_name, customer_internal_handler_name, customer_contact_first_name,
                customer_contact_last_name, customer_email, customer_contact_address, customer_contact_city,
                customer_contact_state_or_province, customer_contact_zip_or_postal_code, customer_contact_country,
                customer_phone, customer_fax, customer_website, user_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
            $loggedInUserId
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Customer created successfully!";
            header("Location: ../dashboard.php");
            exit;
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
            header("Location: ../views/create_customer_view.php");
            exit;
        }
    }
}
