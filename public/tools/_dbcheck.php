<?php
declare(strict_types=1);

// Match index.php ordering closely
require __DIR__ . '/../app/Helpers/sanitize.php';
require __DIR__ . '/../app/bootstrap.php';

session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// Autoload (same as index.php)
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) require $file;
});

// Load .env (same as index.php)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
    }
}

use App\Database\Database;

header('Content-Type: text/plain; charset=utf-8');

// Init DB exactly like index.php
Database::init([
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'name' => $_ENV['DB_NAME'] ?? 'team_transport',
    'user' => $_ENV['DB_USER'] ?? 'TEAMUSER',
    'pass' => $_ENV['DB_PASS'] ?? 'TEAM1234',
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
]);

$pdo = Database::pdo();

echo "connected=1\n";
echo "database=" . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
echo "server_version=" . $pdo->query("SELECT @@version")->fetchColumn() . "\n";
echo "port=" . $pdo->query("SELECT @@port")->fetchColumn() . "\n\n";

echo "loads columns:\n";
foreach ($pdo->query("SHOW COLUMNS FROM loads")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . "\n";
}

echo "\nvehicle_maintenance columns:\n";
foreach ($pdo->query("SHOW COLUMNS FROM vehicle_maintenance")->fetchAll(PDO::FETCH_ASSOC) as $c) {
    echo $c['Field'] . "\n";
}

echo "\nprobe select:\n";
echo "load_number sample=" . var_export($pdo->query("SELECT load_number FROM loads LIMIT 1")->fetchColumn(), true) . "\n";
