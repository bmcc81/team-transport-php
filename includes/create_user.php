<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";
$user = "root";
$pass = ""; 
$db   = "team_transport";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$username = "tom";
$email = "tom@gmail.com";
$plainPassword = "tom123"; 

$hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

// Optional: check if email exists
// $checkStmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$checkStmt = $conn->prepare("SELECT id FROM users WHERE username=?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    echo "User Name already exists.";
} else {
    $stmt = $conn->prepare("INSERT INTO users (username, email, pwd) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    if ($stmt->execute()) {
        echo "User created successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

$checkStmt->close();
$conn->close();