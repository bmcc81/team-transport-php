<?php
session_start();
if (!isset($_SESSION['user_id'])) die("Not logged in");

require_once __DIR__ . '/../../services/config.php';
require_once __DIR__ . '/../../services/load_service.php';

$loadId = (int)($_POST['load_id'] ?? 0);
if ($loadId <= 0) die("Invalid Load ID");

try {
    updateLoad($conn, $loadId, $_POST, $_FILES);

    header("Location: /views/loads/load_view.php?id=" . $loadId);
    exit;

} catch (Exception $e) {
    die("Error updating load: " . $e->getMessage());
}
