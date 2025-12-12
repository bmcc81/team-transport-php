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
        $data = $_POST;
        $data['created_by_user_id'] = $_SESSION['user_id'] ?? null;

        // Basic defaults
        $data['load_status'] = $data['load_status'] ?? 'pending';
        $data['notes'] = $data['notes'] ?? '';

        $id = Load::create($data);
        $this->redirect('/loads/view?id=' . $id);
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

        $pdo->beginTransaction();

        try {
            // Get current driver
            $stmt = $pdo->prepare("SELECT driver_id FROM loads WHERE load_id = :id");
            $stmt->execute(['id' => $id]);
            $oldDriverId = $stmt->fetchColumn();

            // Update load (THIS must persist driver_id)
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
