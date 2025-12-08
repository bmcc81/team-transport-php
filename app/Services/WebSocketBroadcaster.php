<?php
declare(strict_types=1);

namespace App\Services;

class WebSocketBroadcaster
{
    public static function send(string $payload): void
    {
        try {
            $sock = fsockopen("127.0.0.1", 9501, $errno, $errstr, 1);

            if (!$sock) {
                error_log("WebSocketBroadcaster connection failed: $errstr ($errno)");
                return;
            }

            fwrite($sock, $payload);
            fclose($sock);
        } catch (\Throwable $e) {
            error_log("WebSocketBroadcaster error: " . $e->getMessage());
        }
    }
}
