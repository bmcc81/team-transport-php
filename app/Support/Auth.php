<?php

namespace App\Support;

use App\Database\Database;
use PDO;

final class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return self::check() ? $_SESSION['user'] : null;
    }

    public static function attempt(string $identifier, string $password): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT id, username, email, full_name, role, pwd
            FROM users
            WHERE email = :id OR username = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $identifier]); // <- no colon

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['pwd'])) {
            return false;
        }

        // Optional: upgrade hash if needed
        if (password_needs_rehash($user['pwd'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $u = $pdo->prepare("UPDATE users SET pwd = :pwd WHERE id = :id");
            $u->execute([':pwd' => $newHash, ':id' => (int)$user['id']]);
        }

        // Store minimal user payload in session
        $_SESSION['user'] = [
            'id'        => (int)$user['id'],
            'username'  => $user['username'],
            'email'     => $user['email'],
            'full_name' => $user['full_name'],
            'role'      => $user['role'],
        ];

        // Hygiene
        session_regenerate_id(true);

        return true;
    }

    public static function logout(): void
    {
        unset($_SESSION['user']);

        // Optional: full session wipe
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
