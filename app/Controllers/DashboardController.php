<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();

        /**
         * ======================
         * LOAD STATS
         * ======================
         */
        $stats = [
            'loads_total'        => 0,
            'loads_pending'      => 0,
            'loads_transit'      => 0,
            'loads_delivered'    => 0,

            'vehicles_total'     => 0,
            'vehicles_available' => 0,
            'vehicles_maintenance' => 0,
            'drivers_total'     => 0,
            'drivers_available' => 0,
            'drivers_assigned'  => 0,
        ];

        // --- Loads aggregation
        $loadRows = $pdo->query("
            SELECT load_status, COUNT(*) AS cnt
            FROM loads
            GROUP BY load_status
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($loadRows as $row) {
            $cnt = (int) $row['cnt'];
            $stats['loads_total'] += $cnt;

            switch ($row['load_status']) {
                case 'pending':
                    $stats['loads_pending'] = $cnt;
                    break;
                case 'in_transit':
                    $stats['loads_transit'] = $cnt;
                    break;
                case 'delivered':
                    $stats['loads_delivered'] = $cnt;
                    break;
            }
        }

        /**
         * ======================
         * VEHICLE STATS
         * ======================
         */
        $vehicleRows = $pdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM vehicles
            GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vehicleRows as $row) {
            $cnt = (int) $row['cnt'];
            $stats['vehicles_total'] += $cnt;

            switch ($row['status']) {
                case 'available':
                    $stats['vehicles_available'] = $cnt;
                    break;
                case 'maintenance':
                    $stats['vehicles_maintenance'] = $cnt;
                    break;
            }
        }

        /**
         * ======================
         * DRIVER STATS
         * ======================
         */
        $driverRows = $pdo->query("
            SELECT status, COUNT(*) AS cnt
            FROM users
            WHERE role = 'driver'
            GROUP BY status
        ")->fetchAll(PDO::FETCH_ASSOC);

        foreach ($driverRows as $row) {
            $cnt = (int) $row['cnt'];
            $stats['drivers_total'] += $cnt;

            switch ($row['status']) {
                case 'available':
                    $stats['drivers_available'] = $cnt;
                    break;
                case 'assigned':
                    $stats['drivers_assigned'] = $cnt;
                    break;
            }
        }

        $this->view('dashboard/index', compact('stats'));
    }
}
