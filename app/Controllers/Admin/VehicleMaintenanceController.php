<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Database\Database;
use App\Models\Vehicle;
use App\Models\VehicleMaintenance;
use PDO;

class VehicleMaintenanceController extends Controller
{
    public function index($vehicleId): void
    {
        $vehicle = Vehicle::find((int)$vehicleId);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $items = VehicleMaintenance::forVehicle((int)$vehicleId);

        $this->view('admin/vehicles/maintenance_index', [
            'vehicle' => $vehicle,
            'items'   => $items,
        ]);
    }

    public function create($vehicleId): void
    {
        $vehicle = Vehicle::find((int)$vehicleId);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $this->view('admin/vehicles/maintenance_form', [
            'vehicle' => $vehicle,
        ]);
    }

    public function store($vehicleId): void
    {
        $vehicle = Vehicle::find((int)$vehicleId);
        if (!$vehicle) {
            http_response_code(404);
            echo "Vehicle not found";
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $scheduledDate = $_POST['scheduled_date'] ?? '';

        if ($title === '' || $scheduledDate === '') {
            $_SESSION['error'] = "Title and scheduled date are required.";
            header("Location: /admin/vehicles/$vehicleId/maintenance/create");
            exit;
        }

        VehicleMaintenance::create([
            'vehicle_id'     => (int)$vehicleId,
            'title'          => $title,
            'description'    => $_POST['description'] ?? '',
            'scheduled_date' => $scheduledDate,
            'created_by'     => $_SESSION['user_id'] ?? null,
        ]);

        $_SESSION['success'] = "Maintenance item added.";
        header("Location: /admin/vehicles/$vehicleId/maintenance");
        exit;
    }

    public function complete($vehicleId, $id): void
    {
        $item = VehicleMaintenance::find((int)$id);
        if (!$item || (int)$item['vehicle_id'] !== (int)$vehicleId) {
            http_response_code(404);
            echo "Maintenance item not found";
            return;
        }

        VehicleMaintenance::markCompleted((int)$id);
        $_SESSION['success'] = "Maintenance marked as completed.";

        header("Location: /admin/vehicles/$vehicleId/maintenance");
        exit;
    }

    public function delete($vehicleId, $id): void
    {
        $item = VehicleMaintenance::find((int)$id);
        if (!$item || (int)$item['vehicle_id'] !== (int)$vehicleId) {
            http_response_code(404);
            echo "Maintenance item not found";
            return;
        }

        VehicleMaintenance::delete((int)$id);
        $_SESSION['success'] = "Maintenance item deleted.";

        header("Location: /admin/vehicles/$vehicleId/maintenance");
        exit;
    }
}
