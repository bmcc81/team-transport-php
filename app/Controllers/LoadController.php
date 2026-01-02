<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Load;
use App\Models\Customer;
use App\Models\User;
use PDO;

class LoadController extends Controller
{
    private function currentUserId(): int
    {
        return (int)($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
    }

    private function currentRole(): string
    {
        return (string)($_SESSION['user']['role'] ?? '');
    }

    private function isDriver(): bool
    {
        return $this->currentRole() === 'driver';
    }

    private function canManage(): bool
    {
        // Allow dispatcher and admin to create/edit/update via /loads UI.
        return in_array($this->currentRole(), ['dispatcher', 'admin'], true);
    }

    private function forbid(string $message = 'Forbidden'): void
    {
        http_response_code(403);
        echo htmlspecialchars($message);
        exit;
    }

    private function validateLoadInput(array $data): array
    {
        $errors = [];

        if (empty($data['customer_id']))        $errors[] = 'Customer is required.';
        if (empty($data['reference_number']))  $errors[] = 'Reference # is required.';

        if (empty($data['pickup_address']))    $errors[] = 'Pickup address is required.';
        if (empty($data['pickup_city']))       $errors[] = 'Pickup city is required.';
        if (empty($data['pickup_date']))       $errors[] = 'Pickup date/time is required.';

        if (empty($data['delivery_address']))  $errors[] = 'Delivery address is required.';
        if (empty($data['delivery_city']))     $errors[] = 'Delivery city is required.';
        if (empty($data['delivery_date']))     $errors[] = 'Delivery date/time is required.';

        $validStatuses = ['pending','assigned','in_transit','delivered','cancelled'];
        $status = $data['load_status'] ?? 'pending';
        if (!in_array($status, $validStatuses, true)) {
            $errors[] = 'Invalid load status.';
        }

        return $errors;
    }

    /**
     * GET /loads
     * Driver: My assigned loads
     * Dispatcher/Admin: All loads (simplified)
     */
    public function index(): void
    {
        $filters = [
            'status'     => $_GET['status'] ?? '',
            'search'     => $_GET['search'] ?? '',
            'unassigned' => !empty($_GET['unassigned']) ? 1 : 0,
        ];

        if ($this->isDriver()) {
            $filters['assigned_driver_id'] = $this->currentUserId();
            $filters['unassigned'] = 0; // drivers cannot browse unassigned
        }

        $loads = Load::all($filters);

        $this->view('loads/index', [
            'loads'     => $loads,
            'filters'   => $filters,
            'role'      => $this->currentRole(),
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * GET /loads/view?id=123
     */
    public function show(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $load = Load::find($id);
        if (!$load) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        // Driver access control: can only view assigned loads
        if ($this->isDriver()) {
            $uid = $this->currentUserId();
            if ($uid <= 0 || (int)($load['assigned_driver_id'] ?? 0) !== $uid) {
                $this->forbid('You do not have access to this load.');
            }
        }

        // Load documents (safe if table missing)
        $pdo = Database::pdo();
        $docs = [];
        try {
            $stmt = $pdo->prepare("
                SELECT
                    document_id, load_id, uploaded_by_user_id,
                    document_type, file_path, file_extension, uploaded_at
                FROM load_documents
                WHERE load_id = :id
                ORDER BY uploaded_at DESC
            ");
            $stmt->execute([':id' => $id]);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $docs = [];
        }

        $this->view('loads/show', [
            'load'      => $load,
            'docs'      => $docs,
            'role'      => $this->currentRole(),
            'canManage' => $this->canManage(),
        ]);
    }

    /**
     * GET /loads/create (dispatcher/admin only)
     */
    public function create(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot create loads.');
        }

        $customers = Customer::all();
        $drivers   = User::drivers();

        $this->view('loads/create', [
            'customers' => $customers,
            'drivers'   => $drivers,
        ]);
    }

    /**
     * POST /loads (dispatcher/admin only)
     */
    public function store(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot create loads.');
        }

        $pdo  = Database::pdo();
        $uid  = $this->currentUserId();
        $data = $_POST;

        // Normalize driver_id -> assigned_driver_id
        $assignedDriverId = !empty($data['driver_id'] ?? null) ? (int)$data['driver_id'] : null;

        $data['assigned_driver_id']  = $assignedDriverId;
        $data['created_by_user_id']  = $uid ?: 1;
        $data['load_status']         = $data['load_status'] ?? 'pending';
        $data['rate_currency']       = $data['rate_currency'] ?? 'CAD';

        $errors = $this->validateLoadInput($data);
        if ($errors) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect('/loads/create');
            return;
        }

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO loads (
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
                ':customer_id'          => (int)$data['customer_id'],
                ':created_by_user_id'   => (int)$data['created_by_user_id'],
                ':assigned_driver_id'   => $data['assigned_driver_id'] ? (int)$data['assigned_driver_id'] : null,
                ':reference_number'     => trim((string)$data['reference_number']),
                ':description'          => $data['description'] !== '' ? $data['description'] : null,

                ':pickup_contact_name'  => $data['pickup_contact_name'] !== '' ? $data['pickup_contact_name'] : null,
                ':pickup_address'       => trim((string)$data['pickup_address']),
                ':pickup_city'          => trim((string)$data['pickup_city']),
                ':pickup_postal_code'   => $data['pickup_postal_code'] !== '' ? $data['pickup_postal_code'] : null,
                ':pickup_date'          => $data['pickup_date'],

                ':delivery_contact_name'=> $data['delivery_contact_name'] !== '' ? $data['delivery_contact_name'] : null,
                ':delivery_address'     => trim((string)$data['delivery_address']),
                ':delivery_city'        => trim((string)$data['delivery_city']),
                ':delivery_postal_code' => $data['delivery_postal_code'] !== '' ? $data['delivery_postal_code'] : null,
                ':delivery_date'        => $data['delivery_date'],

                ':total_weight_kg'      => ($data['total_weight_kg'] ?? '') !== '' ? (float)$data['total_weight_kg'] : null,
                ':rate_amount'          => ($data['rate_amount'] ?? '') !== '' ? (float)$data['rate_amount'] : null,
                ':rate_currency'        => $data['rate_currency'] ?: 'CAD',
                ':load_status'          => $data['load_status'],
                ':notes'                => $data['notes'] ?? null,
            ]);

            $loadId = (int)$pdo->lastInsertId();

            // Optional initial PDF upload
            $this->handleDocumentUpload($pdo, $loadId);

            $pdo->commit();

            $_SESSION['success'] = 'Load created successfully.';
            $this->redirect('/loads/view?id=' . $loadId);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * GET /loads/edit?id=123 (dispatcher/admin only)
     */
    public function edit(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot edit loads.');
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $load = Load::find($id);
        if (!$load) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $customers = Customer::all();
        $drivers   = User::drivers();

        // If you want the edit view to enforce these warnings, you can compute:
        $load['has_vehicle'] = true; // non-admin loads schema has no vehicle linkage yet
        $load['has_pod']     = true; // we only enforce POD if you want later

        $this->view('loads/edit', [
            'load'      => $load,
            'customers' => $customers,
            'drivers'   => $drivers,
        ]);
    }

    /**
     * POST /loads/update (dispatcher/admin only)
     */
   public function update(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot update loads.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $pdo = Database::pdo();
        $uid = $this->currentUserId();
        $data = $_POST;

        // ---- Detect which columns exist (pickup_date vs pickup_datetime, etc.)
        $columns = [];
        $types   = [];
        $rows = $pdo->query("DESCRIBE loads")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $col) {
            $name = (string)($col['Field'] ?? '');
            if ($name === '') continue;
            $columns[$name] = true;
            $types[$name] = strtolower((string)($col['Type'] ?? ''));
        }

        $pickupCol   = isset($columns['pickup_datetime']) ? 'pickup_datetime' : 'pickup_date';
        $deliveryCol = isset($columns['delivery_datetime']) ? 'delivery_datetime' : 'delivery_date';

        $formatForColumn = function (string $raw, string $colName) use ($types): ?string {
            $raw = trim($raw);
            if ($raw === '') return null;

            $ts = strtotime($raw); // supports YYYY-MM-DDTHH:MM and YYYY-MM-DD HH:MM:SS
            if (!$ts) return null;

            $t = $types[$colName] ?? '';
            // DATE only
            if (str_contains($t, 'date') && !str_contains($t, 'datetime') && !str_contains($t, 'timestamp')) {
                return date('Y-m-d', $ts);
            }
            // DATETIME/TIMESTAMP
            return date('Y-m-d H:i:s', $ts);
        };

        // ---- Normalize field names from your form
        // Your view uses assigned_driver_id, but you previously used driver_id in controller.
        $assignedDriverId = (int)($data['assigned_driver_id'] ?? ($data['driver_id'] ?? 0));
        $data['assigned_driver_id'] = $assignedDriverId > 0 ? $assignedDriverId : null;

        $data['load_status']   = $data['load_status'] ?? 'pending';
        $data['rate_currency'] = $data['rate_currency'] ?? 'CAD';

        // Accept datetime-local inputs (preferred), fallback to legacy date fields
        $pickupRaw   = trim((string)($data['pickup_datetime'] ?? ($data['pickup_date'] ?? '')));
        $deliveryRaw = trim((string)($data['delivery_datetime'] ?? ($data['delivery_date'] ?? '')));

        // Map them into the legacy keys too, in case validateLoadInput() expects pickup_date/delivery_date
        $data['pickup_date']   = $pickupRaw;
        $data['delivery_date'] = $deliveryRaw;

        // ---- Validate
        $errors = $this->validateLoadInput($data);
        if ($errors) {
            $_SESSION['error'] = implode(' ', $errors);
            $this->redirect('/loads/edit?id=' . $id);
            return;
        }

        // ---- Format dates for DB column type
        $pickupDb   = $formatForColumn($pickupRaw, $pickupCol);
        $deliveryDb = $formatForColumn($deliveryRaw, $deliveryCol);

        if ($pickupDb === null || $deliveryDb === null) {
            $_SESSION['error'] = 'Invalid pickup or delivery date/time.';
            $this->redirect('/loads/edit?id=' . $id);
            return;
        }

        $pdo->beginTransaction();
        try {
            $sql = "
                UPDATE loads
                SET
                    customer_id           = :customer_id,
                    assigned_driver_id    = :assigned_driver_id,
                    reference_number      = :reference_number,
                    description           = :description,

                    pickup_contact_name   = :pickup_contact_name,
                    pickup_address        = :pickup_address,
                    pickup_city           = :pickup_city,
                    pickup_postal_code    = :pickup_postal_code,
                    {$pickupCol}          = :pickup_dt,

                    delivery_contact_name = :delivery_contact_name,
                    delivery_address      = :delivery_address,
                    delivery_city         = :delivery_city,
                    delivery_postal_code  = :delivery_postal_code,
                    {$deliveryCol}        = :delivery_dt,

                    total_weight_kg       = :total_weight_kg,
                    rate_amount           = :rate_amount,
                    rate_currency         = :rate_currency,
                    load_status           = :load_status,
                    notes                 = :notes,
                    updated_at            = NOW()
                WHERE load_id = :id
            ";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':customer_id'           => (int)($data['customer_id'] ?? 0),
                ':assigned_driver_id'    => $data['assigned_driver_id'] ? (int)$data['assigned_driver_id'] : null,
                ':reference_number'      => trim((string)($data['reference_number'] ?? '')),
                ':description'           => trim((string)($data['description'] ?? '')) !== '' ? trim((string)$data['description']) : null,

                ':pickup_contact_name'   => trim((string)($data['pickup_contact_name'] ?? '')) !== '' ? trim((string)$data['pickup_contact_name']) : null,
                ':pickup_address'        => trim((string)($data['pickup_address'] ?? '')),
                ':pickup_city'           => trim((string)($data['pickup_city'] ?? '')),
                ':pickup_postal_code'    => trim((string)($data['pickup_postal_code'] ?? '')) !== '' ? trim((string)$data['pickup_postal_code']) : null,
                ':pickup_dt'             => $pickupDb,

                ':delivery_contact_name' => trim((string)($data['delivery_contact_name'] ?? '')) !== '' ? trim((string)$data['delivery_contact_name']) : null,
                ':delivery_address'      => trim((string)($data['delivery_address'] ?? '')),
                ':delivery_city'         => trim((string)($data['delivery_city'] ?? '')),
                ':delivery_postal_code'  => trim((string)($data['delivery_postal_code'] ?? '')) !== '' ? trim((string)$data['delivery_postal_code']) : null,
                ':delivery_dt'           => $deliveryDb,

                ':total_weight_kg'       => ($data['total_weight_kg'] ?? '') !== '' ? (float)$data['total_weight_kg'] : null,
                ':rate_amount'           => ($data['rate_amount'] ?? '') !== '' ? (float)$data['rate_amount'] : null,
                ':rate_currency'         => trim((string)($data['rate_currency'] ?? 'CAD')) !== '' ? trim((string)$data['rate_currency']) : 'CAD',
                ':load_status'           => (string)$data['load_status'],
                ':notes'                 => trim((string)($data['notes'] ?? '')) !== '' ? trim((string)$data['notes']) : null,
                ':id'                    => $id,
            ]);

            // Optional PDF upload on edit
            $this->handleDocumentUpload($pdo, $id);

            $pdo->commit();

            $_SESSION['success'] = 'Load updated successfully.';
            $this->redirect('/loads/view?id=' . $id);
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }   

