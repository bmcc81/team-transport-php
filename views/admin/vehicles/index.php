<?php $pageTitle = "Vehicles"; require __DIR__ . '/../../layout/header.php'; ?>

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
                            <tr>
                                <td><?= htmlspecialchars($v['vehicle_number']) ?></td>
                                <td><?= htmlspecialchars($v['make'].' '.$v['model']) ?></td>
                                <td><?= htmlspecialchars($v['license_plate']) ?></td>
                                <td><?= htmlspecialchars($v['status']) ?></td>
                                <td><?= htmlspecialchars($v['maintenance_status'] ?? 'ok') ?></td>

                                <td>
                                    <?php if ($v['assigned_driver_id']): ?>
                                        Driver ID: <?= $v['assigned_driver_id'] ?>
                                    <?php else: ?>
                                        <span class="text-muted">None</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <a href="/admin/vehicles/view/<?= $v['id'] ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                       <i class="bi bi-eye"></i>
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
