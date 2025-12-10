<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Vehicle;
use App\Models\Geofence;

class VehicleAdminController extends Controller
{
    public function index(): void
    {
        $vehicles = Vehicle::all();
        $this->view('admin/vehicles/index', compact('vehicles'));
    }

    public function profile(string $id): void
    {
        $vehicle = Vehicle::find($id);

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

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number, make, model, year,
                license_plate, vin, capacity,
                status, maintenance_status,
                created_at, updated_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $_POST['vehicle_number'],
            $_POST['make'],
            $_POST['model'],
            (int)$_POST['year'],
            $_POST['license_plate'],
            $_POST['vin'] ?: null,
            $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null,
            $_POST['status'] ?? 'available',
            $_POST['maintenance_status'] ?? 'ok'
        ]);

        $_SESSION['success'] = "Vehicle created successfully.";
        $this->redirect('/admin/vehicles');
    }

    public function edit(string $id)
    {
        $vehicle = Vehicle::find($id);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/edit', ['vehicle' => $vehicle]);
    }

    public function update(string $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE vehicles SET
                vehicle_number = ?, make = ?, model = ?, year = ?,
                license_plate = ?, vin = ?, capacity = ?,
                status = ?, maintenance_status = ?, updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['vehicle_number'],
            $_POST['make'],
            $_POST['model'],
            (int)$_POST['year'],
            $_POST['license_plate'],
            $_POST['vin'] ?: null,
            $_POST['capacity'] !== '' ? (int)$_POST['capacity'] : null,
            $_POST['status'],
            $_POST['maintenance_status'],
            $id
        ]);

        $_SESSION['success'] = "Vehicle updated.";
        $this->redirect("/admin/vehicles/view/$id");
    }

    public function confirmDelete(int $id): void
    {
        $vehicle = Vehicle::find($id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/delete', ['vehicle' => $vehicle]);
    }

    public function delete(string $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Vehicle deleted.";
        $this->redirect('/admin/vehicles');
    }

    public function assignDriver(int $id): void
    {
        $driverId = $_POST['assigned_driver_id'] ?? null;

        if ($driverId === 'none') {
            Vehicle::unassign($id);
            $_SESSION['success'] = "Driver unassigned.";
        } else {
            Vehicle::assignToDriver($id, (int)$driverId);
            $_SESSION['success'] = "Driver assigned.";
        }

        $this->redirect("/admin/vehicles/view/$id");
    }

    public function map(): void
    {
        $pdo = Database::pdo();

        // Fetch vehicles for markers
        $stmt = $pdo->query("
            SELECT id, vehicle_number, make, model, license_plate, status, latitude, longitude
            FROM vehicles
            ORDER BY vehicle_number ASC
        ");
        $vehicles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Fetch ACTIVE geofences
        $geofences = Geofence::allActive();

        $this->view('admin/vehicles/map', compact('vehicles', 'geofences'));
    }

    public function maintenanceCreate(int $id): void
{
    $pdo = Database::pdo();

    // Fetch the vehicle
    $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
    $stmt->execute([$id]);
    $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$vehicle) {
        http_response_code(404);
        echo "Vehicle not found.";
        return;
    }

    // Empty default values for the form
    $item = [
        'title'          => '',
        'description'    => '',
        'scheduled_date' => '',
        'status'         => 'planned',
    ];

    $errors = [];

    $this->view('admin/vehicles/maintenance_create', compact('vehicle', 'item', 'errors'));
}

public function maintenanceStore(int $id): void
    {
        $pdo = Database::pdo();

        // Validate vehicle exists
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found.";
            return;
        }

        // Collect POST data
        $title          = trim($_POST['title'] ?? '');
        $description    = trim($_POST['description'] ?? '');
        $scheduledDate  = trim($_POST['scheduled_date'] ?? '');
        $status         = trim($_POST['status'] ?? 'planned');

        // Validation
        $errors = [];

        if ($title === '') {
            $errors[] = "Title is required.";
        }

        if ($scheduledDate === '') {
            $errors[] = "Scheduled date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $scheduledDate)) {
            $errors[] = "Scheduled date must be YYYY-MM-DD.";
        }

        // If errors, re-render form
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

        // Insert into DB
        $stmt = $pdo->prepare("
            INSERT INTO vehicle_maintenance 
                (vehicle_id, title, description, scheduled_date, status, created_by, created_at)
            VALUES 
                (?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $vehicle['id'],
            $title,
            $description,
            $scheduledDate,
            $status,
            $_SESSION['user_id'] ?? 1, // fallback for safety
        ]);

        // Redirect to maintenance dashboard
        header("Location: /admin/vehicles/{$vehicle['id']}/maintenance");
        exit;
    }

}
