<?php
require_once __DIR__ . '/../services/config.php';
session_start();

// If a user is already logged in, prevent them from seeing the login form again
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['pwd'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password.";
        header("Location: ../index.php");
        exit();
    }

    // ✅ Fetch user with PDO
    $stmt = $pdo->prepare("SELECT id, username, pwd, role FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['pwd'])) {
            // ✅ Store user info in session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            header("Location: ../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password.";
        }
    } else {
        $_SESSION['error'] = "Username not found.";
    }

    header("Location: ../index.php");
    exit();
}
