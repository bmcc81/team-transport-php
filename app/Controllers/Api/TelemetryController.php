<?php
namespace App\Controllers\Api;

use App\Core\Controller;
use App\Services\TelemetryService;

class TelemetryController extends Controller
{
    /**
     * Renders the live map page.
     */
    public function liveMap(): void
    {
        $vehicles = TelemetryService::latestForAll();
        $this->view('admin/vehicles/map', compact('vehicles'));
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
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(TelemetryService::history((int)$id), JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Ingestion endpoint.
     * POST /api/telemetry/ingest
     * JSON body: { "vehicle_id":1, "lat":..., "lng":..., "speed":..., "heading":... }
     */
    public function ingest(): void
    {
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true) ?? [];

        TelemetryService::ingest($payload);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
}
