<?php

declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;
use DateTimeImmutable;
use DateTimeZone;

final class TelemetryService
{
    /**
     * Returns one row per vehicle from telemetry_latest.
     * Output includes both DB-native keys (lat/lng/speed_kph/heading/recorded_at)
     * and WS/frontend-friendly aliases (latitude/longitude/speed/heading_deg/timestamp).
     */
    public static function latestForAll(): array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT
                v.id AS vehicle_id,

                -- Latest telemetry (nullable if none yet)
                t.recorded_at,
                t.lat,
                t.lng,
                t.speed_kph,
                t.heading

            FROM vehicles v
            LEFT JOIN telemetry_latest t
            ON t.vehicle_id = v.id
            ORDER BY v.id
        ";

        $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([self::class, 'normalizeRow'], $rows);
    }

    /**
     * Telemetry history for one vehicle from telemetry_points.
     */
    public static function historyForVehicle(int $vehicleId, int $limit = 300, bool $ascending = true): array
    {
        $pdo = Database::pdo();

        $limit = max(1, min(5000, $limit));
        $order = $ascending ? 'ASC' : 'DESC';

        $stmt = $pdo->prepare("
            SELECT
                vehicle_id,
                recorded_at,
                lat,
                lng,
                speed_kph,
                heading
            FROM telemetry_points
            WHERE vehicle_id = :vid
            ORDER BY recorded_at $order, id $order
            LIMIT $limit
        ");

        $stmt->execute(['vid' => $vehicleId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map([self::class, 'normalizeRow'], $rows);
    }


    /**
     * Optional: if you use the PHP ingest endpoint.
     * Inserts into telemetry_points; DB trigger will maintain telemetry_latest.
     */
    public static function ingest(array $payload): int
    {
        $vehicleId = (int)($payload['vehicle_id'] ?? 0);
        $lat       = $payload['lat'] ?? $payload['latitude'] ?? null;
        $lng       = $payload['lng'] ?? $payload['longitude'] ?? null;

        if ($vehicleId <= 0) {
            throw new \InvalidArgumentException('vehicle_id is required');
        }
        if ($lat === null || $lng === null) {
            throw new \InvalidArgumentException('lat/lng are required');
        }

        $lat = (float)$lat;
        $lng = (float)$lng;

        $speed = $payload['speed_kph'] ?? $payload['speed'] ?? null;
        $heading = $payload['heading'] ?? $payload['heading_deg'] ?? null;

        $speed = ($speed === null || $speed === '') ? null : (float)$speed;
        $heading = ($heading === null || $heading === '') ? null : (float)$heading;

        // If caller provides a timestamp, store it; otherwise let DB default apply.
        $recordedAt = null;
        $ts = $payload['timestamp'] ?? $payload['recorded_at'] ?? null;
        if (is_string($ts) && trim($ts) !== '') {
            try {
                $dt = new DateTimeImmutable($ts);
                // Store as UTC string; TIMESTAMP will store consistently.
                $dtUtc = $dt->setTimezone(new DateTimeZone('UTC'));
                $recordedAt = $dtUtc->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
                // ignore; fall back to DB default
                $recordedAt = null;
            }
        }

        $pdo = Database::pdo();

        if ($recordedAt === null) {
            $sql = "
                INSERT INTO telemetry_points (vehicle_id, lat, lng, speed_kph, heading, raw)
                VALUES (:vid, :lat, :lng, :speed, :heading, :raw)
            ";
            $params = [
                ':vid' => $vehicleId,
                ':lat' => $lat,
                ':lng' => $lng,
                ':speed' => $speed,
                ':heading' => $heading,
                ':raw' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ];
        } else {
            $sql = "
                INSERT INTO telemetry_points (vehicle_id, recorded_at, lat, lng, speed_kph, heading, raw)
                VALUES (:vid, :recorded_at, :lat, :lng, :speed, :heading, :raw)
            ";
            $params = [
                ':vid' => $vehicleId,
                ':recorded_at' => $recordedAt,
                ':lat' => $lat,
                ':lng' => $lng,
                ':speed' => $speed,
                ':heading' => $heading,
                ':raw' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return (int)$pdo->lastInsertId();
    }

    private static function normalizeRow(array $r): array
    {
        $vehicleId = (int)($r['vehicle_id'] ?? 0);

        $lat = isset($r['lat']) ? (float)$r['lat'] : null;
        $lng = isset($r['lng']) ? (float)$r['lng'] : null;

        $speed = array_key_exists('speed_kph', $r) && $r['speed_kph'] !== null ? (float)$r['speed_kph'] : null;
        $heading = array_key_exists('heading', $r) && $r['heading'] !== null ? (float)$r['heading'] : null;

        $recordedAt = $r['recorded_at'] ?? null;
        $recordedAtStr = is_string($recordedAt) ? $recordedAt : (string)$recordedAt;

        return [
            // Vehicle metadata (optional)
            'vehicle_number'     => $r['vehicle_number'] ?? null,
            'license_plate'      => $r['license_plate'] ?? null,
            'make'               => $r['make'] ?? null,
            'model'              => $r['model'] ?? null,
            'year'               => isset($r['year']) ? (int)$r['year'] : null,
            'status'             => $r['status'] ?? null,
            'assigned_driver_id' => isset($r['assigned_driver_id']) ? (int)$r['assigned_driver_id'] : null,

            // DB-native + aliases (your existing block)
            'vehicle_id'   => $vehicleId,
            'recorded_at'  => $recordedAtStr,
            'lat'          => $lat,
            'lng'          => $lng,
            'speed_kph'    => $speed,
            'heading'      => $heading,

            'latitude'     => $lat,
            'longitude'    => $lng,
            'speed'        => $speed,
            'heading_deg'  => $heading,
            'timestamp'    => $recordedAtStr,
        ];
    }
}
