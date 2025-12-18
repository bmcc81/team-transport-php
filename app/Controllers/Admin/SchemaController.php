<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Database\Database;
use PDO;

final class SchemaController
{
    public function rebuild(): void
    {
        // Hard safety guard: local only + token
        $token = $_GET['token'] ?? '';
        $expected = $_ENV['REBUILD_TOKEN'] ?? '';
        $env = $_ENV['APP_ENV'] ?? 'production';
        $remote = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($env !== 'local') {
            http_response_code(403);
            echo "Forbidden (APP_ENV must be local).";
            return;
        }

        if ($remote !== '127.0.0.1' && $remote !== '::1') {
            http_response_code(403);
            echo "Forbidden (localhost only).";
            return;
        }

        if ($expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            echo "Forbidden (invalid token).";
            return;
        }

        $pdo = Database::pdo();

        // 1) Drop all tables
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");

        $tables = $pdo->query("
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = DATABASE()
              AND table_type = 'BASE TABLE'
        ")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $t) {
            $pdo->exec("DROP TABLE IF EXISTS `{$t}`");
        }

        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        // 2) Create tables (minimal schema your app uses)
        // Users (matches your existing column naming: pwd, must_change_password, created_by)
        $pdo->exec("
            CREATE TABLE `users` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `username` VARCHAR(50) NOT NULL,
              `pwd` VARCHAR(255) NOT NULL,
              `email` VARCHAR(150) NOT NULL,
              `full_name` VARCHAR(150) NOT NULL,
              `role` ENUM('admin','driver','dispatcher') NOT NULL DEFAULT 'driver',
              `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
              `created_by` INT(10) UNSIGNED NULL DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_users_username` (`username`),
              UNIQUE KEY `uq_users_email` (`email`),
              KEY `idx_users_role` (`role`),
              KEY `idx_users_created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Customers
        $pdo->exec("
            CREATE TABLE `customers` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(150) NOT NULL,
              `email` VARCHAR(150) NULL,
              `phone` VARCHAR(50) NULL,
              `address` VARCHAR(255) NULL,
              `city` VARCHAR(100) NULL,
              `postal_code` VARCHAR(20) NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_customers_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Vehicles
        $pdo->exec("
            CREATE TABLE `vehicles` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `vehicle_number` VARCHAR(50) NOT NULL,
              `license_plate` VARCHAR(50) NOT NULL,
              `status` ENUM('available','in_service','maintenance') NOT NULL DEFAULT 'available',
              `assigned_driver_id` INT(10) UNSIGNED NULL DEFAULT NULL,
              `last_lat` DECIMAL(10,7) NULL DEFAULT NULL,
              `last_lng` DECIMAL(10,7) NULL DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_vehicles_vehicle_number` (`vehicle_number`),
              UNIQUE KEY `uq_vehicles_license_plate` (`license_plate`),
              KEY `idx_vehicles_status` (`status`),
              KEY `idx_vehicles_assigned_driver_id` (`assigned_driver_id`),
              CONSTRAINT `fk_vehicles_driver`
                FOREIGN KEY (`assigned_driver_id`) REFERENCES `users`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Loads (based on your actual column list)
        $pdo->exec("
            CREATE TABLE `loads` (
              `load_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `load_number` VARCHAR(32) NULL DEFAULT NULL,
              `customer_id` INT(10) UNSIGNED NOT NULL,
              `created_by_user_id` INT(10) UNSIGNED NOT NULL,
              `assigned_driver_id` INT(10) UNSIGNED NULL DEFAULT NULL,
              `reference_number` VARCHAR(50) NOT NULL,
              `description` TEXT NULL,
              `pickup_contact_name` VARCHAR(100) NULL,
              `pickup_address` VARCHAR(255) NOT NULL,
              `pickup_city` VARCHAR(100) NOT NULL,
              `pickup_postal_code` VARCHAR(20) NULL,
              `pickup_date` DATETIME NOT NULL,
              `delivery_contact_name` VARCHAR(100) NULL,
              `delivery_address` VARCHAR(255) NOT NULL,
              `delivery_city` VARCHAR(100) NOT NULL,
              `delivery_postal_code` VARCHAR(20) NULL,
              `delivery_date` DATETIME NOT NULL,
              `total_weight_kg` DECIMAL(10,2) NULL,
              `rate_amount` DECIMAL(10,2) NULL,
              `rate_currency` CHAR(3) NOT NULL DEFAULT 'CAD',
              `load_status` ENUM('pending','assigned','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
              `notes` TEXT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`load_id`),
              UNIQUE KEY `uq_loads_load_number` (`load_number`),
              KEY `idx_loads_customer_id` (`customer_id`),
              KEY `idx_loads_created_by` (`created_by_user_id`),
              KEY `idx_loads_assigned_driver_id` (`assigned_driver_id`),
              KEY `idx_loads_status` (`load_status`),
              CONSTRAINT `fk_loads_customer`
                FOREIGN KEY (`customer_id`) REFERENCES `customers`(`id`)
                ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `fk_loads_created_by`
                FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`)
                ON DELETE RESTRICT ON UPDATE CASCADE,
              CONSTRAINT `fk_loads_assigned_driver`
                FOREIGN KEY (`assigned_driver_id`) REFERENCES `users`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Vehicle maintenance (match your current columns exactly)
        $pdo->exec("
            CREATE TABLE `vehicle_maintenance` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `vehicle_id` INT(10) UNSIGNED NOT NULL,
              `maintenance_type` VARCHAR(100) NOT NULL DEFAULT 'general',
              `status` ENUM('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
              `scheduled_date` DATE NOT NULL,
              `completed_date` DATE NULL DEFAULT NULL,
              `notes` TEXT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_vm_vehicle_id` (`vehicle_id`),
              KEY `idx_vm_status_date` (`status`,`scheduled_date`),
              CONSTRAINT `fk_vm_vehicle`
                FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Load documents (your earlier schema)
        $pdo->exec("
            CREATE TABLE `load_documents` (
              `document_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `load_id` INT(10) UNSIGNED NOT NULL,
              `uploaded_by_user_id` INT(10) UNSIGNED NOT NULL,
              `document_type` ENUM('pod','bol','other') NOT NULL DEFAULT 'pod',
              `file_path` VARCHAR(255) NOT NULL,
              `file_extension` VARCHAR(10) NOT NULL,
              `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`document_id`),
              KEY `idx_load_documents_load_id` (`load_id`),
              KEY `idx_load_documents_user_id` (`uploaded_by_user_id`),
              KEY `idx_load_documents_type` (`document_type`),
              CONSTRAINT `fk_load_documents_load`
                FOREIGN KEY (`load_id`) REFERENCES `loads` (`load_id`)
                ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_load_documents_user`
                FOREIGN KEY (`uploaded_by_user_id`) REFERENCES `users` (`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Geofences (store geojson for simplicity)
        $pdo->exec("
            CREATE TABLE `geofences` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(150) NOT NULL,
              `geojson` LONGTEXT NOT NULL,
              `is_active` TINYINT(1) NOT NULL DEFAULT 1,
              `created_by_user_id` INT(10) UNSIGNED NULL DEFAULT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_geofences_active` (`is_active`),
              KEY `idx_geofences_created_by` (`created_by_user_id`),
              CONSTRAINT `fk_geofences_created_by`
                FOREIGN KEY (`created_by_user_id`) REFERENCES `users`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        $pdo->exec("
            CREATE TABLE `geofence_alerts` (
              `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `geofence_id` INT(10) UNSIGNED NOT NULL,
              `vehicle_id` INT(10) UNSIGNED NULL DEFAULT NULL,
              `event_type` ENUM('enter','exit') NOT NULL,
              `occurred_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `payload` LONGTEXT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_ga_geofence_id` (`geofence_id`),
              KEY `idx_ga_vehicle_id` (`vehicle_id`),
              KEY `idx_ga_occurred_at` (`occurred_at`),
              CONSTRAINT `fk_ga_geofence`
                FOREIGN KEY (`geofence_id`) REFERENCES `geofences`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk_ga_vehicle`
                FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
                ON DELETE SET NULL ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // Telemetry (basic)
        $pdo->exec("
            CREATE TABLE `telemetry_points` (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `vehicle_id` INT(10) UNSIGNED NOT NULL,
              `lat` DECIMAL(10,7) NOT NULL,
              `lng` DECIMAL(10,7) NOT NULL,
              `speed_kph` DECIMAL(8,2) NULL,
              `heading` DECIMAL(8,2) NULL,
              `recorded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `raw` LONGTEXT NULL,
              PRIMARY KEY (`id`),
              KEY `idx_tp_vehicle_id` (`vehicle_id`),
              KEY `idx_tp_recorded_at` (`recorded_at`),
              CONSTRAINT `fk_tp_vehicle`
                FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`)
                ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ");

        // 3) Seed admin user
        $adminUser = 'admin';
        $adminEmail = 'admin@teamtransport.ca';
        $adminName = 'System Admin';
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, pwd, email, full_name, role, must_change_password, created_by)
            VALUES (:u, :p, :e, :n, 'admin', 1, NULL)
        ");
        $stmt->execute([
            ':u' => $adminUser,
            ':p' => $adminPass,
            ':e' => $adminEmail,
            ':n' => $adminName,
        ]);

        header('Content-Type: text/plain; charset=utf-8');
        echo "OK: DB rebuilt.\n";
        echo "Seeded admin login:\n";
        echo "  username=admin\n";
        echo "  password=admin123\n";
    }
}
