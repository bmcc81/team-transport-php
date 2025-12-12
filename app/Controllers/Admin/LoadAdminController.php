<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use PDO;

class LoadAdminController extends Controller
{
    /**
     * Map load_status → status (both enums).
     * load_status: pending, assigned, in_transit, delivered, cancelled
     * status:     planned, in_progress, completed, cancelled
     */
    private function mapLoadStatusToStatus(string $loadStatus): string
    {
        return match ($loadStatus) {
            'in_transit' => 'in_progress',
            'delivered'  => 'completed',
            'cancelled'  => 'cancelled',
            'assigned'   => 'planned',
            default      => 'planned', // pending or anything else
        };
    }

    /**
     * Resolve current user id from session (fallback 1 for safety).
     */
    private function currentUserId(): int
    {
        return (int)($_SESSION['user']['id']
            ?? $_SESSION['user_id']
            ?? 1);
    }

    /**
     * List loads (admin).
     * GET /admin/loads
     */
    public function index(): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->query("
            SELECT
                l.load_id,
                l.load_number,
                l.customer_id,
                l.assigned_driver_id,
                l.vehicle_id,
                l.pickup_city,
                l.delivery_city,
                l.pickup_date,
                l.delivery_date,
                l.load_status,

                c.customer_company_name,
                d.full_name AS driver_name,
                v.vehicle_number

            FROM loads l
            INNER JOIN customers c ON l.customer_id = c.id
            LEFT JOIN users d      ON l.assigned_driver_id = d.id
            LEFT JOIN vehicles v   ON l.vehicle_id = v.id
            WHERE l.deleted_at IS NULL
            ORDER BY l.created_at DESC
        ");

        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('admin/loads/index', compact('loads'));
    }

