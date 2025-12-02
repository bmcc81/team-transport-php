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
        $stmt = $pdo->query("SELECT * FROM users WHERE role = 'driver' ORDER BY full_name ASC");
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/drivers/index', ['drivers' => $drivers]);
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

}
