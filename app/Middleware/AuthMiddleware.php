<?php
namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Require the user to be logged in.
     */
    public static function requireAuth(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash_error'] = "Please login to continue.";
            header("Location: /login");
            exit;
        }
    }

    /**
     * Require a specific role.
     *
     * Example:
     * AuthMiddleware::requireRole("admin");
     */
    public static function requireRole(string $role): void
    {
        self::requireAuth();

        $currentRole = $_SESSION['role'] ?? null;

        if ($currentRole !== $role) {
            http_response_code(403);
            $_SESSION['flash_error'] = "You do not have permission to access this section.";
            header("Location: /dashboard");
            exit;
        }
    }

    /**
     * Require one of multiple allowed roles.
     * Example: requireAnyRole(['admin','dispatcher']);
     */
    public static function requireAnyRole(array $roles): void
    {
        self::requireAuth();

        $currentRole = $_SESSION['role'] ?? null;

        if (!in_array($currentRole, $roles, true)) {
            http_response_code(403);
            $_SESSION['flash_error'] = "Access denied.";
            header("Location: /dashboard");
            exit;
        }
    }
}
