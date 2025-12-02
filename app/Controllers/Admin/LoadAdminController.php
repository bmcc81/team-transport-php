<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class LoadAdminController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        $loads = [];
        try {
            $stmt = $pdo->query("
                SELECT l.*, 
                       c.customer_company_name,
                       u.full_name AS driver_name
                FROM loads l
                LEFT JOIN customers c ON c.id = l.customer_id
                LEFT JOIN users u ON u.id = l.assigned_driver_id
                ORDER BY l.created_at DESC
                LIMIT 200
            ");
            $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {}

        $this->view('admin/loads/index', ['loads' => $loads]);
    }
}