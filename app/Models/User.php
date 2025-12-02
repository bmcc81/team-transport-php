<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class User
{
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function create(array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO users (username, pwd, email, full_name, role, must_change_password, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['username'],
            $data['pwd'], // bcrypt
            $data['email'],
            $data['full_name'],
            $data['role'],
            $data['must_change_password'],
            $data['created_by']
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();

        // If password reset, update pwd too
        if (!empty($data['pwd'])) {
            $stmt = $pdo->prepare("
                UPDATE users
                SET username=?, pwd=?, email=?, full_name=?, role=?, must_change_password=?
                WHERE id=?
            ");
            return $stmt->execute([
                $data['username'],
                $data['pwd'],
                $data['email'],
                $data['full_name'],
                $data['role'],
                $data['must_change_password'],
                $id
            ]);
        }

        // No password change
        $stmt = $pdo->prepare("
            UPDATE users
            SET username=?, email=?, full_name=?, role=?, must_change_password=?
            WHERE id=?
        ");

        return $stmt->execute([
            $data['username'],
            $data['email'],
            $data['full_name'],
            $data['role'],
            $data['must_change_password'],
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


}
