<?php
require_once __DIR__ . '/../services/config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
  $_SESSION['error'] = "Access denied: Admins only.";
  header("Location: ../dashboard.php");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int) $_POST['id'];
  $fullName = trim($_POST['full_name'] ?? '');
  $username = trim($_POST['username'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = trim($_POST['role'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if (empty($fullName) || empty($username) || empty($role)) {
    $_SESSION['error'] = "Full name, username, and role are required.";
    header("Location: ../views/edit_user_by_admin_view.php?id=$id");
    exit();
  }

  if (!empty($password)) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, pwd=?, role=? WHERE id=?");
    $stmt->bind_param("sssssi", $fullName, $username, $email, $hashedPassword, $role, $id);
  } else {
    $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $fullName, $username, $email, $role, $id);
  }

  if ($stmt->execute()) {
    $_SESSION['success'] = "User updated successfully.";
  } else {
    $_SESSION['error'] = "Database error: " . $stmt->error;
  }

  $stmt->close();
  $conn->close();

  header("Location: ../dashboard.php");
  exit();
}
