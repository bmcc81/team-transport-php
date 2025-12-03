<?php
namespace App\Models;

use App\Database\Database;
use PDO;
use DateTimeImmutable;

class VehicleGpsHistory
{
    /**
     * Log one GPS point for a vehicle.
     */
    public static function log(
        int $vehicleId,
        float $latitude,
        float $longitude,
        ?string $createdAt = null
    ): bool {
        $pdo = Database::pdo();

        if ($createdAt === null) {
            $stmt = $pdo->prepare("
                INSERT INTO vehicle_gps_history (vehicle_id, latitude, longitude)
                VALUES (:vehicle_id, :lat, :lng)
            ");
            return $stmt->execute([
                ':vehicle_id' => $vehicleId,
                ':lat'        => $latitude,
                ':lng'        => $longitude,
            ]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_gps_history (vehicle_id, latitude, longitude, created_at)
            VALUES (:vehicle_id, :lat, :lng, :created_at)
        ");
        return $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':lat'        => $latitude,
            ':lng'        => $longitude,
            ':created_at' => $createdAt,
        ]);
    }

    /**
     * Return trip points + summary metrics for a given vehicle + date.
     *
     * Returns:
     * [
     *   'points'  => [...],
     *   'summary' => [
     *       'total_distance_km'      => float,
     *       'total_duration_minutes' => float,
     *       'avg_speed_kmh'          => float,
     *       'max_speed_kmh'          => float,
     *       'point_count'            => int,
     *   ],
     * ]
     */
    public static function getTripWithMetrics(int $vehicleId, string $date): array
    {
        $pdo = Database::pdo();

        $start = $date . ' 00:00:00';
        $end   = $date . ' 23:59:59';

        $stmt = $pdo->prepare("
            SELECT latitude, longitude, created_at
            FROM vehicle_gps_history
            WHERE vehicle_id = :vehicle_id
              AND created_at BETWEEN :start AND :end
            ORDER BY created_at ASC
        ");
        $stmt->execute([
            ':vehicle_id' => $vehicleId,
            ':start'      => $start,
            ':end'        => $end,
        ]);

        $points = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = self::buildSummary($points);

        return [
            'points'  => $points,
            'summary' => $summary,
        ];
    }

    /**
     * Build distance / speed / duration summary from GPS points.
     *
     * @param array<array{latitude:string, longitude:string, created_at:string}> $points
     */
    private static function buildSummary(array $points): array
    {
        $count = count($points);

        if ($count < 2) {
            return [
                'total_distance_km'      => 0.0,
                'total_duration_minutes' => 0.0,
                'avg_speed_kmh'          => 0.0,
                'max_speed_kmh'          => 0.0,
                'point_count'            => $count,
            ];
        }

        $totalDistanceKm = 0.0;
        $maxSpeedKmh     = 0.0;

        /** @var DateTimeImmutable|null $firstTime */
        $firstTime = null;
        /** @var DateTimeImmutable|null $lastTime */
        $lastTime  = null;

        for ($i = 1; $i < $count; $i++) {
            $p1 = $points[$i - 1];
            $p2 = $points[$i];

            $lat1 = (float)$p1['latitude'];
            $lon1 = (float)$p1['longitude'];
            $lat2 = (float)$p2['latitude'];
            $lon2 = (float)$p2['longitude'];

            if (!isset($p1['created_at'], $p2['created_at'])) {
                continue;
            }

            $t1 = new DateTimeImmutable($p1['created_at']);
            $t2 = new DateTimeImmutable($p2['created_at']);

            if ($firstTime === null) {
                $firstTime = $t1;
            }
            $lastTime = $t2;

            $dtSeconds = max(1, $t2->getTimestamp() - $t1->getTimestamp());

            $segmentKm = self::haversineKm($lat1, $lon1, $lat2, $lon2);
            $totalDistanceKm += $segmentKm;

            $speedKmh = ($segmentKm * 3600.0) / $dtSeconds;
            if ($speedKmh > $maxSpeedKmh) {
                $maxSpeedKmh = $speedKmh;
            }
        }

        $totalDurationMinutes = 0.0;
        if ($firstTime !== null && $lastTime !== null) {
            $totalDurationMinutes = max(
                0,
                ($lastTime->getTimestamp() - $firstTime->getTimestamp()) / 60.0
            );
        }

        $avgSpeedKmh = 0.0;
        if ($totalDurationMinutes > 0) {
            $totalDurationHours = $totalDurationMinutes / 60.0;
            $avgSpeedKmh = $totalDistanceKm / $totalDurationHours;
        }

        return [
            'total_distance_km'      => $totalDistanceKm,
            'total_duration_minutes' => $totalDurationMinutes,
            'avg_speed_kmh'          => $avgSpeedKmh,
            'max_speed_kmh'          => $maxSpeedKmh,
            'point_count'            => $count,
        ];
    }

    /**
     * Great-circle distance in KM using haversine formula.
     */
    private static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
           * sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}
