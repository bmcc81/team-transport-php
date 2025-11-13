<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to manage customers.";
    header("Location: ../index.php");
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid customer ID.";
    header("Location: ../dashboard.php");
    exit();
}

$customerId = (int) $_GET['id'];

// Redirect to the edit form
header("Location: ../views/create_customer_view.php?id=" . $customerId);
exit();
