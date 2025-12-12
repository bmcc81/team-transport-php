<?php
namespace App\Services;

use App\Database\Database;
use PDO;

class LoadActivityLogger
{
    public static function log(int $loadId, string $action, ?string $description = null, ?int $userId = null): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO load_activity_log (load_id, action, description, performed_by_user_id)
            VALUES (:load_id, :action, :description, :user_id)
        ");

        $stmt->execute([
            'load_id'    => $loadId,
            'action'     => $action,
            'description'=> $description,
            'user_id'    => $userId,
        ]);
    }

    public function show(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT l.*,
                c.customer_company_name,
                u.full_name AS driver_name,
                v.vehicle_number
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            LEFT JOIN vehicles v ON v.id = l.vehicle_id
            WHERE l.load_id = :id
            AND l.deleted_at IS NULL
        ");
        $stmt->execute(['id' => $id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $stmtStops = $pdo->prepare("
            SELECT *
            FROM load_stops
            WHERE load_id = :id
            ORDER BY sequence ASC
        ");
        $stmtStops->execute(['id' => $id]);
        $stops = $stmtStops->fetchAll(PDO::FETCH_ASSOC);

        $stmtLog = $pdo->prepare("
            SELECT l.*, u.full_name AS user_name
            FROM load_activity_log l
            LEFT JOIN users u ON u.id = l.performed_by_user_id
            WHERE l.load_id = :id
            ORDER BY l.created_at DESC
            LIMIT 100
        ");
        $stmtLog->execute(['id' => $id]);
        $activities = $stmtLog->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/loads/show', compact('load', 'stops', 'activities'));
    }

    public function calendar(): void
    {
        $pdo = Database::pdo();

        // Next 14 days
        $stmt = $pdo->query("
            SELECT l.load_id, l.load_number, l.scheduled_start, l.scheduled_end,
                l.status,
                c.customer_company_name,
                u.full_name AS driver_name,
                v.vehicle_number
            FROM loads l
            LEFT JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users u ON u.id = l.assigned_driver_id
            LEFT JOIN vehicles v ON v.id = l.vehicle_id
            WHERE l.deleted_at IS NULL
            AND l.scheduled_start IS NOT NULL
            AND l.scheduled_end IS NOT NULL
            AND l.scheduled_end >= NOW() - INTERVAL 1 DAY
            AND l.scheduled_start <= NOW() + INTERVAL 14 DAY
            ORDER BY l.scheduled_start ASC
        ");

        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/loads/calendar', compact('loads'));
    }


}
