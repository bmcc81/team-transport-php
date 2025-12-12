<?php
namespace App\Models;

use App\Database\Database;
use PDO;

class GeofenceAlert
{
    public static function latest(int $limit = 100, ?int $vehicleId = null, ?int $geofenceId = null): array
    {
        $pdo = Database::pdo();
        $sql = "
            SELECT ga.*, v.vehicle_number, g.name AS geofence_name
            FROM geofence_alerts ga
            JOIN vehicles v ON v.id = ga.vehicle_id
            JOIN geofences g ON g.id = ga.geofence_id
        ";
        $where = [];
        $params = [];

        if ($vehicleId) {
            $where[] = "ga.vehicle_id = :vehicle_id";
            $params[':vehicle_id'] = $vehicleId;
        }
        if ($geofenceId) {
            $where[] = "ga.geofence_id = :geofence_id";
            $params[':geofence_id'] = $geofenceId;
        }

        if ($where) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY ga.occurred_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function log(array $data): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("
            INSERT INTO geofence_alerts
                (vehicle_id, geofence_id, event, occurred_at, latitude, longitude, speed_kph, raw_payload)
            VALUES
                (:vehicle_id, :geofence_id, :event, :occurred_at, :latitude, :longitude, :speed_kph, :raw_payload)
        ");

        return $stmt->execute([
            ':vehicle_id' => $data['vehicle_id'],
            ':geofence_id' => $data['geofence_id'],
            ':event' => $data['event'],
            ':occurred_at' => $data['occurred_at'],
            ':latitude' => $data['latitude'] ?? null,
            ':longitude' => $data['longitude'] ?? null,
            ':speed_kph' => $data['speed_kph'] ?? null,
            ':raw_payload' => isset($data['raw_payload'])
                ? json_encode($data['raw_payload'])
                : null,
        ]);
    }
}
