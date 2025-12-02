<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Customer
{
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT *
            FROM customers
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data, int $userId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO customers (
                customer_company_name,
                customer_contact_first_name,
                customer_contact_last_name,
                customer_contact_phone,
                customer_email,
                customer_address,
                customer_contact_city,
                customer_contact_postal_code,
                notes,
                created_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['company'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['city'],
            $data['postal'],
            $data['notes'],
            $userId
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE customers SET
                customer_company_name = ?,
                customer_contact_first_name = ?,
                customer_contact_last_name = ?,
                customer_contact_phone = ?,
                customer_email = ?,
                customer_address = ?,
                customer_contact_city = ?,
                customer_contact_postal_code = ?,
                notes = ?
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['company'],
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $data['email'],
            $data['address'],
            $data['city'],
            $data['postal'],
            $data['notes'],
            $id
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
