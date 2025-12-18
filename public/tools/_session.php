<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=utf-8');

$ts = date('c');
error_log("SESSION test start $ts");

session_start();
$_SESSION['__ping'] = $ts;
session_write_close();

echo "SESSION OK $ts\n";
error_log("SESSION test end $ts");
