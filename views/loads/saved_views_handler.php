<?php
// views/loads/saved_views_handler.php
session_start();
require_once __DIR__ . '/../../services/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = (int) $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'save') {
    $name    = trim($_POST['view_name'] ?? '');
    $filters = json_decode($_POST['current_filters'] ?? '{}', true) ?? [];

    if ($name === '') {
        header("Location: loads_list.php");
        exit;
    }

    unset($filters['page'], $filters['export']); // clean noise
    $json = json_encode($filters);

    $stmt = $conn->prepare("INSERT INTO user_saved_views (user_id, view_name, config_json) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $name, $json);
    $stmt->execute();
    $stmt->close();

    header("Location: loads_list.php");
    exit;
}

if ($action === 'rename') {
    $viewId  = (int) ($_POST['view_id'] ?? 0);
    $newName = trim($_POST['new_name'] ?? '');

    if ($viewId > 0 && $newName !== '') {
        $stmt = $conn->prepare("UPDATE user_saved_views SET view_name = ? WHERE view_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $newName, $viewId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: loads_list.php");
    exit;
}

if ($action === 'delete') {
    $viewId = (int) ($_POST['view_id'] ?? 0);

    if ($viewId > 0) {
        $stmt = $conn->prepare("DELETE FROM user_saved_views WHERE view_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $viewId, $userId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: loads_list.php");
    exit;
}

header("Location: loads_list.php");
exit;
