<?php
declare(strict_types=1);

require __DIR__ . '/../app/Helpers/sanitize.php';

// load env + Database::init exactly like index.php
require __DIR__ . '/../app/bootstrap.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require $file;
});

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v;
    }
}

use App\Database\Database;

Database::init([
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'name' => $_ENV['DB_NAME'] ?? 'team_transport',
    'user' => $_ENV['DB_USER'] ?? 'TEAMUSER',
    'pass' => $_ENV['DB_PASS'] ?? 'TEAM1234',
    'port' => (int)($_ENV['DB_PORT'] ?? 3306),
]);

$pdo = Database::pdo();

header('Content-Type: text/plain; charset=utf-8');

echo "DATABASE()=" . $pdo->query("SELECT DATABASE()")->fetchColumn() . PHP_EOL;
echo "@@port=" . $pdo->query("SELECT @@port")->fetchColumn() . PHP_EOL;
echo "@@hostname=" . $pdo->query("SELECT @@hostname")->fetchColumn() . PHP_EOL;

echo PHP_EOL . "SHOW COLUMNS FROM users:" . PHP_EOL;
$stmt = $pdo->query("SHOW COLUMNS FROM users");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
    echo "- {$col['Field']} ({$col['Type']})" . PHP_EOL;
}
