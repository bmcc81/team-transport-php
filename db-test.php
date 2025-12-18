<?php
$pdo = new PDO(
    'mysql:host=127.0.0.1;port=3306;dbname=team_transport;charset=utf8mb4',
    'teamuser',
    'TEAM1234',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo 'APP DB OK';