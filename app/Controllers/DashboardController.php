<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Database\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        $stats = [
            'loads_total'     => 0,
            'loads_pending'   => 0,
            'loads_transit'   => 0,
            'loads_delivered' => 0,
        ];

        $rows = $pdo->query("SELECT load_status, COUNT(*) AS cnt FROM loads GROUP BY load_status")->fetchAll();
        foreach ($rows as $row) {
            $status = $row['load_status'];
            $cnt = (int)$row['cnt'];
            $stats['loads_total'] += $cnt;
            if ($status === 'pending') {
                $stats['loads_pending'] = $cnt;
            } elseif ($status === 'in_transit') {
                $stats['loads_transit'] = $cnt;
            } elseif ($status === 'delivered') {
                $stats['loads_delivered'] = $cnt;
            }
        }

        $this->view('dashboard/index', ['stats' => $stats]);
    }
}
