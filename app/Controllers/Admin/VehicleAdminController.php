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
        // Returns array<Vehicle>
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
     */
    public function store(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number,
                make,
                model,
                year,
                license_plate,
                vin,
                capacity,
                status,
                maintenance_status,
                created_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $_POST['vehicle_number'] ?? null,
            $_POST['make'] ?? null,
            $_POST['model'] ?? null,
            isset($_POST['year']) ? (int)$_POST['year'] : null,
            $_POST['license_plate'] ?? null,
            ($_POST['vin'] ?? '') !== '' ? $_POST['vin'] : null,
            ($_POST['capacity'] ?? '') !== '' ? (int)$_POST['capacity'] : null,
            $_POST['status'] ?? 'available',
            $_POST['maintenance_status'] ?? 'ok',
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

        $stmt = $pdo->prepare("
            UPDATE vehicles
            SET
                vehicle_number      = ?,
                make                = ?,
                model               = ?,
                year                = ?,
                license_plate       = ?,
                vin                 = ?,
                capacity            = ?,
                status              = ?,
                maintenance_status  = ?,
                updated_at          = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['vehicle_number'] ?? null,
            $_POST['make'] ?? null,
            $_POST['model'] ?? null,
            isset($_POST['year']) ? (int)$_POST['year'] : null,
            $_POST['license_plate'] ?? null,
            ($_POST['vin'] ?? '') !== '' ? $_POST['vin'] : null,
            ($_POST['capacity'] ?? '') !== '' ? (int)$_POST['capacity'] : null,
            $_POST['status'] ?? 'available',
            $_POST['maintenance_status'] ?? 'ok',
            (int)$id,
        ]);

        $_SESSION['success'] = "Vehicle updated.";
        $this->redirect("/admin/vehicles/{$id}");
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
     *
     * Expects POST: 'assigned_driver_id'
     * - 'none' → unassign
     * - int id → assign
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

        $this->redirect("/admin/vehicles/{$id}");
    }

    /**
     * Vehicles + Geofences map view.
     * (This view still uses array rows; that’s fine.)
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
                latitude,
                longitude
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $geofences = Geofence::allActive();

        $this->view('admin/vehicles/map', compact('vehicles', 'geofences'));
    }

    /**
     * Show create maintenance task form for a vehicle.
     * (This part still works with arrays from PDO.)
     */
    public function maintenanceCreate(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        $item = [
            'title'          => '',
            'description'    => '',
            'scheduled_date' => '',
            'status'         => 'planned',
        ];

        $errors = [];

        $this->view('admin/vehicles/maintenance_create', compact('vehicle', 'item', 'errors'));
    }

    /**
     * Store a maintenance task.
     */
    public function maintenanceStore(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        $title         = trim($_POST['title'] ?? '');
        $description   = trim($_POST['description'] ?? '');
        $scheduledDate = trim($_POST['scheduled_date'] ?? '');
        $status        = trim($_POST['status'] ?? 'planned');

        $errors = [];

        if ($title === '') {
            $errors[] = "Title is required.";
        }

        if ($scheduledDate === '') {
            $errors[] = "Scheduled date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
            $errors[] = "Scheduled date must be YYYY-MM-DD.";
        }

        if (!empty($errors)) {
            $item = [
                'title'          => $title,
                'description'    => $description,
                'scheduled_date' => $scheduledDate,
                'status'         => $status,
            ];

            $this->view('admin/vehicles/maintenance_create', compact('vehicle', 'item', 'errors'));
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO vehicle_maintenance (
                vehicle_id,
                title,
                description,
                scheduled_date,
                status,
                created_by,
                created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $vehicle['id'],
            $title,
            $description,
            $scheduledDate,
            $status,
            $_SESSION['user_id'] ?? 1,
        ]);

        header("Location: /admin/vehicles/{$vehicle['id']}/maintenance");
        exit;
    }
}
