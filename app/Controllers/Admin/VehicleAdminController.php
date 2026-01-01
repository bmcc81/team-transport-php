<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Vehicle;
use App\Models\Geofence;
use PDO;

class VehicleAdminController extends Controller
{
    /**
     * List all vehicles (Vehicle objects).
     */
    public function index(): void
    {
        $vehicles = Vehicle::all();
        $this->view('admin/vehicles/index', compact('vehicles'));
    }

    /**
     * Show a single vehicle profile.
     */
    public function profile(string $id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/view', compact('vehicle'));
    }

    /**
     * Show create form.
     */
    public function create(): void
    {
        $this->view('admin/vehicles/create');
    }

    /**
     * Store a new vehicle.
     * Schema-aligned to vehicles table:
     * vehicle_number, license_plate, make, model, year, status
     */
    public function store(): void
    {
        $pdo = Database::pdo();

        $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
        $plate         = trim($_POST['license_plate'] ?? '');
        $make          = trim($_POST['make'] ?? '');
        $model         = trim($_POST['model'] ?? '');
        $year          = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $status        = $_POST['status'] ?? 'available';

        $allowedStatus = ['available', 'in_service', 'maintenance'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'available';
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number,
                license_plate,
                make,
                model,
                year,
                status,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $vehicleNumber !== '' ? $vehicleNumber : null,
            $plate !== '' ? $plate : null,
            $make !== '' ? $make : null,
            $model !== '' ? $model : null,
            $year,
            $status,
        ]);