    /**
     * Show create form.
     * GET /admin/loads/create
     */
    public function create(): void
    {
        $pdo = Database::pdo();

        $customers = $pdo->query("
            SELECT id, customer_company_name
            FROM customers
            ORDER BY customer_company_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $drivers = $pdo->query("
            SELECT id, full_name
            FROM users
            WHERE role = 'driver'
            ORDER BY full_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $vehicles = $pdo->query("
            SELECT id, vehicle_number
            FROM vehicles
            ORDER BY vehicle_number
        ")->fetchAll(PDO::FETCH_ASSOC);

        $errors = [];
        $load   = null; // no existing data for create

        $this->view('admin/loads/create', compact('customers', 'drivers', 'vehicles', 'errors', 'load'));
    }

    /**
     * Store a new load.
     * POST /admin/loads/create
     */
    public function store(): void
    {
        $pdo = Database::pdo();

        $userId = $this->currentUserId();

        // Basic required validation
        $errors = [];

        $customerId = $_POST['customer_id'] ?? null;
        $pickupAddress = trim($_POST['pickup_address'] ?? '');
        $pickupCity = trim($_POST['pickup_city'] ?? '');
        $pickupDate = trim($_POST['pickup_date'] ?? '');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryCity = trim($_POST['delivery_city'] ?? '');
        $deliveryDate = trim($_POST['delivery_date'] ?? '');

        if (!$customerId)           $errors[] = "Customer is required.";
        if ($pickupAddress === '')  $errors[] = "Pickup address is required.";
        if ($pickupCity === '')     $errors[] = "Pickup city is required.";
        if ($pickupDate === '')     $errors[] = "Pickup date is required.";
        if ($deliveryAddress === '')$errors[] = "Delivery address is required.";
        if ($deliveryCity === '')   $errors[] = "Delivery city is required.";
        if ($deliveryDate === '')   $errors[] = "Delivery date is required.";

        // Status handling
        $validLoadStatuses = ['pending','assigned','in_transit','delivered','cancelled'];
        $loadStatus = $_POST['load_status'] ?? 'pending';
        if (!in_array($loadStatus, $validLoadStatuses, true)) {
            $loadStatus = 'pending';
        }
        $status = $this->mapLoadStatusToStatus($loadStatus);

        if (!empty($errors)) {
            // Re-display form with old data
            $pdo = Database::pdo();

            $customers = $pdo->query("
                SELECT id, customer_company_name
                FROM customers
                ORDER BY customer_company_name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $drivers = $pdo->query("
                SELECT id, full_name
                FROM users
                WHERE role = 'driver'
                ORDER BY full_name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $vehicles = $pdo->query("
                SELECT id, vehicle_number
                FROM vehicles
                ORDER BY vehicle_number
            ")->fetchAll(PDO::FETCH_ASSOC);

            // In create mode, we can pass $_POST as $load-like array for convenience
            $load = $_POST;

            $this->view('admin/loads/create', compact('customers', 'drivers', 'vehicles', 'errors', 'load'));
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO loads (
                load_number,
                customer_id,
                created_by_user_id,
                assigned_driver_id,
                vehicle_id,
                scheduled_start,
                scheduled_end,
                status,
                reference,
                reference_number,
                description,
                pickup_contact_name,
                pickup_address,
                pickup_city,
                pickup_postal_code,
                pickup_date,
                delivery_contact_name,
                delivery_address,
                delivery_city,
                delivery_postal_code,
                delivery_date,
                total_weight_kg,
                rate_amount,
                rate_currency,
                load_status,
                notes,
                updated_by_user_id
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $_POST['load_number']      ?: null,
            $customerId,
            $userId,
            $_POST['assigned_driver_id'] ?: null,
            $_POST['vehicle_id'] ?? null,
            $_POST['scheduled_start'] ?: null,
            $_POST['scheduled_end']   ?: null,
            $status,
            $_POST['reference']        ?: null,
            $_POST['reference_number'] ?: '',
            $_POST['description']      ?: null,
            $_POST['pickup_contact_name'] ?: null,
            $pickupAddress,
            $pickupCity,
            $_POST['pickup_postal_code'] ?: null,
            $pickupDate,
            $_POST['delivery_contact_name'] ?: null,
            $deliveryAddress,
            $deliveryCity,
            $_POST['delivery_postal_code'] ?: null,
            $deliveryDate,
            $_POST['total_weight_kg'] !== '' ? $_POST['total_weight_kg'] : null,
            $_POST['rate_amount']     !== '' ? $_POST['rate_amount'] : null,
            $_POST['rate_currency']   ?: 'CAD',
            $loadStatus,
            $_POST['notes']           ?: null,
            $userId,
        ]);

        $_SESSION['success'] = "Load created successfully.";
        $this->redirect('/admin/loads');
    }

    /**
     * Show a single load.
     * GET /admin/loads/{id}
     */
    public function show(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            SELECT
                l.*,
                c.customer_company_name,
                d.full_name      AS driver_name,
                v.vehicle_number AS vehicle_number
            FROM loads l
            INNER JOIN customers c ON l.customer_id = c.id
            LEFT JOIN users d      ON l.assigned_driver_id = d.id
            LEFT JOIN vehicles v   ON l.vehicle_id = v.id
            WHERE l.load_id = ? AND l.deleted_at IS NULL
        ");
        $stmt->execute([$id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $this->view('admin/loads/show', compact('load'));
    }

    /**
     * Edit form.
     * GET /admin/loads/{id}/edit
     */
    public function edit(int $id): void
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM loads WHERE load_id = ? AND deleted_at IS NULL");
        $stmt->execute([$id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $customers = $pdo->query("
            SELECT id, customer_company_name
            FROM customers
            ORDER BY customer_company_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $drivers = $pdo->query("
            SELECT id, full_name
            FROM users
            WHERE role = 'driver'
            ORDER BY full_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        $vehicles = $pdo->query("
            SELECT id, vehicle_number
            FROM vehicles
            ORDER BY vehicle_number
        ")->fetchAll(PDO::FETCH_ASSOC);

        $errors = [];

        $this->view('admin/loads/edit', compact('load', 'customers', 'drivers', 'vehicles', 'errors'));
    }

    /**
     * Update an existing load.
     * POST /admin/loads/{id}/edit
     */
    public function update(int $id): void
    {
        $pdo = Database::pdo();

        $userId = $this->currentUserId();

        // Re-validate important fields
        $errors = [];

        $customerId = $_POST['customer_id'] ?? null;
        $pickupAddress = trim($_POST['pickup_address'] ?? '');
        $pickupCity = trim($_POST['pickup_city'] ?? '');
        $pickupDate = trim($_POST['pickup_date'] ?? '');
        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryCity = trim($_POST['delivery_city'] ?? '');
        $deliveryDate = trim($_POST['delivery_date'] ?? '');

        if (!$customerId)           $errors[] = "Customer is required.";
        if ($pickupAddress === '')  $errors[] = "Pickup address is required.";
        if ($pickupCity === '')     $errors[] = "Pickup city is required.";
        if ($pickupDate === '')     $errors[] = "Pickup date is required.";
        if ($deliveryAddress === '')$errors[] = "Delivery address is required.";
        if ($deliveryCity === '')   $errors[] = "Delivery city is required.";
        if ($deliveryDate === '')   $errors[] = "Delivery date is required.";

        // Status handling
        $validLoadStatuses = ['pending','assigned','in_transit','delivered','cancelled'];
        $loadStatus = $_POST['load_status'] ?? 'pending';
        if (!in_array($loadStatus, $validLoadStatuses, true)) {
            $loadStatus = 'pending';
        }
        $status = $this->mapLoadStatusToStatus($loadStatus);

        if (!empty($errors)) {
            // Re-fetch select lists
            $customers = $pdo->query("
                SELECT id, customer_company_name
                FROM customers
                ORDER BY customer_company_name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $drivers = $pdo->query("
                SELECT id, full_name
                FROM users
                WHERE role = 'driver'
                ORDER BY full_name
            ")->fetchAll(PDO::FETCH_ASSOC);

            $vehicles = $pdo->query("
                SELECT id, vehicle_number
                FROM vehicles
                ORDER BY vehicle_number
            ")->fetchAll(PDO::FETCH_ASSOC);

            $load = $_POST;
            $load['load_id'] = $id;

            $this->view('admin/loads/edit', compact('load', 'customers', 'drivers', 'vehicles', 'errors'));
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE loads
            SET
                customer_id           = ?,
                assigned_driver_id    = ?,
                vehicle_id            = ?,
                scheduled_start       = ?,
                scheduled_end         = ?,
                status                = ?,
                reference             = ?,
                reference_number      = ?,
                description           = ?,
                pickup_contact_name   = ?,
                pickup_address        = ?,
                pickup_city           = ?,
                pickup_postal_code    = ?,
                pickup_date           = ?,
                delivery_contact_name = ?,
                delivery_address      = ?,
                delivery_city         = ?,
                delivery_postal_code  = ?,
                delivery_date         = ?,
                total_weight_kg       = ?,
                rate_amount           = ?,
                rate_currency         = ?,
                load_status           = ?,
                notes                 = ?,
                updated_by_user_id    = ?
            WHERE load_id = ?
        ");

        $stmt->execute([
            $customerId,
            $_POST['assigned_driver_id'] ?: null,
            $_POST['vehicle_id'] ?? null,
            $_POST['scheduled_start'] ?: null,
            $_POST['scheduled_end']   ?: null,
            $status,
            $_POST['reference']        ?: null,
            $_POST['reference_number'] ?: '',
            $_POST['description']      ?: null,
            $_POST['pickup_contact_name'] ?: null,
            $pickupAddress,
            $pickupCity,
            $_POST['pickup_postal_code'] ?: null,
            $pickupDate,
            $_POST['delivery_contact_name'] ?: null,
            $deliveryAddress,
            $deliveryCity,
            $_POST['delivery_postal_code'] ?: null,
            $deliveryDate,
            $_POST['total_weight_kg'] !== '' ? $_POST['total_weight_kg'] : null,
            $_POST['rate_amount']     !== '' ? $_POST['rate_amount'] : null,
            $_POST['rate_currency']   ?: 'CAD',
            $loadStatus,
            $_POST['notes']           ?: null,
            $userId,
            $id,
        ]);

        $_SESSION['success'] = "Load updated.";
        $this->redirect("/admin/loads/$id");
    }

    /**
     * Soft-delete load.
     * POST /admin/loads/{id}/delete
     */
    public function delete(int $id): void
    {
        $pdo = Database::pdo();
        $userId = $this->currentUserId();

        $stmt = $pdo->prepare("
            UPDATE loads
            SET deleted_at = NOW(),
                deleted_by_user_id = ?
            WHERE load_id = ?
        ");
        $stmt->execute([$userId, $id]);

        $_SESSION['success'] = "Load deleted.";
        $this->redirect('/admin/loads');
    }
}
