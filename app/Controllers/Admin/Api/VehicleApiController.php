<?php
namespace App\Controllers\Admin\Api;

use App\Core\Controller;
use App\Database\Database;
use App\Models\VehicleGpsHistory;
use PDO;

class VehicleApiController extends Controller
{
    /**
     * GET /admin/api/vehicles/live
     * Returns live positions for in-service vehicles (JSON).
     */
    public function live(): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT
                id,
                vehicle_number,
                make,
                model,
                license_plate,
                status,
                latitude,
                longitude
            FROM vehicles
            WHERE status = 'in_service'
              AND latitude IS NOT NULL
              AND longitude IS NOT NULL
        ");

        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($vehicles);
    }

    /**
     * GET /admin/api/vehicles/{id}/history?date=YYYY-MM-DD
     * Returns GPS points + summary metrics for that day.
     */
    public function history(int $id): void
    {
        header('Content-Type: application/json; charset=utf-8');

        $date = $_GET['date'] ?? null;
        if (!$date) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing date parameter.']);
            return;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid date format, expected YYYY-MM-DD.']);
            return;
        }

        $trip = VehicleGpsHistory::getTripWithMetrics($id, $date);

        echo json_encode($trip);
    }
}
