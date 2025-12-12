<?php

namespace App\Models;

use App\Database\Database;
use PDO;

class Vehicle
{
    public int $id;
    public ?string $vehicle_number;
    public ?string $make;
    public ?string $model;
    public ?int $year;
    public ?string $license_plate;
    public ?string $vin;
    public ?int $capacity;
    public string $status;
    public string $maintenance_status;
    public ?int $assigned_driver_id;
    public ?float $latitude;
    public ?float $longitude;
    public string $created_at;
    public string $updated_at;

    /**
     * Convert DB row -> Vehicle object
     */
    private static function hydrate(array $data): Vehicle
    {
        $v = new Vehicle();

        $v->id                 = (int)$data['id'];
        $v->vehicle_number     = $data['vehicle_number'] ?? null;
        $v->make               = $data['make'] ?? null;
        $v->model              = $data['model'] ?? null;
        $v->year               = isset($data['year']) ? (int)$data['year'] : null;
        $v->license_plate      = $data['license_plate'] ?? null;
        $v->vin                = $data['vin'] ?? null;
        $v->capacity           = isset($data['capacity']) ? (int)$data['capacity'] : null;
        $v->status             = $data['status'] ?? 'available';
        $v->maintenance_status = $data['maintenance_status'] ?? 'ok';
        $v->assigned_driver_id = isset($data['assigned_driver_id']) ? (int)$data['assigned_driver_id'] : null;
        $v->latitude           = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $v->longitude          = isset($data['longitude']) ? (float)$data['longitude'] : null;
        $v->created_at         = $data['created_at'] ?? '';
        $v->updated_at         = $data['updated_at'] ?? '';

        return $v;
    }

    /**
     * Fetch all vehicles as objects.
     */
    public static function all(): array
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY vehicle_number ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => self::hydrate($row), $rows);
    }

    /**
     * Find a single vehicle.
     */
    public static function find(int|string $id): ?Vehicle
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? self::hydrate($row) : null;
    }

    /**
     * Assign driver.
     */
    public function assignToDriver(int $driverId): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET assigned_driver_id = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $ok = $stmt->execute([$driverId, $this->id]);

        if ($ok) {
            $this->assigned_driver_id = $driverId;
        }

        return $ok;
    }

    /**
     * Unassign driver.
     */
    public function unassign(): bool
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET assigned_driver_id = NULL, updated_at = NOW()
            WHERE id = ?
        ");

        $ok = $stmt->execute([$this->id]);

        if ($ok) {
            $this->assigned_driver_id = null;
        }

        return $ok;
    }
}
