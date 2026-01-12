<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\TelemetryService;

final class TelemetryController extends Controller
{
    /**
     * Renders the live map page.
     * GET /admin/vehicles/map
     */
    public function liveMap(): void
    {
        $vehicles  = TelemetryService::latestForAll();
        $geofences = []; // stop warnings until you wire it properly
        $this->view('admin/vehicles/map', compact('vehicles', 'geofences'));
    }

    /**
     * JSON: latest positions for all vehicles.
     * GET /api/telemetry/latest
     */
    public function latest(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(TelemetryService::latestForAll(), JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * JSON: telemetry history for one vehicle.
     * GET /api/telemetry/history/{id}
     */
    public function history(string $id): void
    {
        $vehicleId = (int)$id;

        header('Content-Type: application/json; charset=utf-8');

        if ($vehicleId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid vehicle id'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        // 300 points, ascending for map trails by default
        $rows = TelemetryService::historyForVehicle($vehicleId, 300, true);

        echo json_encode($rows, JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Ingestion endpoint.
     * POST /api/telemetry/ingest
     *
     * Accepts JSON:
     *  { "vehicle_id":1, "lat":..., "lng":..., "speed_kph":..., "heading_deg":..., "source":"api" }
     *
     * Also accepts legacy keys:
     *  speed -> speed_kph
     *  heading -> heading_deg
     */
    public function ingest(): void
    {
        $body = file_get_contents('php://input') ?: '';
        $payload = json_decode($body, true);

        header('Content-Type: application/json; charset=utf-8');

        if (!is_array($payload)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid JSON body'], JSON_UNESCAPED_SLASHES);
            exit;
        }

        // Back-compat mapping (if your devices send speed/heading)
        if (!isset($payload['speed_kph']) && isset($payload['speed'])) {
            $payload['speed_kph'] = $payload['speed'];
        }
        if (!isset($payload['heading_deg']) && isset($payload['heading'])) {
            $payload['heading_deg'] = $payload['heading'];
        }

        try {
            $id = TelemetryService::ingest($payload);

            echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_SLASHES);
            exit;

        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
            ], JSON_UNESCAPED_SLASHES);
            exit;
        }
    }
}
