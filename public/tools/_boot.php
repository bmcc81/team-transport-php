<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

error_log("BOOT test start " . date('c'));

// Include whatever your real entrypoint includes.
// If you have an autoloader/bootstrap file, include that.
// Example guesses (adjust to your project):
require __DIR__ . '/../app/bootstrap.php'; // change if needed

echo "BOOT OK " . date('c') . "\n";
error_log("BOOT test end " . date('c'));
