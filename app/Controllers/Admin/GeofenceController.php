<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Geofence;

class GeofenceController extends Controller
{
    /**
     * List all geofences.
     */
    public function index(): void
    {
        $geofences = Geofence::all();
        $this->view('admin/geofences/index', compact('geofences'));
    }

    /**
     * Show create form.
     */
    public function create(): void
    {
        $this->view('admin/geofences/create');
    }

    /**
     * Store a new geofence (supports AJAX from the Live Map).
     */
    public function store(): void
    {
        $pdo = Database::pdo();

        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $type        = $_POST['type'] ?? 'circle';

        if ($name === '' || !in_array($type, ['circle', 'polygon'], true)) {
            http_response_code(400);
            exit('Invalid geofence data');
        }

        $appliesAll = isset($_POST['applies_to_all_vehicles']) ? 1 : 0;
        $active     = isset($_POST['active']) ? 1 : 0;

        //
        // CIRCLE
        //
        if ($type === 'circle') {
            $centerLat = floatval($_POST['center_lat'] ?? 0);
            $centerLng = floatval($_POST['center_lng'] ?? 0);
            $radius    = intval($_POST['radius_m'] ?? 0);
            $polygonJSON = null;
        }

        //
        // POLYGON
        //
        if ($type === 'polygon') {
            $rawPoints = $_POST['polygon_points'] ?? '';

            $decoded = json_decode($rawPoints, true);
            if (!$decoded || !is_array($decoded)) {
                http_response_code(400);
                exit('Invalid polygon data');
            }

            // Sanitize floats
            $clean = array_map(fn($pair) => [
                floatval($pair[0]),
                floatval($pair[1])
            ], $decoded);

            $polygonJSON = json_encode($clean, JSON_UNESCAPED_UNICODE);

            // Circle values not used
            $centerLat = null;
            $centerLng = null;
            $radius    = null;
        }

        //
        // INSERT
        //
        $stmt = $pdo->prepare("
            INSERT INTO geofences
            (name, description, type, center_lat, center_lng, radius_m, polygon_points, 
             applies_to_all_vehicles, active, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $description,
            $type,
            $centerLat,
            $centerLng,
            $radius,
            $polygonJSON,
            $appliesAll,
            $active,
            $_SESSION['user_id'] ?? null
        ]);

        // If coming from AJAX modal → return OK
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            echo "OK";
            return;
        }

        $this->redirect('/admin/geofences');

    }

    /**
     * Edit a geofence.
     */
    public function edit(int $id): void
    {
        $geofence = Geofence::find($id);
        if (!$geofence) {
            http_response_code(404);
            echo "Geofence not found";
            return;
        }

        $this->view('admin/geofences/edit', compact('geofence'));
    }

    /**
     * Update geofence.
     */
    public function update(): void
    {
        $id   = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $type = $_POST['type'] ?? 'circle';

        if ($id <= 0 || $name === '' || !in_array($type, ['circle', 'polygon'], true)) {
            $this->redirect('/admin/geofences');
        }

        $appliesAll = isset($_POST['applies_to_all_vehicles']) ? 1 : 0;
        $active     = isset($_POST['active']) ? 1 : 0;

        //
        // Build data array
        //
        $data = [
            'name'        => $name,
            'description' => trim($_POST['description'] ?? ''),
            'type'        => $type,
            'applies_to_all_vehicles' => $appliesAll,
            'active'      => $active,
            'center_lat'  => null,
            'center_lng'  => null,
            'radius_m'    => null,
            'polygon_points' => null,
        ];

        if ($type === 'circle') {
            $data['center_lat'] = floatval($_POST['center_lat'] ?? 0);
            $data['center_lng'] = floatval($_POST['center_lng'] ?? 0);
            $data['radius_m']   = intval($_POST['radius_m'] ?? 0);
        }

        if ($type === 'polygon') {
            $raw = $_POST['polygon_points'] ?? '';
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                $clean = array_map(fn($pair) => [
                    floatval($pair[0]),
                    floatval($pair[1])
                ], $decoded);

                $data['polygon_points'] = json_encode($clean, JSON_UNESCAPED_UNICODE);
            }
        }

        Geofence::updateById($id, $data);

        $this->redirect('/admin/geofences');
    }

    /**
     * Delete geofence.
     */
    public function delete(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($id > 0) {
            Geofence::delete($id);
        }

        $this->redirect('/admin/geofences');
    }

    /**
     * Simple placeholder (optional future feature).
     */
    public function alerts(): void
    {
        $this->view('admin/geofences/alerts');
    }
}
