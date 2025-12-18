<?php

declare(strict_types=1);

namespace App\Models;

use App\Database\Database;
use PDO;

class Load
{
    /**
     * Fetch all loads with customer name + driver name.
     *
     * Supported filters:
     *  - status: 'pending'|'assigned'|'in_transit'|'delivered'
     *  - unassigned: true (assigned_driver_id IS NULL)
     *  - search: ref/load_number/cities/customer name
     *  - assigned_driver_id: int (driver-only list)
     */
    public static function all(array $filters = []): array
    {
        $pdo = Database::pdo();

        $where = [];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "l.load_status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['unassigned'])) {
            $where[] = "l.assigned_driver_id IS NULL";
        }

        if (!empty($filters['assigned_driver_id'])) {
            $where[] = "l.assigned_driver_id = :driver_id";
            $params[':driver_id'] = (int)$filters['assigned_driver_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(
                l.reference_number LIKE :q
                OR l.load_number LIKE :q
                OR l.pickup_city LIKE :q
                OR l.delivery_city LIKE :q
                OR c.name LIKE :q
            )";
            $params[':q'] = '%' . $filters['search'] . '%';
        }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        $sql = "
            SELECT
                l.*,
                c.name AS customer_company_name,
                u.full_name AS driver_name
            FROM loads l
            INNER JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            {$whereSql}
            ORDER BY l.created_at DESC, l.load_id DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Fetch one load by id with customer + driver name.
     */
    public static function find(int $loadId): ?array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT
                l.*,
                c.name AS customer_company_name,
                u.full_name AS driver_name
            FROM loads l
            INNER JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            WHERE l.load_id = :id
            LIMIT 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $loadId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
