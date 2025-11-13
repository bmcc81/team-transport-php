<?php
session_start();

// ✅ Access control: must be logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to delete a customer.";
    header("Location: ../index.php");
    exit();
}

$host = "localhost";
$user = "root";
$pass = "";
$db   = "team_transport";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// ✅ Ensure POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $customerId = intval($_POST['id']);
    $userRole   = $_SESSION['role'];
    $userId     = $_SESSION['user_id'];

    // 🧠 Role-based restriction
    // Admins can delete any customer, others only their own
    if ($userRole !== 'admin') {
        // Verify ownership
        $check = $conn->prepare("SELECT user_id FROM customers WHERE id = ?");
        $check->bind_param("i", $customerId);
        $check->execute();
        $check->bind_result($ownerId);
        $check->fetch();
        $check->close();

        if ($ownerId !== $userId) {
            $_SESSION['error'] = "You can only delete your own customers.";
            header("Location: ../dashboard.php");
            exit();
        }
    }

    // ✅ Delete the customer safely
    $stmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->bind_param("i", $customerId);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Customer deleted successfully.";
    } else {
        $_SESSION['error'] = "Error deleting customer: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: ../dashboard.php");
    exit();
}

// If direct access without POST
$_SESSION['error'] = "Invalid request.";
header("Location: ../dashboard.php");
exit