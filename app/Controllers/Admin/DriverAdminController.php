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
}
