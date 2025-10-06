<?php
session_start();

if (!isset($_SESSION['username'])) {
    die("You must be logged in.");
}

if (!isset($_GET['id'])) {
    die("No ID provided.");
}

$id = (int) $_GET['id'];

// Simply redirect with id in query string
header("Location: ../views/create_customer_view.php?id=" . $id);
exit;