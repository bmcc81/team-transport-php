<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use PDO;

class VehicleAdminController extends Controller
{
    public function index(): void
    {
        $vehicles = Vehicle::all();
        $this->view('admin/vehicles/index', compact('vehicles'));
    }

    public function profile($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/view', ['vehicle' => $vehicle]);
    }

    public function create(): void
    {
        $this->view('admin/vehicles/create');
    }

    public function store(): void
    {
        $pdo = Database::pdo();

        $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
        $make          = trim($_POST['make'] ?? '');
        $model         = trim($_POST['model'] ?? '');
        $year          = (int)($_POST['year'] ?? 0);
        $plate         = trim($_POST['license_plate'] ?? '');
        $vin           = trim($_POST['vin'] ?? '');
        $capacity      = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
        $status        = $_POST['status'] ?? 'available';
        $mStatus       = $_POST['maintenance_status'] ?? 'ok';

        if ($vehicleNumber === '' || $make === '' || $model === '' || !$year || $plate === '') {
            $_SESSION['error'] = "Please fill in all required fields.";
            header("Location: /admin/vehicles/create");
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number, make, model, year,
                license_plate, vin, capacity,
                status, maintenance_status,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $vehicleNumber,
            $make,
            $model,
            $year,
            $plate,
            $vin !== '' ? $vin : null,
            $capacity,
            $status,
            $mStatus,
        ]);

        $_SESSION['success'] = "Vehicle created successfully.";
        header("Location: /admin/vehicles");
        exit;
    }

    public function edit($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/edit', ['vehicle' => $vehicle]);
    }

    public function update($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $pdo = Database::pdo();

        $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
        $make          = trim($_POST['make'] ?? '');
        $model         = trim($_POST['model'] ?? '');
        $year          = (int)($_POST['year'] ?? 0);
        $plate         = trim($_POST['license_plate'] ?? '');
        $vin           = trim($_POST['vin'] ?? '');
        $capacity      = $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null;
        $status        = $_POST['status'] ?? 'available';
        $mStatus       = $_POST['maintenance_status'] ?? 'ok';

        if ($vehicleNumber === '' || $make === '' || $model === '' || !$year || $plate === '') {
            $_SESSION['error'] = "Please fill in all required fields.";
            header("Location: /admin/vehicles/edit/$id");
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE vehicles SET
                vehicle_number = ?,
                make = ?,
                model = ?,
                year = ?,
                license_plate = ?,
                vin = ?,
                capacity = ?,
                status = ?,
                maintenance_status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $vehicleNumber,
            $make,
            $model,
            $year,
            $plate,
            $vin !== '' ? $vin : null,
            $capacity,
            $status,
            $mStatus,
            $id
        ]);

        $_SESSION['success'] = "Vehicle updated successfully.";
        header("Location: /admin/vehicles/view/$id");
        exit;
    }

    public function confirmDelete($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        // If assigned to a driver, show warning in the view
        $this->view('admin/vehicles/delete', ['vehicle' => $vehicle]);
    }

    public function delete($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        if (!empty($vehicle['assigned_driver_id'])) {
            $_SESSION['error'] = "Cannot delete: vehicle is assigned to a driver. Unassign it first.";
            header("Location: /admin/vehicles/view/$id");
            exit;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Vehicle deleted.";
        header("Location: /admin/vehicles");
        exit;
    }

    /**
     * Assign / change driver for a vehicle
     * POST /admin/vehicles/{id}/assign-driver
     */
    public function assignDriver($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $driverId = $_POST['assigned_driver_id'] ?? '';

        if ($driverId === '' || $driverId === 'none') {
            Vehicle::unassign((int)$id);
            $_SESSION['success'] = "Vehicle unassigned from driver.";
        } else {
            Vehicle::assignToDriver((int)$id, (int)$driverId);
            $_SESSION['success'] = "Vehicle assigned to driver.";
        }

        header("Location: /admin/vehicles/view/$id");
        exit;
    }

    public function map(): void
    {
        $pdo = Database::pdo();

        // Fetch all vehicles with (optional) coords
        $stmt = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate, status, latitude, longitude
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");

        $vehicles = $stmt->fetchAll(mode: \PDO::FETCH_ASSOC);

        $this->view('admin/vehicles/map', compact('vehicles'));
    }

    public function updateGps($id): void
    {
        $pdo = Database::pdo();

        $lat = $_POST['latitude'] ?? null;
        $lng = $_POST['longitude'] ?? null;

        // Validate numeric
        if ($lat !== null && !is_numeric($lat)) $lat = null;
        if ($lng !== null && !is_numeric($lng)) $lng = null;

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET latitude = :lat, longitude = :lng
            WHERE id = :id
        ");

        $stmt->execute([
            ':lat' => $lat,
            ':lng' => $lng,
            ':id'  => $id
        ]);

        // Redirect back to vehicle page
        header("Location: /admin/vehicles/view/$id");
        exit;
    }

    public function saveGps(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles 
            SET latitude = ?, longitude = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['latitude'] ?? null,
            $_POST['longitude'] ?? null,
            $id
        ]);

        echo "OK";
    }
    public function gpsUpdate(int $vehicleId): void
    {
        $pdo = Database::pdo();

        $lat = $_POST['latitude'] ?? null;
        $lng = $_POST['longitude'] ?? null;

        if ($lat === null || $lng === null) {
            http_response_code(400);
            echo "Missing coordinates";
            return;
        }

        // Update vehicles table
        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET latitude = ?, longitude = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$lat, $lng, $vehicleId]);

        // Log GPS breadcrumb
        $log = $pdo->prepare("
            INSERT INTO vehicle_gps_history (vehicle_id, latitude, longitude)
            VALUES (?, ?, ?)
        ");
        $log->execute([$vehicleId, $lat, $lng]);

        echo "OK";
    }

    public function live(): void
    {
        $pdo = Database::pdo();

        // Only return vehicles currently in service
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
            WHERE status = 'in_service' OR status = 'in service'
            ORDER BY vehicle_number ASC
        ");

        $vehicles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($vehicles);
    }

    public function liveOne(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
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
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);

        $vehicle = $stmt->fetch(\PDO::FETCH_ASSOC);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($vehicle ?: null);
    }

    public function breadcrumbs(int $vehicleId): void
    {
        $pdo = Database::pdo();

        // Get the last 100 trail points with timestamp
        $stmt = $pdo->prepare("
            SELECT latitude, longitude, created_at
            FROM vehicle_gps_history
            WHERE vehicle_id = ?
            ORDER BY id DESC
            LIMIT 100
        ");
        $stmt->execute([$vehicleId]);

        $points = array_reverse($stmt->fetchAll(\PDO::FETCH_ASSOC));

        header('Content-Type: application/json');
        echo json_encode($points);
    }   

}