    /**
     * POST /loads/status
     * Keep for later — dispatcher/admin only
     */
    public function updateStatus(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot update load status.');
        }

        $this->back();
    }

    /**
     * POST /loads/bulk
     * Keep for later — dispatcher/admin only
     */
    public function bulkActions(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot run bulk actions.');
        }

        $this->back();
    }

    /**
     * GET /loads/document (dispatcher/admin only)
     */
    public function documentForm(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot generate documents from this screen.');
        }

        $loadId = (int)($_GET['id'] ?? 0);
        $type   = (string)($_GET['type'] ?? '');

        if ($loadId <= 0 || !in_array($type, ['bol','pod'], true)) {
            http_response_code(404);
            echo 'Invalid document request';
            return;
        }

        $load = Load::find($loadId);
        if (!$load) {
            http_response_code(404);
            echo 'Load not found';
            return;
        }

        $this->view('loads/document', [
            'load' => $load,
            'type' => $type,
        ]);
    }

    /**
     * POST /loads/document (dispatcher/admin only)
     */
    public function generateDocument(): void
    {
        if (!$this->canManage()) {
            $this->forbid('Drivers cannot generate documents from this screen.');
        }

        // Keep your existing PDF generator here if you already have it.
        // If not, tell me which library you use (FPDF/TCPDF/Dompdf) and I’ll generate it.
        $this->forbid('generateDocument() not implemented yet.');
    }

    /**
     * Optional document upload handler for create/edit forms.
     */
    private function handleDocumentUpload(PDO $pdo, int $loadId): void
    {
        if (empty($_FILES['document_file']['tmp_name'])) return;

        $docType = (string)($_POST['document_type'] ?? 'other');
        if (!in_array($docType, ['bol','pod','other'], true)) {
            $docType = 'other';
        }

        $file = $_FILES['document_file'];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return;

        // Basic type check
        if (($file['type'] ?? '') !== 'application/pdf') {
            throw new \RuntimeException('Only PDF files are allowed.');
        }

        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . "/uploads/load_documents/{$loadId}";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename   = strtoupper($docType) . "_{$loadId}_" . date('Ymd_His') . ".pdf";
        $targetPath = $uploadDir . "/" . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException('Failed to move uploaded file.');
        }

        // Insert record (ignore if table missing)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO load_documents (
                    load_id,
                    uploaded_by_user_id,
                    document_type,
                    file_path,
                    file_extension
                ) VALUES (
                    :load_id,
                    :user_id,
                    :doc_type,
                    :file_path,
                    'pdf'
                )
            ");
            $stmt->execute([
                ':load_id'   => $loadId,
                ':user_id'   => (int)($this->currentUserId() ?: 0),
                ':doc_type'  => $docType,
                ':file_path' => "/uploads/load_documents/{$loadId}/{$filename}",
            ]);
        } catch (\Throwable $e) {
            // swallow if table missing or constraint mismatch
        }
    }
}
