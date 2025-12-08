<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class Geofence
{
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT * FROM geofences ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function active(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT * FROM geofences WHERE active = 1 ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("SELECT * FROM geofences WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO geofences 
                (name, description, type, center_lat, center_lng, radius_m, polygon_points, 
                 applies_to_all_vehicles, created_by, active)
            VALUES 
                (:name, :description, :type, :center_lat, :center_lng, :radius_m, :polygon_points,
                 :applies_to_all_vehicles, :created_by, :active)
        ");

        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? 'circle',
            ':center_lat' => $data['center_lat'] ?? null,
            ':center_lng' => $data['center_lng'] ?? null,
            ':radius_m'   => $data['radius_m'] ?? null,
            ':polygon_points' => $data['polygon_points'] ?? null,
            ':applies_to_all_vehicles' => $data['applies_to_all_vehicles'] ?? 1,
            ':created_by' => $data['created_by'] ?? null,
            ':active' => $data['active'] ?? 1,
        ]);

        return (int)$pdo->lastInsertId();
    }

    public static function updateById(int $id, array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            UPDATE geofences SET
                name = :name,
                description = :description,
                type = :type,
                center_lat = :center_lat,
                center_lng = :center_lng,
                radius_m = :radius_m,
                polygon_points = :polygon_points,
                applies_to_all_vehicles = :applies_to_all_vehicles,
                active = :active
            WHERE id = :id
        ");

        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':type' => $data['type'] ?? 'circle',
            ':center_lat' => $data['center_lat'] ?? null,
            ':center_lng' => $data['center_lng'] ?? null,
            ':radius_m'   => $data['radius_m'] ?? null,
            ':polygon_points' => $data['polygon_points'] ?? null,
            ':applies_to_all_vehicles' => $data['applies_to_all_vehicles'] ?? 1,
            ':active' => $data['active'] ?? 1,
        ]);
    }

    public static function deleteById(int $id): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM geofences WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
