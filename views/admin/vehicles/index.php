<?php $pageTitle = "Vehicles"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Vehicles</h2>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Number</th>
                                <th>Make / Model</th>
                                <th>Plate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($vehicles as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars($v['vehicle_number'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($v['make'] ?? '') . ' ' . ($v['model'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($v['license_plate'] ?? '') ?></td>
                                <td><?= htmlspecialchars($v['status'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
