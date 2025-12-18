<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class GeofenceController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        // ------------------------------------------------------------
        // Query Parameters
        // ------------------------------------------------------------
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['per_page']) ? max(10, (int)$_GET['per_page']) : 10;

        $search = trim((string)($_GET['search'] ?? ''));
        $type   = (string)($_GET['type'] ?? '');   // circle|polygon
        $active = (string)($_GET['active'] ?? ''); // 0|1|''

        // Sorting allowed fields
        $allowedSort = ['name', 'type', 'created_at'];
        $sort  = (string)($_GET['sort'] ?? 'created_at');
        $order = strtoupper((string)($_GET['order'] ?? 'DESC'));

        if (!in_array($sort, $allowedSort, true)) $sort = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        $offset = ($page - 1) * $pageSize;

        // ------------------------------------------------------------
        // Build WHERE conditions (geojson + is_active)
        // ------------------------------------------------------------
        $where  = [];
        $params = [];

        if ($search !== '') {
            // Search name OR geojson.properties.description (if present)
            $where[] = "(g.name LIKE :search OR JSON_UNQUOTE(JSON_EXTRACT(g.geojson, '$.properties.description')) LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($type === 'circle') {
            // We store circle as Point geometry with radius in properties
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(g.geojson, '$.geometry.type')) = 'Point'";
        } elseif ($type === 'polygon') {
            $where[] = "JSON_UNQUOTE(JSON_EXTRACT(g.geojson, '$.geometry.type')) = 'Polygon'";
        }

        if ($active !== '') {
            $where[] = "g.is_active = :active";
            $params[':active'] = (int)$active;
        }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        // ------------------------------------------------------------
        // Count total rows for pagination
        // ------------------------------------------------------------
        $countSql = "SELECT COUNT(*) FROM geofences g $whereSql";
        $stmt = $pdo->prepare($countSql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $totalRows = (int)$stmt->fetchColumn();

        $totalPages = max(1, (int)ceil($totalRows / $pageSize));

        // ------------------------------------------------------------
        // Safe ORDER BY mapping (prevents injection)
        // ------------------------------------------------------------
        $sortMap = [
            'name'       => "g.name",
            'created_at' => "g.created_at",
            'type'       => "type", // alias from SELECT below
        ];
        $orderBy = $sortMap[$sort] . " " . $order;

        // ------------------------------------------------------------
        // Fetch paginated geofences
        // ------------------------------------------------------------
        $sql = "
            SELECT
                g.id,
                g.name,
                g.geojson,
                g.is_active AS active,
                g.created_at,

                -- Derive a friendly type for UI/filtering/sorting
                CASE
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(g.geojson, '$.geometry.type')) = 'Polygon' THEN 'polygon'
                    WHEN JSON_UNQUOTE(JSON_EXTRACT(g.geojson, '$.geometry.type')) = 'Point'   THEN 'circle'
                    ELSE 'unknown'
                END AS type,

                -- Vehicle targeting
                (
                    SELECT COUNT(*)
                    FROM geofence_vehicle gv
                    WHERE gv.geofence_id = g.id
                ) AS vehicle_count,

                -- Applies to all vehicles if no assignments exist
                CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM geofence_vehicle gv2
                        WHERE gv2.geofence_id = g.id
                    ) = 0 THEN 1 ELSE 0
                END AS applies_to_all_vehicles

            FROM geofences g
            $whereSql
            ORDER BY $orderBy
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }

        $stmt->bindValue(':limit',  $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);

        $stmt->execute();
        $geofences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/geofences/index', [
            'geofences'  => $geofences,
            'page'       => $page,
            'pageSize'   => $pageSize,
            'totalPages' => $totalPages,
            'search'     => $search,
            'active'     => $active,
            'type'       => $type,
            'sort'       => $sort,
            'order'      => $order,
        ]);
    }

    public function create(): void
    {
        $pdo = Database::pdo();

        // Your vehicles table does NOT have make/model
        $vehicles = $pdo->query("
            SELECT id, vehicle_number, license_plate, status
            FROM vehicles
            ORDER BY vehicle_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/geofences/create', compact('vehicles'));
    }

    public function store(): void
    {
        $pdo = Database::pdo();

        $name        = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? 1 : 0;
        $createdBy   = $_SESSION['user_id'] ?? null;

        if ($name === '') {
            $this->redirect("/admin/geofences?error=Missing required fields");
        }

        // Accept either:
        // - raw geojson POST (preferred going forward)
        // - legacy circle/polygon fields -> converted to GeoJSON Feature
        $geojson = $this->buildGeojsonFromRequest($description);

        $stmt = $pdo->prepare("
            INSERT INTO geofences (name, geojson, is_active, created_by_user_id)
            VALUES (:name, :geojson, :is_active, :created_by)
        ");

        $stmt->execute([
            ':name'       => $name,
            ':geojson'    => $geojson,
            ':is_active'  => $active,
            ':created_by' => $createdBy,
        ]);

        $geofenceId = (int)$pdo->lastInsertId();

        // Vehicle assignment
        $appliesAll = isset($_POST['applies_to_all_vehicles']);
        $vehicleIds = $_POST['vehicle_ids'] ?? [];

        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")->execute([$geofenceId]);

        if (!$appliesAll && !empty($vehicleIds)) {
            $insert = $pdo->prepare("INSERT INTO geofence_vehicle (geofence_id, vehicle_id) VALUES (?, ?)");
            foreach ($vehicleIds as $vid) {
                $insert->execute([$geofenceId, (int)$vid]);
            }
        }

        $this->redirect("/admin/geofences?success=Created");
    }

    public function edit(?int $id = null): void
    {
        if (!$id) {
            $this->redirect("/admin/geofences");
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM geofences WHERE id = ?");
        $stmt->execute([$id]);
        $geofence = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$geofence) {
            $this->redirect("/admin/geofences?error=Not found");
        }

        $vehicles = $pdo->query("
            SELECT id, vehicle_number, license_plate, status
            FROM vehicles
            ORDER BY vehicle_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $assigned = $pdo->prepare("SELECT vehicle_id FROM geofence_vehicle WHERE geofence_id = ?");
        $assigned->execute([$id]);
        $assignedVehicles = array_map('intval', array_column($assigned->fetchAll(PDO::FETCH_ASSOC), 'vehicle_id'));

        $this->view('admin/geofences/edit', compact('geofence', 'vehicles', 'assignedVehicles'));
    }

    public function update(int $id): void
    {
        $pdo = Database::pdo();

        $name        = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $this->redirect("/admin/geofences?error=Missing required fields");
        }

        $geojson = $this->buildGeojsonFromRequest($description);

        $stmt = $pdo->prepare("
            UPDATE geofences
            SET name = :name,
                geojson = :geojson,
                is_active = :is_active,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = :id
        ");

        $stmt->execute([
            ':name'      => $name,
            ':geojson'   => $geojson,
            ':is_active' => $active,
            ':id'        => $id,
        ]);

        // Vehicle assignment
        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")->execute([$id]);

        $appliesAll = isset($_POST['applies_to_all_vehicles']);
        $vehicleIds = $_POST['vehicle_ids'] ?? [];

        if (!$appliesAll && !empty($vehicleIds)) {
            $insert = $pdo->prepare("INSERT INTO geofence_vehicle (geofence_id, vehicle_id) VALUES (?, ?)");
            foreach ($vehicleIds as $vid) {
                $insert->execute([$id, (int)$vid]);
            }
        }

        $this->redirect("/admin/geofences?success=Updated");
    }

    public function delete(int $id): void
    {
        $pdo = Database::pdo();

        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM geofences WHERE id = ?")->execute([$id]);

        $this->redirect("/admin/geofences?success=Deleted");
    }

    /**
     * Builds/stabilizes geojson from request.
     * Supports:
     *  - $_POST['geojson'] (JSON string)
     *  - legacy circle fields: center_lat, center_lng, radius_m
     *  - legacy polygon fields: polygon_points JSON [{lat,lng},...]
     */
    private function buildGeojsonFromRequest(string $description = ''): string
    {
        // Preferred: raw geojson posted by your editor
        $raw = trim((string)($_POST['geojson'] ?? ''));
        if ($raw !== '') {
            // normalize into Feature if caller posted geometry only
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                if (($decoded['type'] ?? null) === 'Feature') {
                    $decoded['properties'] = $decoded['properties'] ?? [];
                    if ($description !== '') $decoded['properties']['description'] = $description;
                    return json_encode($decoded, JSON_UNESCAPED_SLASHES);
                }

                if (isset($decoded['type']) && isset($decoded['coordinates'])) {
                    // Looks like a geometry object
                    $feature = [
                        'type' => 'Feature',
                        'properties' => ['description' => $description],
                        'geometry' => $decoded,
                    ];
                    return json_encode($feature, JSON_UNESCAPED_SLASHES);
                }
            }
            // If invalid JSON, fall through to legacy build
        }

        $type = (string)($_POST['type'] ?? '');

        // Legacy circle -> GeoJSON Point + radius property
        if ($type === 'circle') {
            $lat = $_POST['center_lat'] ?? null;
            $lng = $_POST['center_lng'] ?? null;
            $rad = $_POST['radius_m']   ?? null;

            $lat = ($lat !== '' && $lat !== null) ? (float)$lat : null;
            $lng = ($lng !== '' && $lng !== null) ? (float)$lng : null;
            $rad = ($rad !== '' && $rad !== null) ? (float)$rad : null;

            if ($lat === null || $lng === null || $rad === null) {
                $this->redirect("/admin/geofences?error=Circle requires center & radius");
            }

            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'description' => $description,
                    'radius_m' => $rad,
                ],
                'geometry' => [
                    'type' => 'Point',
                    // GeoJSON is [lng, lat]
                    'coordinates' => [$lng, $lat],
                ],
            ];

            return json_encode($feature, JSON_UNESCAPED_SLASHES);
        }

        // Legacy polygon -> GeoJSON Polygon
        if ($type === 'polygon') {
            $polyJson = (string)($_POST['polygon_points'] ?? '');
            if ($polyJson === '') {
                $this->redirect("/admin/geofences?error=Polygon must have at least 3 points");
            }

            $points = json_decode($polyJson, true);
            if (!is_array($points) || count($points) < 3) {
                $this->redirect("/admin/geofences?error=Polygon must have at least 3 points");
            }

            $ring = [];
            foreach ($points as $p) {
                if (is_array($p) && isset($p['lat'], $p['lng'])) {
                    $ring[] = [(float)$p['lng'], (float)$p['lat']];
                } elseif (is_array($p) && count($p) >= 2) {
                    // maybe [lat, lng]
                    $ring[] = [(float)$p[1], (float)$p[0]];
                }
            }

            if (count($ring) < 3) {
                $this->redirect("/admin/geofences?error=Polygon must have at least 3 valid points");
            }

            // Close ring (repeat first point)
            $first = $ring[0];
            $last  = $ring[count($ring) - 1];
            if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                $ring[] = $first;
            }

            $feature = [
                'type' => 'Feature',
                'properties' => [
                    'description' => $description,
                ],
                'geometry' => [
                    'type' => 'Polygon',
                    'coordinates' => [$ring],
                ],
            ];

            return json_encode($feature, JSON_UNESCAPED_SLASHES);
        }

        // Fallback: create a neutral feature so the row is still valid
        $feature = [
            'type' => 'Feature',
            'properties' => [
                'description' => $description,
            ],
            'geometry' => [
                'type' => 'GeometryCollection',
                'geometries' => [],
            ],
        ];

        return json_encode($feature, JSON_UNESCAPED_SLASHES);
    }
}
