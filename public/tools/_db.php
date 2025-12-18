<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

$ts = date('c');
error_log("DB test start $ts");

$dsn  = "mysql:host=127.0.0.1;port=3306;dbname=team_transport;charset=utf8mb4";
$user = "root";
$pass = "root123";

/**
 * Critical: force a short connect timeout so “DB down/blocked” doesn’t hang PHP for 60s.
 */
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_TIMEOUT => 3, // seconds
];

$pdo = new PDO($dsn, $user, $pass, $options);
$ok = $pdo->query("SELECT 1")->fetchColumn();

echo "DB OK $ts (SELECT 1 => $ok)\n";
error_log("DB test end $ts");
