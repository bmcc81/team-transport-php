<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class CustomerAdminController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT * FROM customers ORDER BY customer_company_name ASC");
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/customers/index', ['customers' => $customers]);
    }
}
