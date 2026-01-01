<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class LoadApiController extends Controller
{
    private function jsonResponse($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function index(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT l.load_id, l.load_number,
                   l.customer_id, c.name AS customer_company_name,
                   l.assigned_driver_id, u.full_name AS driver_name,
                   l.vehicle_id, v.vehicle_number,
                   l.scheduled_start, l.scheduled_end,
                   l.status, l.reference
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            LEFT JOIN vehicles v ON v.id = l.vehicle_id
            WHERE l.deleted_at IS NULL
            ORDER BY l.scheduled_start DESC
            LIMIT 200
        ");

        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['data' => $loads]);
    }

    public function show(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT l.*,
                   c.name AS customer_company_name,
                   u.full_name AS driver_name,
                   v.vehicle_number
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            LEFT JOIN vehicles v ON v.id = l.vehicle_id
            WHERE l.load_id = :id
              AND l.deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            $this->jsonResponse(['error' => 'Load not found'], 404);
        }

        $stmtStops = $pdo->prepare("
            SELECT *
            FROM load_stops
            WHERE load_id = :id
            ORDER BY sequence ASC
        ");
        $stmtStops->execute(['id' => $id]);
        $stops = $stmtStops->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonResponse([
            'data' => [
                'load'  => $load,
                'stops' => $stops,
            ]
        ]);
    }

    public function driverLoads(int $driverId): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT l.load_id, l.load_number,
                   l.customer_id, c.name AS customer_company_name,
                   l.scheduled_start, l.scheduled_end,
                   l.status, l.reference,
                   v.vehicle_number
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN vehicles v ON v.id = l.vehicle_id
            WHERE l.deleted_at IS NULL
              AND l.assigned_driver_id = :driver_id
              AND l.scheduled_end >= NOW() - INTERVAL 1 DAY
            ORDER BY l.scheduled_start ASC
        ");
        $stmt->execute(['driver_id' => $driverId]);

        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['data' => $loads]);
    }

    public function vehicleLoads(int $vehicleId): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT l.load_id, l.load_number,
                   l.customer_id, c.name AS customer_company_name,
                   l.scheduled_start, l.scheduled_end,
                   l.status, l.reference,
                   u.full_name AS driver_name
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            WHERE l.deleted_at IS NULL
              AND l.vehicle_id = :vehicle_id
              AND l.scheduled_end >= NOW() - INTERVAL 1 DAY
            ORDER BY l.scheduled_start ASC
        ");
        $stmt->execute(['vehicle_id' => $vehicleId]);

        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->jsonResponse(['data' => $loads]);
    }
}
