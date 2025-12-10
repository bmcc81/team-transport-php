<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Geofence
{
    /**
     * Fetch all geofences
     */
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT *
            FROM geofences
            ORDER BY created_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch only active geofences (for map displays)
     */
    public static function active(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("
            SELECT *
            FROM geofences
            WHERE active = 1
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find a geofence by ID
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM geofences WHERE id = ?");
        $stmt->execute([$id]);
        $g = $stmt->fetch(PDO::FETCH_ASSOC);
        return $g ?: null;
    }

    /**
     * Create a geofence
     */
    public static function create(array $data): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO geofences
                (name, description, type, center_lat, center_lng, radius_m, polygon_points,
                 applies_to_all_vehicles, active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['type'],
            $data['center_lat'],
            $data['center_lng'],
            $data['radius_m'],
            $data['polygon_points'],
            $data['applies_to_all_vehicles'] ?? $data['applies_all'] ?? 0,
            $data['active'] ?? 1,
            $data['created_by'] ?? ($_SESSION['user_id'] ?? null),
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Update by ID
     */
    public static function updateById(int $id, array $data): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE geofences
               SET name = ?,
                   description = ?,
                   type = ?,
                   center_lat = ?,
                   center_lng = ?,
                   radius_m = ?,
                   polygon_points = ?,
                   applies_to_all_vehicles = ?,
                   active = ?
             WHERE id = ?
        ");

        return $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['type'],
            $data['center_lat'],
            $data['center_lng'],
            $data['radius_m'],
            $data['polygon_points'],
            $data['applies_to_all_vehicles'] ?? $data['applies_all'] ?? 0,
            $data['active'] ?? 1,
            $id,
        ]);
    }

    /**
     * Delete geofence by ID
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();

        // Remove pivot assignments first
        self::clearVehicleAssignments($id);

        $stmt = $pdo->prepare("DELETE FROM geofences WHERE id = ?");
        return $stmt->execute([$id]);
    }

    /**
     * Get assigned vehicle IDs for this geofence
     */
    public static function vehicleIdsFor(int $geofenceId): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT vehicle_id
              FROM geofence_vehicle
             WHERE geofence_id = ?
        ");
        $stmt->execute([$geofenceId]);

        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'vehicle_id');
    }

    /**
     * Assign vehicles to geofence (append)
     */
    public static function assignVehicles(int $geofenceId, array $vehicleIds): void
    {
        $pdo = Database::pdo();

        if (!$vehicleIds) {
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO geofence_vehicle (geofence_id, vehicle_id)
            VALUES (?, ?)
        ");

        foreach ($vehicleIds as $vid) {
            $stmt->execute([$geofenceId, (int)$vid]);
        }
    }

    /**
     * Remove all vehicle assignments for a geofence
     */
    public static function clearVehicleAssignments(int $geofenceId): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?");
        $stmt->execute([$geofenceId]);
    }
}
