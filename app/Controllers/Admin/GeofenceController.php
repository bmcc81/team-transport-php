<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Geofence;
use App\Models\GeofenceAlert;

class GeofenceController extends Controller
{
    public function index(): void
    {
        $geofences = Geofence::all();
        $this->view('admin/geofences/index', compact('geofences'));
    }

    public function create(): void
    {
        $geofence = [
            'id' => null,
            'name' => '',
            'description' => '',
            'type' => 'circle',
            'center_lat' => null,
            'center_lng' => null,
            'radius_m' => 500,
            'polygon_points' => null,
            'applies_to_all_vehicles' => 1,
            'active' => 1,
        ];
        $this->view('admin/geofences/form', compact('geofence'));
    }

    public function store(): void
    {
        $data = $this->sanitizeGeofenceInput();
        $data['created_by'] = $_SESSION['user_id'] ?? null;

        Geofence::create($data);
        $_SESSION['flash_success'] = 'Geofence created successfully.';
        header('Location: /admin/geofences');
        exit;
    }

    public function edit(int $id): void
    {
        $geofence = Geofence::find($id);

        if (!$geofence) {
            http_response_code(404);
            echo "Geofence not found.";
            return;
        }

        $this->view('admin/geofences/form', compact('geofence'));
    }


    public function update(int $id): void
    {
        $existing = Geofence::find($id);
        if (!$existing) {
            http_response_code(404);
            echo "Geofence not found.";
            return;
        }

        $data = $this->sanitizeGeofenceInput();

        Geofence::updateById($id, $data);

        $_SESSION['flash_success'] = 'Geofence updated.';
        header('Location: /admin/geofences');
        exit;
    }

    public function delete(int $id): void
    {
        Geofence::deleteById($id);

        $_SESSION['flash_success'] = 'Geofence deleted.';
        header('Location: /admin/geofences');
        exit;
    }

    public function alerts(): void
    {
        $vehicleId = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : null;
        $geofenceId = isset($_GET['geofence_id']) ? (int)$_GET['geofence_id'] : null;

        $alerts = GeofenceAlert::latest(100, $vehicleId, $geofenceId);
        $geofences = Geofence::all();

        $this->view('admin/geofences/alerts', compact('alerts', 'geofences', 'vehicleId', 'geofenceId'));
    }

    private function sanitizeGeofenceInput(): array
    {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $name = 'Untitled Geofence';
        }

        $type = $_POST['type'] ?? 'circle';

        $centerLat = $_POST['center_lat'] ?? null;
        $centerLng = $_POST['center_lng'] ?? null;
        $radiusM   = $_POST['radius_m'] ?? null;

        return [
            'name' => $name,
            'description' => trim($_POST['description'] ?? ''),
            'type' => $type,
            'center_lat' => $centerLat !== '' ? (float)$centerLat : null,
            'center_lng' => $centerLng !== '' ? (float)$centerLng : null,
            'radius_m'   => $radiusM !== '' ? (int)$radiusM : null,
            'polygon_points' => null, // future
            'applies_to_all_vehicles' => isset($_POST['applies_to_all_vehicles']) ? 1 : 0,
            'active' => isset($_POST['active']) ? 1 : 0,
        ];
    }
}
