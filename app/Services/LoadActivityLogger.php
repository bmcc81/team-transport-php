<?php
declare(strict_types=1);

namespace App\Services;

use App\Database\Database;
use PDO;

final class LoadActivityLogger
{
    /**
     * Write a single activity record for a load.
     *
     * @param int         $loadId      loads.load_id
     * @param string      $action      short action key (e.g., "created", "updated", "assigned_driver")
     * @param string|null $description optional human-readable details
     * @param int|null    $userId      users.id of the actor (nullable)
     */
    public static function log(int $loadId, string $action, ?string $description = null, ?int $userId = null): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            INSERT INTO load_activity_log (load_id, action, description, performed_by_user_id)
            VALUES (:load_id, :action, :description, :user_id)
        ");

        $stmt->execute([
            ':load_id'     => $loadId,
            ':action'      => $action,
            ':description' => $description,
            ':user_id'     => $userId,
        ]);
    }

    /**
     * Convenience helper: auto-detect user id from session if available.
     * Uses either $_SESSION['user']['id'] (your common pattern) or $_SESSION['user_id'].
     */
    public static function logWithSessionUser(int $loadId, string $action, ?string $description = null): void
    {
        $userId = null;

        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!empty($_SESSION['user']['id'])) {
                $userId = (int)$_SESSION['user']['id'];
            } elseif (!empty($_SESSION['user_id'])) {
                $userId = (int)$_SESSION['user_id'];
            }
        }

        self::log($loadId, $action, $description, $userId);
    }
}
