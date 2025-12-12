<?php

namespace App\Support;

use App\Database\Database;
use PDO;

class Auth
{
    /**
     * Check if a user is currently authenticated.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
    }

    /**
     * Get the current authenticated user array (or null).
     */
    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    /**
     * Get the current authenticated user ID, or null.
     */
    public static function id(): ?int
    {
        return self::check() ? (int)$_SESSION['user']['id'] : null;
    }

    /**
     * Attempt to log in using an email or username and password.
     *
     * @param string $identifier  Email or username
     * @param string $password    Plain-text password from form
     */
    public static function attempt(string $identifier, string $password): bool
    {
        $pdo = Database::pdo();

        // Adjust this query to match your users table
        $stmt = $pdo->prepare("
            SELECT *
            FROM users
            WHERE email = :identifier
               OR username = :identifier
            LIMIT 1
        ");

        $stmt->execute(['identifier' => $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        // Assuming hashed password is stored in 'pwd' column
        if (!password_verify($password, $user['pwd'])) {
            return false;
        }

        // Optional: if using password_needs_rehash, you can update hash here

        // Successful login → bind minimal user data into session
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'username'  => $user['username'] ?? null,
            'email'     => $user['email'] ?? null,
            'full_name' => $user['full_name'] ?? null,
            'role'      => $user['role'] ?? null,
        ];

        // Regenerate session ID to prevent fixation
        session_regenerate_id(true);

        return true;
    }

    /**
     * Log the current user out.
     */
    public static function logout(): void
    {
        unset($_SESSION['user']);
        session_regenerate_id(true);
    }
}

