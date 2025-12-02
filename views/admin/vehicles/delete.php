<?php 
$pageTitle = "Delete Vehicle"; 
require __DIR__ . '/../../layout/header.php'; 
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="card shadow-sm" style="max-width: 600px">
                <div class="card-body">

                    <h3 class="h5 text-danger mb-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        Confirm Vehicle Deletion
                    </h3>

                    <p>Are you sure you want to delete this vehicle?</p>

                    <ul>
                        <li><strong>Vehicle #:</strong> <?= htmlspecialchars($vehicle['vehicle_number']) ?></li>
                        <li><strong>Make/Model:</strong> <?= htmlspecialchars($vehicle['make'].' '.$vehicle['model']) ?></li>
                        <li><strong>License Plate:</strong> <?= htmlspecialchars($vehicle['license_plate']) ?></li>
                    </ul>

                    <?php if (!empty($assignedDriver)): ?>
                        <div class="alert alert-warning">
                            <strong>Warning:</strong> This vehicle is currently assigned to driver:
                            <br>
                            <strong><?= htmlspecialchars($assignedDriver) ?></strong>
                            <br><br>
                            You must unassign it before deleting.
                        </div>
                        <a href="/admin/vehicles/view/<?= $vehicle['id'] ?>" class="btn btn-secondary">
                            Back
                        </a>
                    <?php else: ?>
                        <form method="POST" action="/admin/vehicles/delete/<?= $vehicle['id'] ?>">
                            <button class="btn btn-danger">Yes, Delete Vehicle</button>
                            <a href="/admin/vehicles/view/<?= $vehicle['id'] ?>" class="btn btn-secondary">
                                Cancel
                            </a>
                        </form>
                    <?php endif; ?>

                </div>
            </div>

        </main>

    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
