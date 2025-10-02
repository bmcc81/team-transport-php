<?php

session_start();

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create a customer.");
}

$loggedInUserId = $_SESSION['user_id'];

$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "team_transport";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$username = "";
$email = "";
$plainPassword = ""; 

$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'], $_POST['email'], $_POST['create_password'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $plainPassword = $_POST['create_password'];
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    } else {
        $_SESSION['error'] = "Please fill in all required fields";
        // die("Missing required fields.");
    }
    // Optional: check if email exists
    // $checkStmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $_SESSION['error'] = "Error: User Name already exists.";
        header("Location: ../views/create_user_by_admin_view.php");
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, email, pwd) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            $_SESSION['success'] = "User created successfully!";
            header("Location: ../dashboard.php");
        } else {
            $_SESSION['error'] = "Error: " . $stmt->error;
            header("Location: ../views/create_user_by_admin_view.php");
        }
        $stmt->close();
    }

    $checkStmt->close();
    $conn->close();
}
