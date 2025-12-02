<?php 
$pageTitle = "Vehicle Details"; 
require __DIR__ . '/../../layout/header.php';

use App\Models\VehicleMaintenance;
$maintenanceDue = VehicleMaintenance::countDueOrOverdueForVehicle((int)$vehicle['id']);

?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <?php if ($maintenanceDue > 0): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= $maintenanceDue ?></strong> maintenance item(s) due or overdue.
                    </div>
                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance" class="btn btn-sm btn-outline-dark">
                        View Maintenance
                    </a>
                </div>
            <?php endif; ?>

             <div class="row">
                <div class="col-12 col-md-6">
                    <h2 class="h4 mb-3">Vehicle: <?= htmlspecialchars($vehicle['vehicle_number']) ?></h2>
                </div>
                <div class="col-12 col-md-6">
                    <a class="btn btn-outline-secondary btn-sm mt-2 float-end" 
                        href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance">
                        <i class="bi bi-wrench"></i> Maintenance</a>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">

                    <h5 class="mb-3">Basic Information</h5>

                    <p><strong>Make:</strong> <?= htmlspecialchars($vehicle['make']) ?></p>
                    <p><strong>Model:</strong> <?= htmlspecialchars($vehicle['model']) ?></p>
                    <p><strong>Year:</strong> <?= htmlspecialchars($vehicle['year']) ?></p>
                    <p><strong>License Plate:</strong> <?= htmlspecialchars($vehicle['license_plate']) ?></p>
                    <p><strong>VIN:</strong> <?= htmlspecialchars($vehicle['vin'] ?? 'N/A') ?></p>
                    <p><strong>Capacity:</strong> <?= htmlspecialchars($vehicle['capacity'] ?? 'N/A') ?></p>

                    <hr>

                    <h5 class="mb-3">Status</h5>

                    <p><strong>Status:</strong> <?= htmlspecialchars($vehicle['status']) ?></p>
                    <p><strong>Maintenance:</strong> 
                        <span class="badge bg-info">
                            <?= htmlspecialchars($vehicle['maintenance_status']) ?>
                        </span>
                    </p>

                    <?php if ($driver): ?>
                        <p><strong>Assigned Driver:</strong> <?= htmlspecialchars($driver['full_name']) ?></p>
                    <?php else: ?>
                        <p class="text-muted">No driver assigned.</p>
                    <?php endif; ?>

                    <a class="btn btn-primary mt-3" 
                       href="/admin/vehicles/edit/<?= $vehicle['id'] ?>">Edit Vehicle</a>

                    <a  class="btn btn-danger mt-3 ms-2"
                        href="/admin/vehicles/delete/<?= $vehicle['id'] ?>">
                            <i class="bi bi-trash"></i> Delete</a>

                </div>
            </div>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