        $_SESSION['success'] = "Vehicle created successfully.";
        $this->redirect('/admin/vehicles');
    }

    /**
     * Show edit form.
     */
    public function edit(string $id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/edit', compact('vehicle'));
    }

    /**
     * Update an existing vehicle.
     */
    public function update(string $id): void
    {
        $pdo = Database::pdo();
        $id  = (int)$id;

        $vehicleNumber = trim($_POST['vehicle_number'] ?? '');
        $plate         = trim($_POST['license_plate'] ?? '');
        $make          = trim($_POST['make'] ?? '');
        $model         = trim($_POST['model'] ?? '');
        $year          = ($_POST['year'] ?? '') !== '' ? (int)$_POST['year'] : null;
        $vin           = trim($_POST['vin'] ?? '');
        $status        = $_POST['status'] ?? 'available';

        $allowedStatus = ['available', 'in_service', 'maintenance'];
        if (!in_array($status, $allowedStatus, true)) {
            $status = 'available';
        }

        $mustUnassign = ($status === 'maintenance');

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET
                vehicle_number     = ?,
                make               = ?,
                model              = ?,
                year               = ?,
                license_plate      = ?,
                vin                = ?,
                status             = ?,
                assigned_driver_id = CASE WHEN ? THEN NULL ELSE assigned_driver_id END,
                updated_at         = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $vehicleNumber !== '' ? $vehicleNumber : null,
            $make !== '' ? $make : null,
            $model !== '' ? $model : null,
            $year,
            $plate !== '' ? $plate : null,
            $vin !== '' ? $vin : null,
            $status,
            $mustUnassign ? 1 : 0,
            $id,
        ]);

        $_SESSION['success'] = 'Vehicle updated.';
        $this->redirect("/admin/vehicles/view/{$id}");
    }

    /**
     * Confirm delete screen.
     */
    public function confirmDelete(int $id): void
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/delete', compact('vehicle'));
    }

    /**
     * Delete vehicle.
     */
    public function delete(string $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([(int)$id]);

        $_SESSION['success'] = "Vehicle deleted.";
        $this->redirect('/admin/vehicles');
    }

    /**
     * Assign or unassign a driver from a vehicle.
     */
    public function assignDriver(int $id): void
    {
        $driverId = $_POST['assigned_driver_id'] ?? null;

        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        if ($driverId === 'none') {
            $vehicle->unassign();
            $_SESSION['success'] = "Driver unassigned.";
        } else {
            $vehicle->assignToDriver((int)$driverId);
            $_SESSION['success'] = "Driver assigned.";
        }

        // Adjust if your route differs
        $this->redirect("/admin/vehicles/view/{$id}");
    }

    /**
     * Vehicles + Geofences map view.
     * Your schema uses last_lat/last_lng (not latitude/longitude).
     */
    public function map(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT
                id,
                vehicle_number,
                make,
                model,
                license_plate,
                status,
                last_lat AS latitude,
                last_lng AS longitude
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $geofences = Geofence::allActive();

        $this->view('admin/vehicles/map', compact('vehicles', 'geofences'));
    }

    /**
     * Maintenance index for vehicle.
     */
    public function maintenance($vehicleId): void
    {
        $pdo = Database::pdo();
        $vehicleId = (int)$vehicleId;

        $vehicle = Vehicle::find($vehicleId);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $stmt = $pdo->prepare("
            SELECT
                id,
                vehicle_id,
                maintenance_type,
                status,
                scheduled_date,
                completed_date,
                notes,
                created_at,
                updated_at
            FROM vehicle_maintenance
            WHERE vehicle_id = ?
            ORDER BY scheduled_date DESC, id DESC
        ");
        $stmt->execute([$vehicleId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM vehicle_maintenance
            WHERE vehicle_id = ?
              AND status IN ('planned','in_progress')
              AND scheduled_date < CURDATE()
        ");
        $stmt->execute([$vehicleId]);
        $overdue = (int)$stmt->fetchColumn();

        $this->view('admin/vehicles/maintenance', compact('vehicle', 'items', 'overdue'));
    }

    /**
     * Show create maintenance form.
     */
    public function maintenanceCreate(int $id): void
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        $item = [
            'maintenance_type' => '',
            'scheduled_date'   => (new \DateTimeImmutable('today'))->format('Y-m-d'),
            'status'           => 'planned',
            'completed_date'   => '',
            'notes'            => '',
        ];

        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['errors']);

        $this->view('admin/vehicles/maintenance_create', compact('vehicle', 'item', 'errors'));
    }

    /**
     * Store maintenance task.
     */
    public function maintenanceStore(int $id): void
    {
        $pdo = Database::pdo();

        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        $maintenanceType = trim($_POST['maintenance_type'] ?? '');
        $scheduledDate   = trim($_POST['scheduled_date'] ?? '');
        $status          = trim($_POST['status'] ?? 'planned');
        $completedDate   = trim($_POST['completed_date'] ?? '');
        $notes           = trim($_POST['notes'] ?? '');

        $allowedStatus = ['planned','in_progress','completed','cancelled'];
        $errors = [];

        if ($maintenanceType === '') $errors[] = 'Maintenance type is required.';
        if ($scheduledDate === '') $errors[] = 'Scheduled date is required.';
        if (!in_array($status, $allowedStatus, true)) $errors[] = 'Invalid status.';

        if ($status === 'completed' && $completedDate === '') {
            $completedDate = (new \DateTimeImmutable('today'))->format('Y-m-d');
        }
        if ($status !== 'completed') {
            $completedDate = '';
        }

        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $this->redirect("/admin/vehicles/{$id}/maintenance/create");
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_maintenance
                (vehicle_id, maintenance_type, status, scheduled_date, completed_date, notes)
            VALUES
                (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id,
            $maintenanceType,
            $status,
            $scheduledDate,
            ($completedDate !== '' ? $completedDate : null),
            ($notes !== '' ? $notes : null),
        ]);

        $_SESSION['success'] = 'Maintenance item created.';
        $this->redirect("/admin/vehicles/{$id}/maintenance");
    }

    public function maintenanceComplete($id, $maintenanceId): void
    {
        $pdo = Database::pdo();
        $id = (int)$id;
        $maintenanceId = (int)$maintenanceId;

        $stmt = $pdo->prepare("
            UPDATE vehicle_maintenance
            SET status = 'completed',
                completed_date = COALESCE(completed_date, CURDATE())
            WHERE id = ?
              AND vehicle_id = ?
              AND status IN ('planned','in_progress')
        ");
        $stmt->execute([$maintenanceId, $id]);

        $_SESSION['success'] = 'Maintenance marked as completed.';
        $this->redirect("/admin/vehicles/{$id}/maintenance");
    }

    public function maintenanceDelete($id, $maintenanceId): void
    {
        $pdo = Database::pdo();
        $id = (int)$id;
        $maintenanceId = (int)$maintenanceId;

        $stmt = $pdo->prepare("
            DELETE FROM vehicle_maintenance
            WHERE id = ?
              AND vehicle_id = ?
        ");
        $stmt->execute([$maintenanceId, $id]);

        $_SESSION['success'] = 'Maintenance item deleted.';
        $this->redirect("/admin/vehicles/{$id}/maintenance");
    }
}
