<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Vehicle
{
public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT 
                id,
                vehicle_number,
                make,
                model,
                year,
                license_plate,
                vin,
                capacity,
                status,
                maintenance_status,
                assigned_driver_id,
                created_at,
                updated_at,
                latitude,
                longitude,
                last_lat,
                last_lng,
                last_telemetry_at
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function find($id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            SELECT 
                id,
                vehicle_number,
                make,
                model,
                year,
                license_plate,
                vin,
                capacity,
                status,
                maintenance_status,
                assigned_driver_id,
                created_at,
                updated_at,
                latitude,
                longitude,
                last_lat,
                last_lng,
                last_telemetry_at
            FROM vehicles
            WHERE id = ?
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function allActive(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate, status
            FROM vehicles
            WHERE status = 'active'
            ORDER BY vehicle_number ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function allSimple(): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}
