<?php
session_start();

// Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied: Admins only.";
    header("Location: ../dashboard.php");
    exit();
}

$adminId = $_SESSION['user_id']; // ✅ store who created the new user

$host = "localhost";
$user = "root";
$pass = "";
$db   = "team_transport";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $plainPassword = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'driver';

    if (empty($username) || empty($plainPassword)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header("Location: ../views/create_user_by_admin_view.php");
        exit();
    }

    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "Error: Username already exists.";
        header("Location: ../views/create_user_by_admin_view.php");
    } else {
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        // ✅ Include created_by in insert
       $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, pwd, role, created_by)
                                VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $fullName, $username, $email, $hashedPassword, $role, $adminId);

        if ($stmt->execute()) {
            $_SESSION['success'] = "User '$username' created successfully by admin.";
            header("Location: ../dashboard.php");
        } else {
            $_SESSION['error'] = "Database error: " . $stmt->error;
            header("Location: ../views/create_user_by_admin_view.php");
        }

        $stmt->close();
    }

    $checkStmt->close();
}

$conn->close();
