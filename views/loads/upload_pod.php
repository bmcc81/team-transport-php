<?php
require_once __DIR__ . '/../../services/config.php';
session_start();

if (!isset($_SESSION['id'])) die("Not logged in");

$userId = (int) $_SESSION['id'];
$loadId = (int) $_POST['load_id'];

$file = $_FILES['pod_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['pdf','jpg','jpeg','png'];

if (!in_array($ext, $allowed)) die("Invalid file type");

$dir = "uploads/pod/";
if (!is_dir($dir)) mkdir($dir, 0777, true);

$filename = "POD_{$loadId}_" . time() . "." . $ext;
$path = $dir . $filename;

move_uploaded_file($file['tmp_name'], $path);

$stmt = $conn->prepare("
    INSERT INTO load_documents (load_id, uploaded_by_user_id, document_type, file_path, file_extension)
    VALUES (?, ?, 'pod', ?, ?)
");
$stmt->bind_param("iiss", $loadId, $userId, $path, $ext);
$stmt->execute();

header("Location: load_view.php?id=" . $loadId);
exit;
