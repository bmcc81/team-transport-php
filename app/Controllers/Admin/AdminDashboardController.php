<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class AdminDashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        $stats = [];

        $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $stats['customers'] = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
        $stats['drivers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn();
        $stats['loads'] = (int)$pdo->query("SELECT COUNT(*) FROM loads")->fetchColumn();

        $this->view('admin/dashboard', ['stats' => $stats]);
    }
}
