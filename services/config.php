<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables from .env
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// --- Database Configuration --- Defaults are for local development
$DB_HOST = $_ENV['DB_HOST'] ?? 'localhost';
$DB_PORT = $_ENV['DB_PORT'] ?? 3306;
$DB_NAME = $_ENV['DB_DATABASE'] ?? 'team_transport';
$DB_USER = $_ENV['DB_USERNAME'] ?? 'root';
$DB_PASS = $_ENV['DB_PASSWORD'] ?? '';

/**
 * OPTION 1: MySQLi Connection
 */
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($mysqli->connect_error) {
    die("❌ Database connection failed: " . $mysqli->connect_error);
}

/**
 * OPTION 2: PDO Connection (preferred for new features)
 */
try {
    $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => true
    ]);
} catch (PDOException $e) {
    die("❌ PDO connection failed: " . $e->getMessage());
}

$conn = $mysqli; // For backward compatibility

// You can now use either $mysqli or $pdo anywhere by including this file
