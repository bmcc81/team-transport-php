<?php

namespace App\Middleware;

use App\Support\Auth;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Public routes
        if (in_array($uri, ['/login', '/logout'])) {
            return $next($request);
        }

        // If not authenticated → redirect to login
        if (!Auth::check()) {
            header("Location: /login");
            exit;
        }

        return $next($request);
    }
}
