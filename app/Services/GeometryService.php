<?php

namespace App\Services;

class GeometryService
{
    /**
     * Convert degrees to radians
     */
    private static function rad(float $deg): float
    {
        return $deg * M_PI / 180;
    }

    /**
     * Compute distance between two coordinates (meters)
     */
    public static function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $R = 6371000; // meters
        $dLat = self::rad($lat2 - $lat1);
        $dLng = self::rad($lng2 - $lng1);
        
        $lat1 = self::rad($lat1);
        $lat2 = self::rad($lat2);

        $a = sin($dLat/2) ** 2 +
            sin($dLng/2) ** 2 * cos($lat1) * cos($lat2);

        return 2 * $R * asin(sqrt($a));
    }

    /**
     * Bounding box helper:
     * Returns [minLat, minLng, maxLat, maxLng]
     */
    public static function boundingBox(array $points): array
    {
        $minLat = $minLng = 999;
        $maxLat = $maxLng = -999;

        foreach ($points as [$lat, $lng]) {
            $minLat = min($minLat, $lat);
            $minLng = min($minLng, $lng);
            $maxLat = max($maxLat, $lat);
            $maxLng = max($maxLng, $lng);
        }

        return [$minLat, $minLng, $maxLat, $maxLng];
    }

    /**
     * Point-in-polygon (Ray-casting)
     */
    public static function pointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $polygon[$i];
            [$xj, $yj] = $polygon[$j];

            $intersect =
                (($yi > $lng) !== ($yj > $lng)) &&
                ($lat < ($xj - $xi) * ($lng - $yi) / (($yj - $yi) ?: 1) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Polygon area (square meters)
     * Uses the spherical Earth projected area approximation
     */
    public static function polygonArea(array $points): float
    {
        $area = 0;
        $n = count($points);

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;

            $lat1 = self::rad($points[$i][0]);
            $lng1 = self::rad($points[$i][1]);
            $lat2 = self::rad($points[$j][0]);
            $lng2 = self::rad($points[$j][1]);

            $area += ($lng2 - $lng1) * (2 + sin($lat1) + sin($lat2));
        }

        return abs($area * 6378137 * 6378137 / 2); // Earth radius
    }
}
