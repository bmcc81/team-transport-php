<?php
namespace App\Models;

use App\Database\Database;

class Load
{
    public static function all(array $filters = []): array
    {
        $pdo = Database::pdo();

        $sql = "SELECT l.*, c.customer_company_name, u.full_name AS driver_name
                FROM loads l
                JOIN customers c ON l.customer_id = c.id
                LEFT JOIN users u ON l.driver_id = u.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND l.load_status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (
                l.reference_number LIKE :search
                OR c.customer_company_name LIKE :search
                OR l.pickup_city LIKE :search
                OR l.delivery_city LIKE :search
            )";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll() ?: [];
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT l.*, c.customer_company_name, u.full_name AS driver_name
            FROM loads l
            JOIN customers c ON l.customer_id = c.id
            LEFT JOIN users u ON l.driver_id = u.id
            WHERE l.load_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO loads (
                customer_id,
                created_by_user_id,
                driver_id,
                reference_number,
                description,
                pickup_contact_name,
                pickup_address,
                pickup_city,
                pickup_postal_code,
                pickup_date,
                delivery_contact_name,
                delivery_address,
                delivery_city,
                delivery_postal_code,
                delivery_date,
                total_weight_kg,
                rate_amount,
                rate_currency,
                load_status,
                notes
            ) VALUES (
                :customer_id,
                :created_by_user_id,
                :driver_id,
                :reference_number,
                :description,
                :pickup_contact_name,
                :pickup_address,
                :pickup_city,
                :pickup_postal_code,
                :pickup_date,
                :delivery_contact_name,
                :delivery_address,
                :delivery_city,
                :delivery_postal_code,
                :delivery_date,
                :total_weight_kg,
                :rate_amount,
                :rate_currency,
                :load_status,
                :notes
            )
        ");

        $stmt->execute([
            ':customer_id'           => $data['customer_id'],
            ':created_by_user_id'    => $data['created_by_user_id'],
            ':driver_id'             => $data['driver_id'] ?? null,
            ':reference_number'      => $data['reference_number'],
            ':description'           => $data['description'],
            ':pickup_contact_name'   => $data['pickup_contact_name'],
            ':pickup_address'        => $data['pickup_address'],
            ':pickup_city'           => $data['pickup_city'],
            ':pickup_postal_code'    => $data['pickup_postal_code'],
            ':pickup_date'           => $data['pickup_date'],
            ':delivery_contact_name' => $data['delivery_contact_name'],
            ':delivery_address'      => $data['delivery_address'],
            ':delivery_city'         => $data['delivery_city'],
            ':delivery_postal_code'  => $data['delivery_postal_code'],
            ':delivery_date'         => $data['delivery_date'],
            ':total_weight_kg'       => $data['total_weight_kg'],
            ':rate_amount'           => $data['rate_amount'],
            ':rate_currency'         => $data['rate_currency'],
            ':load_status'           => $data['load_status'],
            ':notes'                 => $data['notes'],
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE loads SET
                customer_id = :customer_id,
                driver_id = :driver_id,
                reference_number = :reference_number,
                description = :description,
                pickup_contact_name = :pickup_contact_name,
                pickup_address = :pickup_address,
                pickup_city = :pickup_city,
                pickup_postal_code = :pickup_postal_code,
                pickup_date = :pickup_date,
                delivery_contact_name = :delivery_contact_name,
                delivery_address = :delivery_address,
                delivery_city = :delivery_city,
                delivery_postal_code = :delivery_postal_code,
                delivery_date = :delivery_date,
                total_weight_kg = :total_weight_kg,
                rate_amount = :rate_amount,
                rate_currency = :rate_currency,
                load_status = :load_status,
                notes = :notes
            WHERE load_id = :id
        ");

        $stmt->execute([
            ':id'                    => $id,
            ':customer_id'           => $data['customer_id'],
            ':driver_id'             => $data['driver_id'] ?? null,
            ':reference_number'      => $data['reference_number'],
            ':description'           => $data['description'],
            ':pickup_contact_name'   => $data['pickup_contact_name'],
            ':pickup_address'        => $data['pickup_address'],
            ':pickup_city'           => $data['pickup_city'],
            ':pickup_postal_code'    => $data['pickup_postal_code'],
            ':pickup_date'           => $data['pickup_date'],
            ':delivery_contact_name' => $data['delivery_contact_name'],
            ':delivery_address'      => $data['delivery_address'],
            ':delivery_city'         => $data['delivery_city'],
            ':delivery_postal_code'  => $data['delivery_postal_code'],
            ':delivery_date'         => $data['delivery_date'],
            ':total_weight_kg'       => $data['total_weight_kg'],
            ':rate_amount'           => $data['rate_amount'],
            ':rate_currency'         => $data['rate_currency'],
            ':load_status'           => $data['load_status'],
            ':notes'                 => $data['notes'],
        ]);
    }

    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE loads
            SET load_status = :status
            WHERE load_id = :id
        ");
        $stmt->execute([
            ':status' => $status,
            ':id'     => $id,
        ]);
    }
}
