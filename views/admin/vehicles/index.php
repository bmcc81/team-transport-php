<?php
$pageTitle = "Vehicles";
require __DIR__ . '/../../layout/header.php';

use App\Models\VehicleMaintenance;
use App\Database\Database;

// Optional = Fetch driver names (cleaner admin UI)
$pdo = Database::pdo();
$driverMap = [];

$drivers = $pdo->query("SELECT id, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($drivers as $d) {
    $driverMap[$d['id']] = $d['full_name'];
}
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4">Vehicles</h2>
                <a href="/admin/vehicles/create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Vehicle
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Make/Model</th>
                            <th>Plate</th>
                            <th>Status</th>
                            <th>Maintenance</th>
                            <th>Driver</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($vehicles as $v): ?>

                            <?php
                                // Count overdue maintenance items
                                $overdue = VehicleMaintenance::countDueOrOverdueForVehicle($v['id']);
                            ?>

                            <tr>
                                <!-- Vehicle Number (Clickable) -->
                                <td>
                                    <a href="/admin/vehicles/view/<?= $v['id'] ?>" 
                                       class="text-decoration-none fw-semibold">
                                        <?= htmlspecialchars($v['vehicle_number']) ?>
                                    </a>
                                </td>

                                <!-- Make & Model -->
                                <td><?= htmlspecialchars($v['make'].' '.$v['model']) ?></td>

                                <!-- License Plate -->
                                <td><?= htmlspecialchars($v['license_plate']) ?></td>

                                <!-- Status -->
                                <td>
                                    <?php if ($v['status'] === 'available'): ?>
                                        <span class="badge bg-success">Available</span>
                                    <?php elseif ($v['status'] === 'in_service'): ?>
                                        <span class="badge bg-primary">In Service</span>
                                    <?php elseif ($v['status'] === 'maintenance'): ?>
                                        <span class="badge bg-warning">In Maintenance</span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($v['status']) ?>
                                    <?php endif; ?>
                                </td>

                                <!-- Maintenance Status -->
                                <td>
                                    <?php if ($overdue > 0): ?>
                                        <a href="/admin/vehicles/<?= $v['id'] ?>/maintenance" 
                                           class="badge bg-danger text-decoration-none">
                                            <?= $overdue ?> overdue
                                        </a>
                                    <?php else: ?>
                                        <a href="/admin/vehicles/<?= $v['id'] ?>/maintenance" 
                                           class="badge bg-success text-decoration-none">
                                            OK
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <!-- Assigned Driver -->
                                <td>
                                    <?php if ($v['assigned_driver_id']): ?>
                                        <?= htmlspecialchars($driverMap[$v['assigned_driver_id']] ?? "Driver #".$v['assigned_driver_id']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Actions -->
                                <td class="text-end">
                                    <a href="/admin/vehicles/view/<?= $v['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm" title="View">
                                       <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/admin/vehicles/map?focus=<?= $v['id'] ?>" title="View Vehicule on Map" class="btn btn-outline-info btn-sm">
                                        <i class="bi bi-geo-alt"></i>
                                    </a>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
