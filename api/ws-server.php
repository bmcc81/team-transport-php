<?php
require 'vendor/autoload.php';

$server = new WebSocket\Server('0.0.0.0', 9501);

$server->on('message', function($client, $message) use ($server) {
    foreach ($server->clients as $c) {
        $c->send($message);
    }
});

$server->start();
