<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');
error_log("PING start " . date('c'));
echo "PING OK " . date('c') . "\n";
error_log("PING end " . date('c'));