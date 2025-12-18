<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

final class TelemetryService
{
    /**
     * Latest GPS point per vehicle (one row per vehicle).
     * Returns: vehicle_id, lat, lng, speed_kph, heading_deg, source, recorded_at
     */
    public static function latestForAll(): array
    {
        $pdo = Database::pdo();

        // Latest row per vehicle_id using a derived table on MAX(recorded_at)
        $sql = "
            SELECT g.*
            FROM vehicle_gps g
            INNER JOIN (
                SELECT vehicle_id, MAX(recorded_at) AS max_recorded_at
                FROM vehicle_gps
                GROUP BY vehicle_id
            ) x
              ON x.vehicle_id = g.vehicle_id
             AND x.max_recorded_at = g.recorded_at
            ORDER BY g.vehicle_id ASC
        ";

        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Latest GPS point for a single vehicle.
     */
    public static function latestForVehicle(int $vehicleId): ?array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT *
            FROM vehicle_gps
            WHERE vehicle_id = :vehicle_id
            ORDER BY recorded_at DESC
            LIMIT 1
        ");
        $stmt->execute(['vehicle_id' => $vehicleId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * History for a vehicle.
     * If $limit is provided, returns most recent N points (descending).
     * If you need ascending for map trails, set $ascending=true.
     */
    public static function historyForVehicle(int $vehicleId, int $limit = 300, bool $ascending = true): array
    {
        $pdo = Database::pdo();

        $order = $ascending ? "ASC" : "DESC";
        $stmt = $pdo->prepare("
            SELECT *
            FROM vehicle_gps
            WHERE vehicle_id = :vehicle_id
            ORDER BY recorded_at {$order}
            LIMIT :lim
        ");
        $stmt->bindValue(':vehicle_id', $vehicleId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Insert a new GPS point.
     * Accepts: vehicle_id, lat, lng, speed_kph?, heading_deg?, source?
     */
    public static function ingest(array $payload): int
    {
        $pdo = Database::pdo();

        $vehicleId  = (int)($payload['vehicle_id'] ?? 0);
        $lat        = $payload['lat'] ?? null;
        $lng        = $payload['lng'] ?? null;
        $speedKph   = $payload['speed_kph'] ?? null;
        $headingDeg = $payload['heading_deg'] ?? null;
        $source     = (string)($payload['source'] ?? 'api');

        if ($vehicleId <= 0) {
            throw new \InvalidArgumentException('vehicle_id is required.');
        }
        if ($lat === null || $lng === null) {
            throw new \InvalidArgumentException('lat and lng are required.');
        }

        // Normalize/validate numbers
        $lat = (float)$lat;
        $lng = (float)$lng;

        // Clamp to valid ranges (optional but recommended)
        if ($lat < -90 || $lat > 90) {
            throw new \InvalidArgumentException('lat out of range.');
        }
        if ($lng < -180 || $lng > 180) {
            throw new \InvalidArgumentException('lng out of range.');
        }

        // Ensure source is valid for your ENUM
        $allowedSources = ['manual', 'device', 'sim', 'api'];
        if (!in_array($source, $allowedSources, true)) {
            $source = 'api';
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_gps (vehicle_id, lat, lng, speed_kph, heading_deg, source, recorded_at)
            VALUES (:vehicle_id, :lat, :lng, :speed_kph, :heading_deg, :source, NOW())
        ");
        $stmt->execute([
            'vehicle_id'  => $vehicleId,
            'lat'         => $lat,
            'lng'         => $lng,
            'speed_kph'   => ($speedKph === '' || $speedKph === null) ? null : (float)$speedKph,
            'heading_deg' => ($headingDeg === '' || $headingDeg === null) ? null : (int)$headingDeg,
            'source'      => $source,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
