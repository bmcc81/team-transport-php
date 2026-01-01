<?php

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Services\LoadActivityLogger;
use PDO;

class LoadAdminController extends Controller
{
    /**
     * Resolve current user id from session (fallback 1 for safety).
     */
    private function currentUserId(): int
    {
        return (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 1);
    }

    /**
     * Get load id from argument or GET (?id=).
     */
    private function resolveId(?int $id = null): int
    {
        if ($id && $id > 0) {
            return $id;
        }
        $qid = (int)($_GET['id'] ?? 0);
        return $qid > 0 ? $qid : 0;
    }

    /**
     * Whether the vehicle selection fields are present in the submitted form.
     * Prevents accidental "wipe all active vehicles" if the edit form doesn't include them.
     */
    private function vehiclesFieldPresent(array $post): bool
    {
        return array_key_exists('vehicle_id', $post) || array_key_exists('vehicle_ids', $post);
    }

    /**
     * Normalize vehicle ids coming from form.
     * Supports:
     *  - vehicle_id (single)
     *  - vehicle_ids[] (multi)
     */
    private function normalizeVehicleIds(array $post): array
    {
        if (!empty($post['vehicle_ids']) && is_array($post['vehicle_ids'])) {
            $ids = array_map('intval', $post['vehicle_ids']);
            $ids = array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));
            return $ids;
        }

        $single = (int)($post['vehicle_id'] ?? 0);
        return $single > 0 ? [$single] : [];
    }

    /**
     * Get current active vehicle ids for a load.
     */
    private function getActiveVehicleIds(PDO $pdo, int $loadId): array
    {
        $stmt = $pdo->prepare("
            SELECT vehicle_id
            FROM load_vehicles
            WHERE load_id = ?
              AND unassigned_at IS NULL
        ");
        $stmt->execute([$loadId]);

        $ids = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        $ids = array_map('intval', $ids);
        $ids = array_values(array_unique(array_filter($ids, fn($v) => $v > 0)));
        return $ids;
    }

    /**
     * Pivot sync: make active assigned vehicles match exactly $vehicleIds.
     * Keeps history via unassigned_at.
     *
     * Returns: ['added'=>int[], 'removed'=>int[], 'current'=>int[], 'want'=>int[]]
     */
    private function syncLoadVehicles(PDO $pdo, int $loadId, array $vehicleIds, ?int $byUserId): array
    {
        $current = $this->getActiveVehicleIds($pdo, $loadId);
        $want = array_values(array_unique(array_filter(array_map('intval', $vehicleIds), fn($v) => $v > 0)));

        $toAdd = array_values(array_diff($want, $current));
        $toRemove = array_values(array_diff($current, $want));

        if (!empty($toRemove)) {
            $in = implode(',', array_fill(0, count($toRemove), '?'));
            $params = array_merge([$loadId], $toRemove);

            $sql = "
                UPDATE load_vehicles
                SET unassigned_at = NOW()
                WHERE load_id = ?
                  AND unassigned_at IS NULL
                  AND vehicle_id IN ($in)
            ";
            $pdo->prepare($sql)->execute($params);
        }

        if (!empty($toAdd)) {
            $ins = $pdo->prepare("
                INSERT INTO load_vehicles (load_id, vehicle_id, assigned_by_user_id)
                VALUES (?, ?, ?)
            ");
            foreach ($toAdd as $vid) {
                $ins->execute([$loadId, (int)$vid, $byUserId]);
            }
        }

        return [
            'added'   => $toAdd,
            'removed' => $toRemove,
            'current' => $current,
            'want'    => $want,
        ];
    }

    /**
     * List loads (admin).
     * GET /admin/loads
     */
    public function index(): void
    {
        $pdo = Database::pdo();

        $status     = trim($_GET['status'] ?? '');
        $search     = trim($_GET['search'] ?? '');
        $unassigned = !empty($_GET['unassigned']) ? 1 : 0;

        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = max(10, (int)($_GET['per_page'] ?? 20));
        $offset   = ($page - 1) * $perPage;

        $where = [];
        $params = [];

        if ($status !== '') {
            $where[] = "l.load_status = :status";
            $params[':status'] = $status;
        }

        if ($unassigned) {
            $where[] = "l.load_status = 'pending' AND l.assigned_driver_id IS NULL";
        }

        if ($search !== '') {
            $where[] = "(
                l.load_number LIKE :q
                OR l.reference_number LIKE :q
                OR l.pickup_city LIKE :q
                OR l.delivery_city LIKE :q
                OR c.name LIKE :q
            )";
            $params[':q'] = '%' . $search . '%';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Count for pagination
        $countSql = "
            SELECT COUNT(*)
            FROM loads l
            INNER JOIN customers c ON c.id = l.customer_id
            $whereSql
        ";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRows  = (int)$countStmt->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRows / $perPage));

        // Data query (vehicles via pivot)
        $sql = "
            SELECT
                l.load_id,
                l.load_number,
                l.reference_number,
                l.customer_id,
                l.assigned_driver_id,
                l.pickup_city,
                l.delivery_city,
                l.pickup_date,
                l.delivery_date,
                l.load_status,
                l.created_at,

                c.name AS customer_company_name,
                d.full_name AS driver_name,

                GROUP_CONCAT(DISTINCT v.vehicle_number ORDER BY v.vehicle_number SEPARATOR ', ') AS vehicle_numbers
            FROM loads l
            INNER JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users d ON d.id = l.assigned_driver_id

            LEFT JOIN load_vehicles lv
                ON lv.load_id = l.load_id
               AND lv.unassigned_at IS NULL
            LEFT JOIN vehicles v ON v.id = lv.vehicle_id

            $whereSql
            GROUP BY l.load_id
            ORDER BY l.created_at DESC, l.load_id DESC
            LIMIT :limit OFFSET :offset
        ";

        $stmt = $pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $loads = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->view('admin/loads/index', [
            'loads'       => $loads,
            'status'      => $status,
            'search'      => $search,
            'unassigned'  => $unassigned,
            'page'        => $page,
            'perPage'     => $perPage,
            'totalPages'  => $totalPages,
            'totalRows'   => $totalRows,
        ]);
    }

    /**
     * Show create form.
     * GET /admin/loads/create
     */
    public function create(): void
    {
        $pdo = Database::pdo();

        $customers = $pdo->query("
            SELECT id, name AS customer_company_name
            FROM customers
            ORDER BY name
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $drivers = $pdo->query("
            SELECT id, full_name
            FROM users
            WHERE role = 'driver'
            ORDER BY full_name
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $vehicles = $pdo->query("
            SELECT id, vehicle_number, license_plate, status
            FROM vehicles
            ORDER BY vehicle_number
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $errors = [];
        $load = null;

        $this->view('admin/loads/create', compact('customers', 'drivers', 'vehicles', 'errors', 'load'));
    }

    /**
     * Store a new load.
     * POST /admin/loads/store  (adjust route as needed)
     */
    public function store(): void
    {
        $pdo = Database::pdo();
        $userId = $this->currentUserId();

        $errors = [];

        $customerId      = (int)($_POST['customer_id'] ?? 0);
        $assignedDriver  = (int)($_POST['assigned_driver_id'] ?? 0) ?: null;

        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $pickupAddress   = trim($_POST['pickup_address'] ?? '');
        $pickupCity      = trim($_POST['pickup_city'] ?? '');
        $pickupDate      = trim($_POST['pickup_date'] ?? '');

        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryCity    = trim($_POST['delivery_city'] ?? '');
        $deliveryDate    = trim($_POST['delivery_date'] ?? '');

        if ($customerId <= 0)            $errors[] = "Customer is required.";
        if ($referenceNumber === '')     $errors[] = "Reference # is required.";
        if ($pickupAddress === '')       $errors[] = "Pickup address is required.";
        if ($pickupCity === '')          $errors[] = "Pickup city is required.";
        if ($pickupDate === '')          $errors[] = "Pickup date is required.";
        if ($deliveryAddress === '')     $errors[] = "Delivery address is required.";
        if ($deliveryCity === '')        $errors[] = "Delivery city is required.";
        if ($deliveryDate === '')        $errors[] = "Delivery date is required.";

        $validLoadStatuses = ['pending','assigned','in_transit','delivered','cancelled'];
        $loadStatus = $_POST['load_status'] ?? 'pending';
        if (!in_array($loadStatus, $validLoadStatuses, true)) $loadStatus = 'pending';

        if (!empty($errors)) {
            // Re-display form with lists and old input
            $customers = $pdo->query("
                SELECT id, name AS customer_company_name
                FROM customers
                ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $drivers = $pdo->query("
                SELECT id, full_name
                FROM users
                WHERE role = 'driver'
                ORDER BY full_name
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $vehicles = $pdo->query("
                SELECT id, vehicle_number, license_plate, status
                FROM vehicles
                ORDER BY vehicle_number
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $load = $_POST;
            $this->view('admin/loads/create', compact('customers', 'drivers', 'vehicles', 'errors', 'load'));
            return;
        }

        $vehicleIds = $this->normalizeVehicleIds($_POST);

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO loads (
                    load_number,
                    customer_id,
                    created_by_user_id,
                    assigned_driver_id,
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
                    notes
                ) VALUES (
                    :load_number,
                    :customer_id,
                    :created_by_user_id,
                    :assigned_driver_id,
                    :reference_number,
                    :description,
                    :pickup_contact_name,
                    :pickup_address,
                    :pickup_city,
                    :pickup_postal_code,
                    :pickup_date,
                    :delivery_contact_name,
                    :delivery_address,
                    :delivery_city,
                    :delivery_postal_code,
                    :delivery_date,
                    :total_weight_kg,
                    :rate_amount,
                    :rate_currency,
                    :load_status,
                    :notes
                )
            ");

            $stmt->execute([
                ':load_number'          => ($_POST['load_number'] ?? '') !== '' ? trim($_POST['load_number']) : null,
                ':customer_id'          => $customerId,
                ':created_by_user_id'   => $userId,
                ':assigned_driver_id'   => $assignedDriver,
                ':reference_number'     => $referenceNumber,
                ':description'          => ($_POST['description'] ?? '') !== '' ? trim($_POST['description']) : null,

                ':pickup_contact_name'  => ($_POST['pickup_contact_name'] ?? '') !== '' ? trim($_POST['pickup_contact_name']) : null,
                ':pickup_address'       => $pickupAddress,
                ':pickup_city'          => $pickupCity,
                ':pickup_postal_code'   => ($_POST['pickup_postal_code'] ?? '') !== '' ? trim($_POST['pickup_postal_code']) : null,
                ':pickup_date'          => $pickupDate,

                ':delivery_contact_name'=> ($_POST['delivery_contact_name'] ?? '') !== '' ? trim($_POST['delivery_contact_name']) : null,
                ':delivery_address'     => $deliveryAddress,
                ':delivery_city'        => $deliveryCity,
                ':delivery_postal_code' => ($_POST['delivery_postal_code'] ?? '') !== '' ? trim($_POST['delivery_postal_code']) : null,
                ':delivery_date'        => $deliveryDate,

                ':total_weight_kg'      => ($_POST['total_weight_kg'] ?? '') !== '' ? (float)$_POST['total_weight_kg'] : null,
                ':rate_amount'          => ($_POST['rate_amount'] ?? '') !== '' ? (float)$_POST['rate_amount'] : null,
                ':rate_currency'        => ($_POST['rate_currency'] ?? '') !== '' ? trim($_POST['rate_currency']) : 'CAD',
                ':load_status'          => $loadStatus,
                ':notes'                => ($_POST['notes'] ?? '') !== '' ? trim($_POST['notes']) : null,
            ]);

            $loadId = (int)$pdo->lastInsertId();

            // Vehicles via pivot
            $sync = ['added'=>[], 'removed'=>[], 'current'=>[], 'want'=>[]];
            if (!empty($vehicleIds)) {
                $sync = $this->syncLoadVehicles($pdo, $loadId, $vehicleIds, $userId);
            }

            // Activity log
            LoadActivityLogger::log($loadId, 'created', 'Load created. Ref: ' . $referenceNumber, $userId);

            if (!empty($assignedDriver)) {
                LoadActivityLogger::log($loadId, 'assigned_driver', 'Driver assigned (user_id=' . (int)$assignedDriver . ')', $userId);
            }

            if (!empty($sync['added'])) {
                LoadActivityLogger::log($loadId, 'assigned_vehicles', 'Vehicles assigned: ' . implode(',', $sync['added']), $userId);
            }

            $pdo->commit();

            $_SESSION['success'] = "Load created successfully.";
            $this->redirect('/admin/loads');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Show a single load.
     * GET /admin/loads/view?id=123  (or route param)
     */
    public function show(?int $id = null): void
    {
        $id = $this->resolveId($id);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $pdo = Database::pdo();

        // Load + customer + driver + active vehicle numbers
        $stmt = $pdo->prepare("
            SELECT
                l.*,
                c.name AS customer_company_name,
                d.full_name AS driver_name,
                GROUP_CONCAT(DISTINCT v.vehicle_number ORDER BY v.vehicle_number SEPARATOR ', ') AS vehicle_numbers
            FROM loads l
            INNER JOIN customers c ON c.id = l.customer_id
            LEFT JOIN users d ON d.id = l.assigned_driver_id
            LEFT JOIN load_vehicles lv
                ON lv.load_id = l.load_id
               AND lv.unassigned_at IS NULL
            LEFT JOIN vehicles v ON v.id = lv.vehicle_id
            WHERE l.load_id = ?
            GROUP BY l.load_id
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        // Stops (schema: load_routes)
        $stmtStops = $pdo->prepare("
            SELECT *
            FROM load_routes
            WHERE load_id = ?
            ORDER BY stop_sequence ASC
        ");
        $stmtStops->execute([$id]);
        $stops = $stmtStops->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Activity log
        $stmtLog = $pdo->prepare("
            SELECT al.*, u.full_name AS user_name
            FROM load_activity_log al
            LEFT JOIN users u ON u.id = al.performed_by_user_id
            WHERE al.load_id = ?
            ORDER BY al.created_at DESC
            LIMIT 100
        ");
        $stmtLog->execute([$id]);
        $activities = $stmtLog->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $this->view('admin/loads/show', compact('load', 'stops', 'activities'));
    }

    /**
     * Edit form.
     * GET /admin/loads/edit?id=123  (or route param)
     */
    public function edit(?int $id = null): void
    {
        $id = $this->resolveId($id);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("SELECT * FROM loads WHERE load_id = ?");
        $stmt->execute([$id]);
        $load = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$load) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $customers = $pdo->query("
            SELECT id, name AS customer_company_name
            FROM customers
            ORDER BY name
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $drivers = $pdo->query("
            SELECT id, full_name
            FROM users
            WHERE role = 'driver'
            ORDER BY full_name
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $vehicles = $pdo->query("
            SELECT id, vehicle_number, license_plate, status
            FROM vehicles
            ORDER BY vehicle_number
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $activeVehiclesStmt = $pdo->prepare("
            SELECT
                lv.vehicle_id,
                v.vehicle_number,
                v.license_plate,
                v.status,
                lv.assigned_at
            FROM load_vehicles lv
            INNER JOIN vehicles v ON v.id = lv.vehicle_id
            WHERE lv.load_id = ?
              AND lv.unassigned_at IS NULL
            ORDER BY lv.assigned_at DESC
        ");
        $activeVehiclesStmt->execute([$id]);
        $activeVehicles = $activeVehiclesStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $selectedVehicleIds = array_map('intval', array_column($activeVehicles, 'vehicle_id'));

        $errors = [];

        $this->view('admin/loads/edit', compact(
            'load',
            'customers',
            'drivers',
            'vehicles',
            'activeVehicles',
            'selectedVehicleIds',
            'errors'
        ));
    }

    /**
     * Update an existing load.
     * POST /admin/loads/update?id=123  (or route param)
     */
    public function update(?int $id = null): void
    {
        $id = $this->resolveId($id);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $pdo = Database::pdo();
        $userId = $this->currentUserId();

        // BEFORE state (for logging)
        $beforeStmt = $pdo->prepare("
            SELECT assigned_driver_id, load_status
            FROM loads
            WHERE load_id = ?
            LIMIT 1
        ");
        $beforeStmt->execute([$id]);
        $before = $beforeStmt->fetch(PDO::FETCH_ASSOC) ?: ['assigned_driver_id' => null, 'load_status' => null];

        $errors = [];

        $customerId      = (int)($_POST['customer_id'] ?? 0);
        $assignedDriver  = (int)($_POST['assigned_driver_id'] ?? 0) ?: null;

        $referenceNumber = trim($_POST['reference_number'] ?? '');
        $pickupAddress   = trim($_POST['pickup_address'] ?? '');
        $pickupCity      = trim($_POST['pickup_city'] ?? '');
        $pickupDate      = trim($_POST['pickup_date'] ?? '');

        $deliveryAddress = trim($_POST['delivery_address'] ?? '');
        $deliveryCity    = trim($_POST['delivery_city'] ?? '');
        $deliveryDate    = trim($_POST['delivery_date'] ?? '');

        if ($customerId <= 0)            $errors[] = "Customer is required.";
        if ($referenceNumber === '')     $errors[] = "Reference # is required.";
        if ($pickupAddress === '')       $errors[] = "Pickup address is required.";
        if ($pickupCity === '')          $errors[] = "Pickup city is required.";
        if ($pickupDate === '')          $errors[] = "Pickup date is required.";
        if ($deliveryAddress === '')     $errors[] = "Delivery address is required.";
        if ($deliveryCity === '')        $errors[] = "Delivery city is required.";
        if ($deliveryDate === '')        $errors[] = "Delivery date is required.";

        $validLoadStatuses = ['pending','assigned','in_transit','delivered','cancelled'];
        $loadStatus = $_POST['load_status'] ?? 'pending';
        if (!in_array($loadStatus, $validLoadStatuses, true)) $loadStatus = 'pending';

        // Vehicles
        $vehicleFieldPresent = $this->vehiclesFieldPresent($_POST);
        $vehicleIds = $vehicleFieldPresent ? $this->normalizeVehicleIds($_POST) : [];

        if (!empty($errors)) {
            // Re-render edit with lists and posted data
            $customers = $pdo->query("
                SELECT id, name AS customer_company_name
                FROM customers
                ORDER BY name
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $drivers = $pdo->query("
                SELECT id, full_name
                FROM users
                WHERE role = 'driver'
                ORDER BY full_name
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $vehicles = $pdo->query("
                SELECT id, vehicle_number, license_plate, status
                FROM vehicles
                ORDER BY vehicle_number
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $load = $_POST;
            $load['load_id'] = $id;

            // For the edit view helpers
            $activeVehicles = [];
            $selectedVehicleIds = $vehicleFieldPresent ? $vehicleIds : [];

            $this->view('admin/loads/edit', compact(
                'load',
                'customers',
                'drivers',
                'vehicles',
                'activeVehicles',
                'selectedVehicleIds',
                'errors'
            ));
            return;
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                UPDATE loads
                SET
                    load_number           = :load_number,
                    customer_id           = :customer_id,
                    assigned_driver_id    = :assigned_driver_id,
                    reference_number      = :reference_number,
                    description           = :description,
                    pickup_contact_name   = :pickup_contact_name,
                    pickup_address        = :pickup_address,
                    pickup_city           = :pickup_city,
                    pickup_postal_code    = :pickup_postal_code,
                    pickup_date           = :pickup_date,
                    delivery_contact_name = :delivery_contact_name,
                    delivery_address      = :delivery_address,
                    delivery_city         = :delivery_city,
                    delivery_postal_code  = :delivery_postal_code,
                    delivery_date         = :delivery_date,
                    total_weight_kg       = :total_weight_kg,
                    rate_amount           = :rate_amount,
                    rate_currency         = :rate_currency,
                    load_status           = :load_status,
                    notes                 = :notes
                WHERE load_id = :id
            ");

            $stmt->execute([
                ':load_number'           => ($_POST['load_number'] ?? '') !== '' ? trim($_POST['load_number']) : null,
                ':customer_id'           => $customerId,
                ':assigned_driver_id'    => $assignedDriver,
                ':reference_number'      => $referenceNumber,
                ':description'           => ($_POST['description'] ?? '') !== '' ? trim($_POST['description']) : null,

                ':pickup_contact_name'   => ($_POST['pickup_contact_name'] ?? '') !== '' ? trim($_POST['pickup_contact_name']) : null,
                ':pickup_address'        => $pickupAddress,
                ':pickup_city'           => $pickupCity,
                ':pickup_postal_code'    => ($_POST['pickup_postal_code'] ?? '') !== '' ? trim($_POST['pickup_postal_code']) : null,
                ':pickup_date'           => $pickupDate,

                ':delivery_contact_name' => ($_POST['delivery_contact_name'] ?? '') !== '' ? trim($_POST['delivery_contact_name']) : null,
                ':delivery_address'      => $deliveryAddress,
                ':delivery_city'         => $deliveryCity,
                ':delivery_postal_code'  => ($_POST['delivery_postal_code'] ?? '') !== '' ? trim($_POST['delivery_postal_code']) : null,
                ':delivery_date'         => $deliveryDate,

                ':total_weight_kg'       => ($_POST['total_weight_kg'] ?? '') !== '' ? (float)$_POST['total_weight_kg'] : null,
                ':rate_amount'           => ($_POST['rate_amount'] ?? '') !== '' ? (float)$_POST['rate_amount'] : null,
                ':rate_currency'         => ($_POST['rate_currency'] ?? '') !== '' ? trim($_POST['rate_currency']) : 'CAD',
                ':load_status'           => $loadStatus,
                ':notes'                 => ($_POST['notes'] ?? '') !== '' ? trim($_POST['notes']) : null,
                ':id'                    => $id,
            ]);

            // Vehicles via pivot (sync only if the field is actually present)
            $sync = ['added'=>[], 'removed'=>[], 'current'=>[], 'want'=>[]];
            if ($vehicleFieldPresent) {
                $sync = $this->syncLoadVehicles($pdo, $id, $vehicleIds, $userId);
            }

            // Activity log
            LoadActivityLogger::log($id, 'updated', 'Load updated.', $userId);

            if ((int)($before['assigned_driver_id'] ?? 0) !== (int)($assignedDriver ?? 0)) {
                LoadActivityLogger::log(
                    $id,
                    'assigned_driver_changed',
                    'Driver changed from ' . (int)($before['assigned_driver_id'] ?? 0) . ' to ' . (int)($assignedDriver ?? 0),
                    $userId
                );
            }

            if (($before['load_status'] ?? '') !== $loadStatus) {
                LoadActivityLogger::log(
                    $id,
                    'status_changed',
                    'Status changed from ' . ($before['load_status'] ?? '') . ' to ' . $loadStatus,
                    $userId
                );
            }

            if ($vehicleFieldPresent) {
                if (!empty($sync['added'])) {
                    LoadActivityLogger::log($id, 'vehicle_assigned', 'Vehicles added: ' . implode(',', $sync['added']), $userId);
                }
                if (!empty($sync['removed'])) {
                    LoadActivityLogger::log($id, 'vehicle_unassigned', 'Vehicles removed: ' . implode(',', $sync['removed']), $userId);
                }
                LoadActivityLogger::log(
                    $id,
                    'vehicles_synced',
                    'Active vehicles now: ' . (empty($sync['want']) ? '(none)' : implode(',', $sync['want'])),
                    $userId
                );
            }

            $pdo->commit();

            $_SESSION['success'] = "Load updated.";
            $this->redirect('/admin/loads/view?id=' . $id);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Delete load (hard delete; your loads table has no deleted_at).
     * POST /admin/loads/delete?id=123  (or route param)
     */
    public function delete(?int $id = null): void
    {
        $id = $this->resolveId($id);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found.";
            return;
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("DELETE FROM loads WHERE load_id = ?");
        $stmt->execute([$id]);

        LoadActivityLogger::log($id, 'deleted', 'Load deleted.', $this->currentUserId());

        $_SESSION['success'] = "Load deleted.";
        $this->redirect('/admin/loads');
    }

    /**
     * Assign a vehicle (adds an active row in load_vehicles).
     * POST /admin/loads/assign-vehicle
     */
    public function assignVehicle(): void
    {
        $loadId    = (int)($_POST['load_id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        if ($loadId <= 0 || $vehicleId <= 0) {
            $this->back();
            return;
        }

        $pdo = Database::pdo();
        $userId = $this->currentUserId();

        // Avoid duplicate active assignment
        $chk = $pdo->prepare("
            SELECT COUNT(*)
            FROM load_vehicles
            WHERE load_id = ?
              AND vehicle_id = ?
              AND unassigned_at IS NULL
        ");
        $chk->execute([$loadId, $vehicleId]);

        if ((int)$chk->fetchColumn() === 0) {
            $ins = $pdo->prepare("
                INSERT INTO load_vehicles (load_id, vehicle_id, assigned_by_user_id)
                VALUES (?, ?, ?)
            ");
            $ins->execute([$loadId, $vehicleId, $userId]);

            LoadActivityLogger::log($loadId, 'vehicle_assigned', 'Vehicle assigned: ' . $vehicleId, $userId);
        }

        $_SESSION['success'] = 'Vehicle assigned.';
        $this->redirect('/admin/loads/edit?id=' . $loadId);
    }

    /**
     * Unassign a vehicle (closes active row in load_vehicles).
     * POST /admin/loads/unassign-vehicle
     */
    public function unassignVehicle(): void
    {
        $loadId    = (int)($_POST['load_id'] ?? 0);
        $vehicleId = (int)($_POST['vehicle_id'] ?? 0);

        if ($loadId <= 0 || $vehicleId <= 0) {
            $this->back();
            return;
        }

        $pdo = Database::pdo();

        $stmt = $pdo->prepare("
            UPDATE load_vehicles
            SET unassigned_at = NOW()
            WHERE load_id = ?
              AND vehicle_id = ?
              AND unassigned_at IS NULL
        ");
        $stmt->execute([$loadId, $vehicleId]);

        LoadActivityLogger::log($loadId, 'vehicle_unassigned', 'Vehicle unassigned: ' . $vehicleId, $this->currentUserId());

        $_SESSION['success'] = 'Vehicle unassigned.';
        $this->redirect('/admin/loads/edit?id=' . $loadId);
    }
}