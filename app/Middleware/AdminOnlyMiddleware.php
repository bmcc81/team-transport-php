<?php

namespace App\Middleware;

use App\Support\Auth;

class AdminOnlyMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        $user = Auth::user();

        // Not authenticated
        if (!$user) {
            http_response_code(403);
            echo "Forbidden: authentication required.";
            exit;
        }

        // Only allow admin role
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo "Forbidden: admin access only.";
            exit;
        }

        return $next($request);
    }
}
