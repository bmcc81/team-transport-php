<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Vehicle
{
    /**
     * Get all vehicles
     */
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT *
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a single vehicle by ID
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find vehicle assigned to a particular driver
     */
    public static function forDriver(int $driverId): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE assigned_driver_id = ?");
        $stmt->execute([$driverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Assign a vehicle to a driver
     */
    public static function assignToDriver(int $vehicleId, int $driverId): bool
    {
        $pdo = Database::pdo();

        // Unassign any vehicle currently linked to this driver
        $pdo->prepare("
            UPDATE vehicles 
            SET assigned_driver_id = NULL 
            WHERE assigned_driver_id = ?
        ")->execute([$driverId]);

        // Assign the new vehicle
        $stmt = $pdo->prepare("
            UPDATE vehicles 
            SET assigned_driver_id = ? 
            WHERE id = ?
        ");

        return $stmt->execute([$driverId, $vehicleId]);
    }

    /**
     * Unassign a vehicle from all drivers
     */
    public static function unassign(int $vehicleId): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE vehicles 
            SET assigned_driver_id = NULL 
            WHERE id = ?
        ");

        return $stmt->execute([$vehicleId]);
    }

    /**
     * Create a new vehicle (optional future CRUD)
     */
    public static function create(array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number, make, model, year,
                license_plate, vin, capacity, status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        return $stmt->execute([
            $data['vehicle_number'],
            $data['make'],
            $data['model'],
            $data['year'],
            $data['license_plate'],
            $data['vin'],   // NEW
                $data['capacity'],
            $data['status']
        ]);
    }   

    /**
     * Update vehicle (optional future CRUD)
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles SET
                vehicle_number = ?,
                make = ?,
                model = ?,
                year = ?,
                license_plate = ?,
                vin = ?,
                capacity = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([
            $data['vehicle_number'],
            $data['make'],
            $data['model'],
            $data['year'],
            $data['license_plate'],
            $data['vin'],           // NEW
            $data['capacity'],
            $data['status'],
            $id
        ]);
    }


    /**
     * Delete a vehicle (optional future CRUD)
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public static function updateGPS(int $id, float $lat, float $lng): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET latitude = ?, longitude = ?, updated_at = NOW()
            WHERE id = ?
        ");

        return $stmt->execute([$lat, $lng, $id]);
    }

}
