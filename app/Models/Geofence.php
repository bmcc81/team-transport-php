<?php

namespace App\Models;

use App\Database\Database;
use PDO;

class Geofence
{
    /**
     * Get all geofences (optionally only active).
     */
    public static function all(bool $onlyActive = false): array
    {
        $pdo = Database::pdo();

        $sql = "SELECT * FROM geofences";
        if ($onlyActive) {
            $sql .= " WHERE active = 1";
        }
        $sql .= " ORDER BY name ASC";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map([self::class, 'cast'], $rows);
    }

    /**
     * Get only ACTIVE geofences (used by Live Map).
     */
    public static function active(): array
    {
        return self::all(true);
    }

    /**
     * Find a geofence by ID.
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM geofences WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::cast($row) : null;
    }

    /**
     * MODEL CASTING
     * Convert DB values → usable PHP structures.
     */
    private static function cast(array $row): array
    {
        // Convert numeric values
        if (isset($row['center_lat'])) $row['center_lat'] = $row['center_lat'] !== null ? floatval($row['center_lat']) : null;
        if (isset($row['center_lng'])) $row['center_lng'] = $row['center_lng'] !== null ? floatval($row['center_lng']) : null;
        if (isset($row['radius_m']))   $row['radius_m']   = $row['radius_m'] !== null ? intval($row['radius_m']) : null;

        // Applies to all vehicles / Active → boolean
        $row['applies_to_all_vehicles'] = !empty($row['applies_to_all_vehicles']);
        $row['active'] = !empty($row['active']);

        // Decode polygon JSON into usable array for Leaflet
        if (!empty($row['polygon_points'])) {
            $decoded = json_decode($row['polygon_points'], true);
            $row['polygon_points'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['polygon_points'] = [];
        }

        return $row;
    }

    /**
     * Update a geofence by ID.
     */
    public static function updateById(int $id, array $data): bool
    {
        $pdo = Database::pdo();

        // Handle polygon JSON safely
        if (!empty($data['polygon_points']) && !is_string($data['polygon_points'])) {
            $data['polygon_points'] = json_encode($data['polygon_points'], JSON_UNESCAPED_UNICODE);
        }

        $sql = "
            UPDATE geofences SET
                name = :name,
                description = :description,
                type = :type,
                center_lat = :center_lat,
                center_lng = :center_lng,
                radius_m = :radius_m,
                polygon_points = :polygon_points,
                applies_to_all_vehicles = :applies,
                active = :active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':id'       => $id,
            ':name'     => $data['name'],
            ':description' => $data['description'],
            ':type'     => $data['type'],
            ':center_lat' => $data['center_lat'],
            ':center_lng' => $data['center_lng'],
            ':radius_m'   => $data['radius_m'],
            ':polygon_points' => $data['polygon_points'],
            ':applies'   => $data['applies_to_all_vehicles'],
            ':active'    => $data['active'],
        ]);
    }

    /**
     * Delete a geofence.
     */
    public static function delete(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM geofences WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
