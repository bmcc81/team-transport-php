<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class GeofenceController extends Controller
{
    /**
     * GET /admin/geofences
     */
    public function index(): void
    {
        $pdo = Database::pdo();

        // ------------------------------------------------------------
        // Query parameters
        // ------------------------------------------------------------
        $page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $pageSize = isset($_GET['per_page']) ? max(10, (int)$_GET['per_page']) : 10;
        $pageSize = min(100, $pageSize); // safety cap

        $search = trim((string)($_GET['search'] ?? ''));
        $type   = (string)($_GET['type'] ?? '');   // circle|polygon|rectangle|''
        $active = (string)($_GET['active'] ?? ''); // 0|1|''

        // Sorting
        $allowedSort = ['name', 'type', 'created_at', 'active', 'vehicle_count'];
        $sort  = (string)($_GET['sort'] ?? 'created_at');
        $order = strtoupper((string)($_GET['order'] ?? 'DESC'));

        if (!in_array($sort, $allowedSort, true)) $sort = 'created_at';
        if (!in_array($order, ['ASC', 'DESC'], true)) $order = 'DESC';

        // ------------------------------------------------------------
        // WHERE conditions
        // Uses generated columns: geo_description, geo_type; normal column: type, is_active
        // ------------------------------------------------------------
        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(g.name LIKE :search OR g.geo_description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($type !== '') {
            $allowedTypes = ['circle', 'polygon', 'rectangle'];
            if (in_array($type, $allowedTypes, true)) {
                $where[] = "g.type = :type";
                $params[':type'] = $type;
            }
        }

        if ($active !== '') {
            $where[] = "g.is_active = :active";
            $params[':active'] = (int)$active;
        }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        // ------------------------------------------------------------
        // Count total rows (pagination)
        // ------------------------------------------------------------
        $countSql = "SELECT COUNT(*) FROM geofences g {$whereSql}";
        $stmt = $pdo->prepare($countSql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        $totalRows  = (int)$stmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $pageSize));

        // Clamp page after we know total pages
        $page   = min($page, $totalPages);
        $offset = ($page - 1) * $pageSize;

        // ------------------------------------------------------------
        // Safe ORDER BY mapping (prevents injection)
        // ------------------------------------------------------------
        $sortMap = [
            'name'          => "g.name",
            'type'          => "g.type",
            'created_at'    => "g.created_at",
            'active'        => "g.is_active",
            'vehicle_count' => "vehicle_count", // alias from SELECT
        ];
        $orderBy = $sortMap[$sort] . " " . $order;

        // ------------------------------------------------------------
        // Fetch paginated geofences + assignment counts
        // ------------------------------------------------------------
        $sql = "
            SELECT
                g.id,
                g.name,
                g.type,
                g.geojson,
                g.is_active AS active,
                g.created_at,
                g.updated_at,
                g.geo_type,
                g.geo_description,

                COALESCE(gv.vehicle_count, 0) AS vehicle_count,
                CASE WHEN COALESCE(gv.vehicle_count, 0) = 0 THEN 1 ELSE 0 END AS applies_to_all_vehicles

            FROM geofences g
            LEFT JOIN (
                SELECT geofence_id, COUNT(*) AS vehicle_count
                FROM geofence_vehicle
                GROUP BY geofence_id
            ) gv ON gv.geofence_id = g.id

            {$whereSql}
            ORDER BY {$orderBy}
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);

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

    /**
     * GET /admin/geofences/create
     */
    public function create(): void
    {
        $pdo = Database::pdo();

        $vehicles = $pdo->query("
            SELECT id, vehicle_number, license_plate, status
            FROM vehicles
            ORDER BY vehicle_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/geofences/create', compact('vehicles'));
    }

    /**
     * POST /admin/geofences/store
     */
    public function store(): void
    {
        $pdo = Database::pdo();

        $name        = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $this->redirect("/admin/geofences?error=Missing required fields");
        }

        // Shape type column (enum)
        $shapeType = $this->normalizeShapeType((string)($_POST['type'] ?? ''));

        // Build GeoJSON Feature
        $geojson = $this->buildGeojsonFromRequest($description, $shapeType);

        // created_by_user_id (supports either session shape)
        $createdBy = $_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO geofences (name, type, geojson, is_active, created_by_user_id)
            VALUES (:name, :type, :geojson, :is_active, :created_by)
        ");

        $stmt->execute([
            ':name'       => $name,
            ':type'       => $shapeType,
            ':geojson'    => $geojson,
            ':is_active'  => $active,
            ':created_by' => $createdBy,
        ]);

        $geofenceId = (int)$pdo->lastInsertId();

        // Vehicle assignment
        $this->syncVehicleAssignments($pdo, $geofenceId);

        $this->redirect("/admin/geofences?success=Created");
    }

    /**
     * GET /admin/geofences/edit/{id}
     */
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

    /**
     * POST /admin/geofences/update/{id}
     */
    public function update(int $id): void
    {
        $pdo = Database::pdo();

        $name        = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $active      = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $this->redirect("/admin/geofences?error=Missing required fields");
        }

        $shapeType = $this->normalizeShapeType((string)($_POST['type'] ?? ''));

        $geojson = $this->buildGeojsonFromRequest($description, $shapeType);

        // updated_at is already handled by the DB definition:
        // updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        $stmt = $pdo->prepare("
            UPDATE geofences
            SET name = :name,
                type = :type,
                geojson = :geojson,
                is_active = :is_active
            WHERE id = :id
        ");

        $stmt->execute([
            ':name'      => $name,
            ':type'      => $shapeType,
            ':geojson'   => $geojson,
            ':is_active' => $active,
            ':id'        => $id,
        ]);

        // Vehicle assignment
        $this->syncVehicleAssignments($pdo, $id);

        $this->redirect("/admin/geofences?success=Updated");
    }

    /**
     * POST /admin/geofences/delete/{id}
     */
    public function delete(int $id): void
    {
        $pdo = Database::pdo();

        // If FK cascade exists on geofence_vehicle you can omit the first delete; keeping it explicit is fine.
        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM geofences WHERE id = ?")->execute([$id]);

        $this->redirect("/admin/geofences?success=Deleted");
    }

    /**
     * Normalize/validate shape type (enum in DB).
     */
    private function normalizeShapeType(string $type): string
    {
        $type = strtolower(trim($type));
        $allowed = ['circle', 'polygon', 'rectangle'];
        return in_array($type, $allowed, true) ? $type : 'polygon';
    }

    /**
     * Sync vehicle assignments for a geofence.
     * Rules:
     *  - If "applies_to_all_vehicles" checked, store zero rows in geofence_vehicle for this geofence.
     *  - Otherwise insert rows for selected vehicle_ids.
     */
    private function syncVehicleAssignments(PDO $pdo, int $geofenceId): void
    {
        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")->execute([$geofenceId]);

        $appliesAll = isset($_POST['applies_to_all_vehicles']);

        $vehicleIds = (array)($_POST['vehicle_ids'] ?? []);
        $vehicleIds = array_values(array_unique(array_map('intval', $vehicleIds)));
        $vehicleIds = array_values(array_filter($vehicleIds, fn($v) => $v > 0));

        if ($appliesAll || empty($vehicleIds)) {
            return;
        }

        $insert = $pdo->prepare("INSERT INTO geofence_vehicle (geofence_id, vehicle_id) VALUES (?, ?)");
        foreach ($vehicleIds as $vid) {
            $insert->execute([$geofenceId, $vid]);
        }
    }

    /**
     * Builds/stabilizes GeoJSON Feature from request.
     *
     * Supports:
     *  - $_POST['geojson'] as JSON string:
     *      - Feature
     *      - FeatureCollection (takes first feature)
     *      - Geometry (wraps into Feature)
     *  - Legacy circle fields: center_lat, center_lng, radius_m
     *  - Legacy polygon fields: polygon_points JSON [{lat,lng},...]
     *  - Legacy rectangle fields:
     *      - rectangle_bounds JSON {north,south,east,west}
     *      - OR north_lat, south_lat, east_lng, west_lng
     */
    private function buildGeojsonFromRequest(string $description = '', string $shapeType = 'polygon'): string
    {
        // Preferred: raw GeoJSON posted by your editor
        $raw = trim((string)($_POST['geojson'] ?? ''));
        if ($raw !== '') {
            $decoded = json_decode($raw, true);

            if (is_array($decoded)) {
                // FeatureCollection -> take first feature
                if (($decoded['type'] ?? null) === 'FeatureCollection') {
                    $features = $decoded['features'] ?? [];
                    if (is_array($features) && isset($features[0]) && is_array($features[0])) {
                        $decoded = $features[0];
                    }
                }

                // Feature -> ensure properties + description
                if (($decoded['type'] ?? null) === 'Feature') {
                    $decoded['properties'] = $decoded['properties'] ?? [];
                    if ($description !== '') {
                        $decoded['properties']['description'] = $description;
                    }

                    // Optional: basic consistency check between shapeType and geometry.type
                    $geomType = $decoded['geometry']['type'] ?? null;
                    if ($shapeType === 'circle' && $geomType !== 'Point') {
                        $this->redirect("/admin/geofences?error=GeoJSON geometry must be Point for circle");
                    }
                    if (in_array($shapeType, ['polygon', 'rectangle'], true) && $geomType !== 'Polygon') {
                        $this->redirect("/admin/geofences?error=GeoJSON geometry must be Polygon for polygon/rectangle");
                    }

                    return json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }

                // Geometry -> wrap into Feature
                if (isset($decoded['type'], $decoded['coordinates'])) {
                    $feature = [
                        'type'       => 'Feature',
                        'properties' => ['description' => $description],
                        'geometry'   => $decoded,
                    ];

                    // Basic consistency check
                    $geomType = $decoded['type'] ?? null;
                    if ($shapeType === 'circle' && $geomType !== 'Point') {
                        $this->redirect("/admin/geofences?error=GeoJSON geometry must be Point for circle");
                    }
                    if (in_array($shapeType, ['polygon', 'rectangle'], true) && $geomType !== 'Polygon') {
                        $this->redirect("/admin/geofences?error=GeoJSON geometry must be Polygon for polygon/rectangle");
                    }

                    return json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                }
            }
            // If invalid JSON or unsupported shape, fall through to legacy build
        }

        // Legacy builds by shape type
        if ($shapeType === 'circle') {
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
                'type'       => 'Feature',
                'properties' => [
                    'description' => $description,
                    'radius_m'    => $rad,
                ],
                'geometry'   => [
                    'type'        => 'Point',
                    // GeoJSON is [lng, lat]
                    'coordinates' => [$lng, $lat],
                ],
            ];

            return json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        if ($shapeType === 'rectangle') {
            // Accept rectangle_bounds JSON OR individual fields
            $boundsJson = trim((string)($_POST['rectangle_bounds'] ?? ''));
            $north = $south = $east = $west = null;

            if ($boundsJson !== '') {
                $b = json_decode($boundsJson, true);
                if (is_array($b)) {
                    $north = isset($b['north']) ? (float)$b['north'] : null;
                    $south = isset($b['south']) ? (float)$b['south'] : null;
                    $east  = isset($b['east'])  ? (float)$b['east']  : null;
                    $west  = isset($b['west'])  ? (float)$b['west']  : null;
                }
            } else {
                $north = ($_POST['north_lat'] ?? '') !== '' ? (float)$_POST['north_lat'] : null;
                $south = ($_POST['south_lat'] ?? '') !== '' ? (float)$_POST['south_lat'] : null;
                $east  = ($_POST['east_lng']  ?? '') !== '' ? (float)$_POST['east_lng']  : null;
                $west  = ($_POST['west_lng']  ?? '') !== '' ? (float)$_POST['west_lng']  : null;
            }

            if ($north === null || $south === null || $east === null || $west === null) {
                $this->redirect("/admin/geofences?error=Rectangle requires bounds (north/south/east/west)");
            }

            // Build rectangle ring (clockwise) and close it
            $ring = [
                [$west, $north],
                [$east, $north],
                [$east, $south],
                [$west, $south],
                [$west, $north],
            ];

            $feature = [
                'type'       => 'Feature',
                'properties' => [
                    'description' => $description,
                ],
                'geometry'   => [
                    'type'        => 'Polygon',
                    'coordinates' => [$ring],
                ],
            ];

            return json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // polygon (default)
        $polyJson = (string)($_POST['polygon_points'] ?? '');
        if ($polyJson !== '') {
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

            // Close ring
            $first = $ring[0];
            $last  = $ring[count($ring) - 1];
            if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
                $ring[] = $first;
            }

            $feature = [
                'type'       => 'Feature',
                'properties' => [
                    'description' => $description,
                ],
                'geometry'   => [
                    'type'        => 'Polygon',
                    'coordinates' => [$ring],
                ],
            ];

            return json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        // Fallback: store a valid but empty Feature (keeps row valid JSON)
        $feature = [
            'type'       => 'Feature',
            'properties' => [
                'description' => $description,
            ],
            'geometry'   => [
                'type'       => 'GeometryCollection',
                'geometries' => [],
            ],
        ];

        return json_encode($feature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
