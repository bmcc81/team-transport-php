<?php
require_once __DIR__ . '/../services/config.php';

// admin_reset_password.php
if (php_sapi_name() !== 'cli') {
    echo "Run from CLI only.\n";
    exit(1);
}

$argv_count = $argc ?? count($argv);
if ($argc < 3) {
    echo "Usage: php admin_reset_password.php <username> <temporary_password>\n";
    exit(1);
}

$username = $argv[1];
$newPlain = $argv[2];

$hash = password_hash($newPlain, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET pwd = ?, must_change_password = 1 WHERE username = ?");
$stmt->bind_param('ss', $hash, $username);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Password updated for user '{$username}'. Temporary password: {$newPlain}\n";
} else {
    echo "No rows updated — check username '{$username}'.\n";
}

$stmt->close();
$conn->close();
