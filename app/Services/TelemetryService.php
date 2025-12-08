<?php
namespace App\Services;

use App\Database\Database;
use PDO;

class TelemetryService
{
    /**
     * Fetch the latest GPS point for each vehicle.
     */
    public static function latestForAll(): array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT 
                v.id,
                v.vehicle_number,
                v.make,
                v.model,
                v.status,
                gps.latitude,
                gps.longitude,
                gps.speed_kmh,
                gps.heading,
                gps.recorded_at
            FROM vehicles v
            LEFT JOIN (
                SELECT g.*
                FROM vehicle_gps g
                INNER JOIN (
                    SELECT vehicle_id, MAX(id) AS max_id
                    FROM vehicle_gps
                    GROUP BY vehicle_id
                ) x ON g.id = x.max_id
            ) gps ON gps.vehicle_id = v.id
            ORDER BY v.vehicle_number ASC
        ";


        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch telemetry history for a single vehicle.
     * Last 100 points by default.
     */
    public static function history(int $vehicleId, int $limit = 100): array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT
                vehicle_id,
                latitude,
                longitude,
                speed_kmh,
                heading,
                recorded_at
            FROM vehicle_gps
            WHERE vehicle_id = :id
            ORDER BY recorded_at DESC
            LIMIT :limit
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':id', $vehicleId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert a telemetry point from API ingestion.
     */
    public static function ingest(array $payload): bool
    {
        if (!isset($payload['vehicle_id'], $payload['lat'], $payload['lng'])) {
            return false;
        }

        $vehicleId = (int)$payload['vehicle_id'];
        $lat = (float)$payload['lat'];
        $lng = (float)$payload['lng'];
        $speed = $payload['speed'] ?? null;
        $heading = $payload['heading'] ?? null;

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_gps (vehicle_id, latitude, longitude, speed_kmh, heading, recorded_at)
            VALUES (:vehicle_id, :lat, :lng, :speed, :heading, NOW())
        ");

        return $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':lat' => $lat,
            ':lng' => $lng,
            ':speed' => $speed,
            ':heading' => $heading,
        ]);
    }
}
