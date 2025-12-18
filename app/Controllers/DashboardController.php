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

        $stats = [
            // Loads
            'loads_total'       => 0,
            'loads_pending'     => 0,
            'loads_transit'     => 0,
            'loads_delivered'   => 0,

            // Vehicles
            'vehicles_total'        => 0,
            'vehicles_available'    => 0,
            'vehicles_in_service'   => 0,
            'vehicles_maintenance'  => 0,

            // Drivers
            'drivers_total'     => 0,
            'drivers_available' => 0,
            'drivers_assigned'  => 0,

            // Other
            'unassigned_loads'  => 0,
        ];

        /**
         * ======================
         * UNASSIGNED LOADS
         * ======================
         * Pending + no assigned driver
         */
        $stats['unassigned_loads'] = (int) $pdo->query("
            SELECT COUNT(*)
            FROM loads
            WHERE load_status = 'pending'
              AND assigned_driver_id IS NULL
        ")->fetchColumn();

        /**
         * ======================
         * LOAD STATUS COUNTS
         * ======================
         */
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

                // Other statuses (like 'assigned') are counted in loads_total only
                default:
                    break;
            }
        }

        /**
         * ======================
         * VEHICLE STATUS COUNTS
         * ======================
         * vehicles.status: ('available','in_service','maintenance')
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

                case 'in_service':
                    $stats['vehicles_in_service'] = $cnt;
                    break;

                case 'maintenance':
                    $stats['vehicles_maintenance'] = $cnt;
                    break;

                default:
                    break;
            }
        }

        /**
         * ======================
         * DRIVER STATS (DERIVED)
         * ======================
         * users table has NO status column, so derive "assigned" from loads.
         */
        $stats['drivers_total'] = (int) $pdo->query("
            SELECT COUNT(*)
            FROM users
            WHERE role = 'driver'
        ")->fetchColumn();

        // Distinct drivers currently tied to active work
        $stats['drivers_assigned'] = (int) $pdo->query("
            SELECT COUNT(DISTINCT assigned_driver_id)
            FROM loads
            WHERE assigned_driver_id IS NOT NULL
              AND load_status IN ('assigned','in_transit')
        ")->fetchColumn();

        $stats['drivers_available'] = max(0, $stats['drivers_total'] - $stats['drivers_assigned']);

        $this->view('dashboard/index', compact('stats'));
    }
}
