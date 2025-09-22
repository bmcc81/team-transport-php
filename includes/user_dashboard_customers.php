<?php

    if (!isset($_SESSION['user_id'])) {
        die("You must be logged in to create a customer.");
    }

    $loggedInUserId = $_SESSION['username'];

    // Connect to MySQL
    $conn = new mysqli("localhost", "root", "", "team_transport");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Use prepared statement safely
    $query = "SELECT * FROM customers WHERE customer_internal_handler_name = ?";
    $stmt = $conn->prepare($query);

    // Bind parameter: 'i' for integer user ID, 's' for string
    $stmt->bind_param("s", $loggedInUserId);

    $stmt->execute();
    $result = $stmt->get_result();

    // Store results in an array
    $customers = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $conn->close();
?>
