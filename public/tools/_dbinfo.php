<?php
declare(strict_types=1);

session_start();
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

try {
    // Autoload (same idea as your public/index.php)
    spl_autoload_register(function (string $class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../app/';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;

        $relativeClass = substr($class, strlen($prefix));
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) require $file;
    });

    // Load .env (same logic you showed previously)
    $envFile = __DIR__ . '/../.env';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (!str_contains($line, '=')) continue;
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $_ENV[$k] = $v;
        }
    }

    // Now use your Database class
    $config = [
        'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
        'port' => (int)($_ENV['DB_PORT'] ?? 3306),
        'name' => $_ENV['DB_NAME'] ?? 'team_transport',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? '',
    ];

    \App\Database\Database::init($config);
    $pdo = \App\Database\Database::pdo();

    echo "connected=1\n";
    echo "database=" . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";
    echo "server_version=" . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "\n";
    echo "port=" . $pdo->query("SELECT @@port")->fetchColumn() . "\n";

    echo "\nloads columns:\n";
    foreach ($pdo->query("SHOW COLUMNS FROM loads") as $row) {
        echo $row['Field'] . "\n";
    }

} catch (\Throwable $e) {
    http_response_code(500);
    error_log("DBINFO FAIL: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    echo "DBINFO FAIL:\n" . $e->getMessage() . "\n";
}
