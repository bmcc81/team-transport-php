<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Vehicle;
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

        // Get assigned driver info
        $driver = null;
        if (!empty($vehicle['assigned_driver_id'])) {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$vehicle['assigned_driver_id']]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $this->view('admin/vehicles/view', compact('vehicle', 'driver'));
    }

    public function create(): void
    {
        $this->view('admin/vehicles/create');
    }

    public function store(): void
    {
        $data = [
            'vehicle_number' => $_POST['vehicle_number'],
            'make'           => $_POST['make'],
            'model'          => $_POST['model'],
            'year'           => $_POST['year'],
            'license_plate'  => $_POST['license_plate'],
            'vin'            => $_POST['vin'] ?: null,
            'capacity'       => $_POST['capacity'] ?: null,
            'status'         => $_POST['status'],
            'maintenance_status' => $_POST['maintenance_status'],
        ];

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO vehicles (
                vehicle_number, make, model, year, 
                license_plate, vin, capacity, status,
                maintenance_status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $data['vehicle_number'],
            $data['make'],
            $data['model'],
            $data['year'],
            $data['license_plate'],
            $data['vin'],
            $data['capacity'],
            $data['status'],
            $data['maintenance_status']
        ]);

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

        $this->view('admin/vehicles/edit', compact('vehicle'));
    }

    public function update($id): void
    {
        $data = [
            'vehicle_number' => $_POST['vehicle_number'],
            'make'           => $_POST['make'],
            'model'          => $_POST['model'],
            'year'           => $_POST['year'],
            'license_plate'  => $_POST['license_plate'],
            'vin'            => $_POST['vin'] ?: null,
            'capacity'       => $_POST['capacity'] ?: null,
            'status'         => $_POST['status'],
            'maintenance_status' => $_POST['maintenance_status'],
        ];

        $pdo = Database::pdo();

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
            $data['vehicle_number'],
            $data['make'],
            $data['model'],
            $data['year'],
            $data['license_plate'],
            $data['vin'],
            $data['capacity'],
            $data['status'],
            $data['maintenance_status'],
            $id
        ]);

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

        // Optional: Check if assigned to a driver
        $assignedDriver = null;
        if (!empty($vehicle['assigned_driver_id'])) {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$vehicle['assigned_driver_id']]);
            $assignedDriver = $stmt->fetchColumn();
        }

        $this->view('admin/vehicles/delete', [
            'vehicle' => $vehicle,
            'assignedDriver' => $assignedDriver
        ]);
    }

    public function delete($id): void
    {
        $vehicle = Vehicle::find((int)$id);

        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
                }

        // Safety: Prevent deletion if assigned to a driver
        if (!empty($vehicle['assigned_driver_id'])) {
            $_SESSION['error'] = "Cannot delete a vehicle assigned to a driver.";
            header("Location: /admin/vehicles/view/$id");
            exit;
        }

        $pdo = Database::pdo();
        $stmt = $pdo->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['success'] = "Vehicle deleted successfully.";

        header("Location: /admin/vehicles");
        exit;
    }   


}
