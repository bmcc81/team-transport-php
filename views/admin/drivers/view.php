<?php 
$pageTitle = "Driver Profile"; 
require __DIR__ . '/../../layout/header.php'; 
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">Driver: <?= htmlspecialchars($driver['full_name']) ?></h2>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Driver Info</h5>
                    <p><strong>Username:</strong> <?= htmlspecialchars($driver['username']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($driver['email']) ?></p>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Assigned Vehicle</h5>

                    <?php if ($vehicle): ?>
                        <p><strong>Vehicle #:</strong> <?= htmlspecialchars($vehicle['vehicle_number']) ?></p>
                        <p><strong>Model:</strong> <?= htmlspecialchars($vehicle['make'].' '.$vehicle['model']) ?></p>
                        <p><strong>Plate:</strong> <?= htmlspecialchars($vehicle['license_plate']) ?></p>
                    <?php else: ?>
                        <p class="text-muted">No vehicle assigned.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>Assigned Loads</h5>

                    <?php if (empty($loads)): ?>
                        <p class="text-muted">No loads assigned.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Reference</th>
                                    <th>Customer</th>
                                    <th>Status</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($loads as $l): ?>
                                    <tr>
                                        <td><?= $l['load_id'] ?></td>
                                        <td><?= htmlspecialchars($l['reference_number']) ?></td>
                                        <td><?= htmlspecialchars($l['customer_company_name']) ?></td>
                                        <td><?= htmlspecialchars($l['load_status']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
