<?php
session_start();
if (!isset($_SESSION['user_id'])) die("Not logged in");

require_once __DIR__ . '/../../services/config.php';
require_once __DIR__ . '/../../services/load_service.php';

try {
    $newLoadId = createLoad($conn, $_POST, $_SESSION['user_id'], $_FILES);

    header("Location: /views/loads/load_view.php?id=" . $newLoadId);
    exit;

} catch (Exception $e) {
    die("Error creating load: " . $e->getMessage());
}
