<?php
namespace App\Middleware;

class DriverOnlyMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        if (!isset($_SESSION['user'])) {
            header("Location: /login");
            exit;
        }

        if ($_SESSION['user']['role'] !== 'driver') {
            http_response_code(403);
            echo "<h1>403 Forbidden (Driver Only)</h1>";
            exit;
        }

        return $next($request);
    }
}
