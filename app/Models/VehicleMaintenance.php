<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class VehicleMaintenance
{
    public static function forVehicle(int $vehicleId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT *
            FROM vehicle_maintenance
            WHERE vehicle_id = ?
            ORDER BY scheduled_date DESC, id DESC
        ");
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function upcomingForVehicle(int $vehicleId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT *
            FROM vehicle_maintenance
            WHERE vehicle_id = ?
              AND status = 'planned'
              AND scheduled_date >= CURDATE()
            ORDER BY scheduled_date ASC
        ");
        $stmt->execute([$vehicleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function countDueOrOverdueForVehicle(int $vehicleId): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM vehicle_maintenance
            WHERE vehicle_id = ?
              AND status = 'planned'
              AND scheduled_date <= CURDATE()
        ");
        $stmt->execute([$vehicleId]);
        return (int)$stmt->fetchColumn();
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM vehicle_maintenance WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_maintenance (
                vehicle_id, title, description,
                scheduled_date, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, 'planned', ?, NOW())
        ");

        return $stmt->execute([
            $data['vehicle_id'],
            $data['title'],
            $data['description'],
            $data['scheduled_date'],
            $data['created_by'],
        ]);
    }

    public static function markCompleted(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE vehicle_maintenance
            SET status = 'completed', completed_date = CURDATE()
            WHERE id = ?
        ");
        return $stmt->execute([$id]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM vehicle_maintenance WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
