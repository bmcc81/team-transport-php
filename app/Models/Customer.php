<?php

namespace App\Models;

use App\Database\Database;
use PDO;

class Customer
{
    public static function all(): array
    {
        $pdo = Database::pdo();

        return $pdo->query("
            SELECT
                id,
                name,
                first_name,
                last_name,
                phone,
                email,
                address,
                city,
                postal_code,
                notes,
                created_at,

                -- View-friendly aliases:
                name AS company,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', first_name, last_name)), ''),
                    phone,
                    ''
                ) AS contact,
                postal_code AS postal
            FROM customers
            ORDER BY name ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT
                id,
                name,
                first_name,
                last_name,
                phone,
                email,
                address,
                city,
                postal_code,
                notes,
                created_at,

                name AS company,
                COALESCE(
                    NULLIF(TRIM(CONCAT_WS(' ', first_name, last_name)), ''),
                    phone,
                    ''
                ) AS contact,
                postal_code AS postal
            FROM customers
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public static function create(array $data, ?int $createdByUserId = null): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO customers (name, first_name, last_name, email, phone, address, city, postal_code, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'] ?? '',
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['postal_code'] ?? null,
            $data['notes'] ?? null,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE customers
            SET
                name = ?,
                first_name = ?,
                last_name = ?,
                email = ?,
                phone = ?,
                address = ?,
                city = ?,
                postal_code = ?,
                notes = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $data['name'] ?? '',
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['postal_code'] ?? null,
            $data['notes'] ?? null,
            $id
        ]);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);
    }
}
