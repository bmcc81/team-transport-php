<?php
declare(strict_types=1);

$path = 'C:\\xampp\\php\\logs\\php_error_log';

ini_set('log_errors', '1');
ini_set('error_log', $path);

error_log("WEB error_log() test " . date('c'));
trigger_error("WEB trigger_error() test", E_USER_WARNING);

echo "done\n";
echo "error_log=" . ini_get('error_log') . "\n";
echo "log_errors=" . ini_get('log_errors') . "\n";
