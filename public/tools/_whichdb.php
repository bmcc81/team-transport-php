<?php
declare(strict_types=1);

require __DIR__ . '/index.php'; // if this boots your app + Database::init()

use App\Database\Database;

$pdo = Database::pdo();

header('Content-Type: text/plain; charset=utf-8');

echo "DATABASE()=" . $pdo->query("SELECT DATABASE()")->fetchColumn() . PHP_EOL;
echo "@@port=" . $pdo->query("SELECT @@port")->fetchColumn() . PHP_EOL;
echo "@@hostname=" . $pdo->query("SELECT @@hostname")->fetchColumn() . PHP_EOL;
echo "@@version=" . $pdo->query("SELECT @@version")->fetchColumn() . PHP_EOL;
