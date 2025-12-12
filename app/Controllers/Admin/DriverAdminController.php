<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class DriverAdminController extends Controller
{
   public function index(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT 
                u.id,
                u.full_name,
                u.username,
                u.email,
                u.status,
                v.vehicle_number
            FROM users u
            LEFT JOIN vehicles v
                ON v.assigned_driver_id = u.id
            WHERE u.role = 'driver'
            ORDER BY u.full_name ASC
        ");

        $drivers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/drivers/index', [
            'drivers' => $drivers
        ]);
    }

    public function profile($id): void
    {
        $pdo = Database::pdo();

        // Get driver info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'driver'");
        $stmt->execute([$id]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            http_response_code(404);
            echo "Driver not found";
            return;
        }

        // Get loads for this driver
        $stmt = $pdo->prepare("
            SELECT l.*, c.customer_company_name
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            WHERE l.assigned_driver_id = ?
            ORDER BY l.pickup_date DESC
        ");
        $stmt->execute([$id]);
        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get assigned vehicle (if any)
        $stmt = $pdo->prepare("SELECT * FROM vehicles WHERE assigned_driver_id = ?");
        $stmt->execute([$id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->view('admin/drivers/view', [
            'driver'  => $driver,
            'loads'   => $loads,
            'vehicle' => $vehicle
        ]);
    }
    
    public function edit(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.full_name,
                u.username,
                u.email,
                u.status,
                u.updated_at,
                v.id AS vehicle_id,
                v.vehicle_number
            FROM users u
            LEFT JOIN vehicles v 
                ON v.assigned_driver_id = u.id
            WHERE u.id = ?
        ");

        $stmt->execute([$id]);
        $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$driver) {
            http_response_code(404);
            echo 'Driver not found';
            return;
        }

        $this->view('admin/drivers/edit', [
            'driver' => $driver
        ]);
    }

    public function update(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE users
            SET full_name = ?,
                username  = ?,
                email     = ?,
                status    = ?,
                updated_at = NOW(),
                updated_by = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['full_name'],
            $_POST['username'],
            $_POST['email'],
            $_POST['status'],
            $_SESSION['user']['id'],
            $id
        ]);

        $_SESSION['success'] = 'Driver updated successfully.';
        $this->redirect('/admin/drivers');
    }

    public function assignVehicleForm(int $driverId): void
    {
        $pdo = Database::pdo();

        // Fetch driver
        $stmt = $pdo->prepare("
            SELECT id, full_name
            FROM users
            WHERE id = ? AND role = 'driver'
        ");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$driver) {
            http_response_code(404);
            echo 'Driver not found';
            return;
        }

        // Fetch available vehicles
        $stmt = $pdo->prepare("
            SELECT 
                v.id,
                v.vehicle_number,
                v.assigned_driver_id,
                u.full_name AS assigned_driver_name
            FROM vehicles v
            LEFT JOIN users u ON u.id = v.assigned_driver_id
            WHERE v.status != 'retired'
            ORDER BY v.vehicle_number
        ");
        $stmt->execute();
        $vehicles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('admin/drivers/assign_vehicle', [
            'driver'   => $driver,
            'vehicles' => $vehicles
        ]);
    }

    public function assignVehicleSave(int $driverId): void
    {
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        if ($vehicleId <= 0) {
            $_SESSION['errors'][] = 'Please select a vehicle.';
            $this->redirect("/admin/drivers/assign-vehicle/{$driverId}");
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Unassign this driver from any vehicle (safety)
            $stmt = $pdo->prepare("
                UPDATE vehicles
                SET assigned_driver_id = NULL
                WHERE assigned_driver_id = ?
            ");
            $stmt->execute([$driverId]);

            // Assign new vehicle
            $stmt = $pdo->prepare("
                UPDATE vehicles
                SET assigned_driver_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$driverId, $vehicleId]);

            $pdo->commit();

            $_SESSION['success'] = 'Vehicle assigned successfully.';
            $this->redirect('/admin/drivers/edit/' . $driverId);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

}
