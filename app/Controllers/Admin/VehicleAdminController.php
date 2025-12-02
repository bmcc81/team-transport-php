<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class VehicleAdminController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT * FROM vehicles ORDER BY vehicle_number ASC");
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/vehicles/index', ['vehicles' => $vehicles]);
    }
}
