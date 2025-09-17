<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['pwd'] ?? '';
    $email = $_POST['email'] ?? '';


    try {
        require_once 'dbh.inc.php';
    } catch (Exception $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    if (empty($username) || empty($password) || empty($email)) {
        $_SESSION['error'] = "Please fill in all fields";
        header("Location: ../index.php");
        exit;
    }
} else {
    header("Location: ../index.php");
    exit;
}