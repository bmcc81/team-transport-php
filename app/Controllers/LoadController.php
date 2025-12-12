<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Load;
use App\Models\Customer;
use App\Models\User;

class LoadController extends Controller
{
    public function index(): void
    {
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];

        $loads = Load::all($filters);

        $this->view('loads/index', [
            'loads'   => $loads,
            'filters' => $filters,
        ]);
    }

    public function show(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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

        // Simple documents fetch if table exists
        $pdo = Database::pdo();
        $docs = [];
        try {
            $stmt = $pdo->prepare("SELECT * FROM load_documents WHERE load_id = :id ORDER BY uploaded_at DESC");
            $stmt->execute([':id' => $id]);
            $docs = $stmt->fetchAll() ?: [];
        } catch (\Throwable $e) {
            $docs = [];
        }

        $this->view('loads/show', [
            'load' => $load,
            'docs' => $docs,
        ]);
    }

    public function create(): void
    {
        $customers = Customer::all();
        $drivers   = User::drivers();

        $this->view('loads/create', [
            'customers' => $customers,
            'drivers'   => $drivers,
        ]);
    }

    public function store(): void
    {
        $pdo = Database::pdo();

        $data = $_POST;
        $data['created_by_user_id'] = $_SESSION['user_id'] ?? null;
        $data['load_status'] = $data['load_status'] ?? 'pending';
        $data['notes'] = $data['notes'] ?? '';

        $pdo->beginTransaction();

        try {
            // 1️⃣ Create load
            $loadId = Load::create($data);

            // 2️⃣ Handle document upload (optional)
            if (!empty($_FILES['document_file']['tmp_name'])) {

                $docType = $_POST['document_type'] ?? 'other';
                $file    = $_FILES['document_file'];

                if ($file['error'] === UPLOAD_ERR_OK) {

                    // Validate file type
                    if ($file['type'] !== 'application/pdf') {
                        throw new \RuntimeException('Only PDF files are allowed.');
                    }

                    $uploadDir = __DIR__ . "/../../public/uploads/load_documents/{$loadId}";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    $filename = strtoupper($docType) . "_{$loadId}.pdf";
                    $targetPath = "{$uploadDir}/{$filename}";

                    move_uploaded_file($file['tmp_name'], $targetPath);

                    // Insert document record
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
                        'load_id'   => $loadId,
                        'user_id'   => $_SESSION['user_id'],
                        'doc_type'  => $docType,
                        'file_path' => "/uploads/load_documents/{$loadId}/{$filename}",
                    ]);
                }
            }

            $pdo->commit();

            $_SESSION['success'] = 'Load created successfully.';
            $this->redirect('/loads/view?id=' . $loadId);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }


    public function edit(): void
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
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

        $load['has_vehicle'] = Load::hasVehicle($load['load_id']);
        $load['has_pod'] = Load::hasPOD($load['load_id']);

        $this->view('loads/edit', [
            'load'      => $load,
            'customers' => $customers,
            'drivers'   => $drivers,
        ]);
    }

  public function update(): void
    {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            http_response_code(404);
            echo "Load not found";
            return;
        }

        $pdo = Database::pdo();

        // Explicit mapping — no ambiguity
        $data = $_POST;
        $data['driver_id'] = !empty($_POST['driver_id'])
            ? (int) $_POST['driver_id']
            : null;

        // 🚨 REQUIRE VEHICLE BEFORE IN-TRANSIT
        if (
            isset($data['load_status']) &&
            $data['load_status'] === 'in_transit' &&
            !Load::hasVehicle($id)
        ) {
            $_SESSION['error'] = 'A vehicle must be assigned before dispatching this load.';
            $this->redirect('/loads/edit?id=' . $id);
            return;
        }

        // 🚨 ENFORCE POD BEFORE DELIVERY (GUARD)
        if (
            isset($data['load_status']) &&
            $data['load_status'] === 'delivered' &&
            !Load::hasPOD($id)
        ) {
            $_SESSION['error'] = 'Proof of Delivery (POD) is required before marking this load as delivered.';
            $this->redirect('/loads/edit?id=' . $id);
            return;
        }

        $pdo->beginTransaction();

        try {
            // Get current driver
            $stmt = $pdo->prepare("SELECT driver_id FROM loads WHERE load_id = :id");
            $stmt->execute(['id' => $id]);
            $oldDriverId = $stmt->fetchColumn();

            // Update load
            Load::update($id, $data);

            // Driver state transitions
            if ($oldDriverId && $oldDriverId != $data['driver_id']) {
                $pdo->prepare("
                    UPDATE users
                    SET status = 'available'
                    WHERE id = :id
                ")->execute(['id' => $oldDriverId]);
            }

            if ($data['driver_id']) {
                $pdo->prepare("
                    UPDATE users
                    SET status = 'assigned'
                    WHERE id = :id
                ")->execute(['id' => $data['driver_id']]);
            }

            $pdo->commit();

            $_SESSION['success'] = 'Load updated successfully.';
            $this->redirect('/loads/view?id=' . $id);

        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateStatus(): void
    {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $status = $_POST['status'] ?? '';

        if ($id > 0 && $status !== '') {
            Load::updateStatus($id, $status);
        }

        $this->back();
    }

    public function bulkActions(): void
    {
        // Stub: you can wire your bulk action logic here
        // e.g. close multiple loads, assign driver, etc.
        $this->back();
    }
}
