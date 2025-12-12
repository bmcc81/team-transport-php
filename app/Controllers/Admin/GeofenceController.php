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
        $page      = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $pageSize  = isset($_GET['per_page']) ? max(10, intval($_GET['per_page'])) : 10;
        $search    = $_GET['search'] ?? '';
        $type      = $_GET['type'] ?? '';
        $active    = $_GET['active'] ?? '';
        
        // Sorting allowed fields
        $allowedSort = ['name', 'type', 'created_at'];
        $sort   = $_GET['sort'] ?? 'created_at';
        $order  = $_GET['order'] ?? 'DESC';

        if (!in_array($sort, $allowedSort)) $sort = 'created_at';
        if (!in_array(strtoupper($order), ['ASC', 'DESC'])) $order = 'DESC';

        $offset = ($page - 1) * $pageSize;

        // ------------------------------------------------------------
        // Build WHERE conditions
        // ------------------------------------------------------------
        $where = [];
        $params = [];

        if ($search !== '') {
            $where[] = "(g.name LIKE :search OR g.description LIKE :search)";
            $params[':search'] = "%{$search}%";
        }

        if ($type === 'circle' || $type === 'polygon') {
            $where[] = "g.type = :type";
            $params[':type'] = $type;
        }

        if ($active !== '') {
            $where[] = "g.active = :active";
            $params[':active'] = intval($active);
        }

        $whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

        // ------------------------------------------------------------
        // Count total rows for pagination
        // ------------------------------------------------------------
        $countSql = "SELECT COUNT(*) FROM geofences g $whereSql";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $totalRows = intval($stmt->fetchColumn());

        $totalPages = max(1, ceil($totalRows / $pageSize));

        // ------------------------------------------------------------
        // Fetch paginated geofences
        // ------------------------------------------------------------
        $sql = "
            SELECT 
                g.id,
                g.name,
                g.type,
                g.active,
                g.applies_to_all_vehicles,
                g.created_at,
                (
                    SELECT COUNT(*) FROM geofence_vehicle gv
                    WHERE gv.geofence_id = g.id
                ) AS vehicle_count
            FROM geofences g
            $whereSql
            ORDER BY $sort $order
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $geofences = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ------------------------------------------------------------
        // View Render
        // ------------------------------------------------------------
        $this->view('admin/geofences/index', [
            'geofences'   => $geofences,
            'page'        => $page,
            'pageSize'    => $pageSize,
            'totalPages'  => $totalPages,
            'search'      => $search,
            'active'      => $active,
            'type'        => $type,
            'sort'        => $sort,
            'order'       => $order
        ]);
    }


    public function create(): void
    {
        $pdo = Database::pdo();
        $vehicles = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate
            FROM vehicles
            ORDER BY vehicle_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/geofences/create', compact('vehicles'));
    }

    public function store(): void
    {
        $pdo = Database::pdo();

        $type        = $_POST['type'] ?? null;
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active      = isset($_POST['active']) ? 1 : 0;
        $createdBy   = $_SESSION['user_id'] ?? null;

        if (!$name || !$type) {
            $this->redirect("/admin/geofences?error=Missing required fields");
        }

        // Validate geometry ------------------------------------------
        $centerLat = $_POST['center_lat'] ?? null;
        $centerLng = $_POST['center_lng'] ?? null;
        $radius    = $_POST['radius_m']   ?? null;
        $polyJson  = $_POST['polygon_points'] ?? null;

        $centerLat = $centerLat !== '' ? floatval($centerLat) : null;
        $centerLng = $centerLng !== '' ? floatval($centerLng) : null;
        $radius    = $radius    !== '' ? floatval($radius)    : null;

        $polygonPoints = null;
        if (!empty($polyJson)) {
            try {
                $polygonPoints = json_decode($polyJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $this->redirect("/admin/geofences?error=Invalid polygon JSON");
            }
        }

        // Validate based on type -------------------------------------
        if ($type === 'circle') {
            if (!$centerLat || !$centerLng || !$radius) {
                $this->redirect("/admin/geofences?error=Circle requires center & radius");
            }
        }

        if ($type === 'polygon') {
            if (!$polygonPoints || !is_array($polygonPoints) || count($polygonPoints) < 3) {
                $this->redirect("/admin/geofences?error=Polygon must have at least 3 points");
            }
        }

        // Insert geofence --------------------------------------------
        $stmt = $pdo->prepare("
            INSERT INTO geofences
            (name, description, type, center_lat, center_lng, radius_m, polygon_points, active, created_by, updated_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $name,
            $description,
            $type,
            $centerLat,
            $centerLng,
            $radius,
            $polygonPoints ? json_encode($polygonPoints) : null,
            $active,
            $createdBy,
            $createdBy
        ]);
        $geofenceId = $pdo->lastInsertId();

        // Vehicle assignment ------------------------------------------
        $appliesAll = isset($_POST['applies_to_all_vehicles']);
        $vehicleIds = $_POST['vehicle_ids'] ?? [];

        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")
            ->execute([$geofenceId]);

        if (!$appliesAll && !empty($vehicleIds)) {
            $insert = $pdo->prepare("
                INSERT INTO geofence_vehicle (geofence_id, vehicle_id) 
                VALUES (?, ?)
            ");

            foreach ($vehicleIds as $id) {
                $insert->execute([$geofenceId, (int)$id]);
            }
        }

        $this->redirect("/admin/geofences?success=Created");
    }

    public function edit(?int $id = null): void
    {
        if (!$id) {
            // redirect or show error
            header("Location: /admin/geofences");
            exit;
        }
        $pdo = Database::pdo();

        $geofence = $pdo->prepare("SELECT * FROM geofences WHERE id = ?");
        $geofence->execute([$id]);
        $geofence = $geofence->fetch(PDO::FETCH_ASSOC);

        if (!$geofence)
            $this->redirect("/admin/geofences?error=Not found");

        $vehicles = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate
            FROM vehicles
            ORDER BY vehicle_number ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $assigned = $pdo->prepare("
            SELECT vehicle_id FROM geofence_vehicle WHERE geofence_id = ?
        ");
        $assigned->execute([$id]);
        $assignedVehicles = array_column($assigned->fetchAll(PDO::FETCH_ASSOC), 'vehicle_id');

        $this->view('admin/geofences/edit', compact('geofence', 'vehicles', 'assignedVehicles'));
    }

    public function update(int $id): void
    {
        $pdo = Database::pdo();

        $type        = $_POST['type'] ?? null;
        $name        = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $active      = isset($_POST['active']) ? 1 : 0;
        $updatedBy   = $_SESSION['user_id'] ?? null;

        if (!$name || !$type)
            $this->redirect("/admin/geofences?error=Missing required fields");

        // geometry validation same as store()
        $centerLat = $_POST['center_lat'] ?? null;
        $centerLng = $_POST['center_lng'] ?? null;
        $radius    = $_POST['radius_m']   ?? null;
        $polyJson  = $_POST['polygon_points'] ?? null;

        $centerLat = $centerLat !== '' ? floatval($centerLat) : null;
        $centerLng = $centerLng !== '' ? floatval($centerLng) : null;
        $radius    = $radius    !== '' ? floatval($radius)    : null;

        $polygonPoints = null;
        if (!empty($polyJson)) {
            try {
                $polygonPoints = json_decode($polyJson, true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable $e) {
                $this->redirect("/admin/geofences?error=Invalid polygon JSON");
            }
        }

        // Update geofence
        $stmt = $pdo->prepare("
            UPDATE geofences
            SET name=?, description=?, type=?, center_lat=?, center_lng=?, radius_m=?, polygon_points=?, active=?, updated_by=?
            WHERE id=?
        ");

        $stmt->execute([
            $name,
            $description,
            $type,
            $centerLat,
            $centerLng,
            $radius,
            $polygonPoints ? json_encode($polygonPoints) : null,
            $active,
            $updatedBy,
            $id
        ]);

        // Vehicle assignment
        $pdo->prepare("DELETE FROM geofence_vehicle WHERE geofence_id = ?")
            ->execute([$id]);

        $appliesAll = isset($_POST['applies_to_all_vehicles']);
        $vehicleIds = $_POST['vehicle_ids'] ?? [];

        if (!$appliesAll && !empty($vehicleIds)) {
            $insert = $pdo->prepare("INSERT INTO geofence_vehicle (geofence_id, vehicle_id) VALUES (?, ?)");
            foreach ($vehicleIds as $v) {
                $insert->execute([$id, (int)$v]);
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
}
