<?php
session_start();

// If a user is already logged in, prevent them from seeing the login form again
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "team_transport";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username']);
    $password = $_POST['pwd'];

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password.";
        header("Location: ../index.php");
        exit();
    }

    // Fetch role too
    $stmt = $conn->prepare("SELECT id, username, pwd, role FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $user, $hashedPwd, $role);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashedPwd)) {
            // ✅ Store all user info in session
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $user;
            $_SESSION['role'] = $role;

            header("Location: ../dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Incorrect password.";
            header("Location: ../index.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "Username not found.";
        header("Location: ../index.php");
        exit();
    }

    $stmt->close();
}

$conn->close();
