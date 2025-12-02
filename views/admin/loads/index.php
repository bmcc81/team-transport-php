<?php $pageTitle = "All Loads"; 
require __DIR__ . '/../../layout/header.php'; 
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>


        <main class="col-md-9 col-lg-10">
            <h2 class="h4 mb-3">All Loads</h2>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Reference</th>
                            <th>Customer</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Pickup</th>
                            <th>Delivery</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($loads as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['load_id']) ?></td>
                                <td><?= htmlspecialchars($l['reference_number']) ?></td>
                                <td><?= htmlspecialchars($l['customer_company_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($l['driver_name'] ?? 'Unassigned') ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?= htmlspecialchars($l['load_status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($l['pickup_date']) ?></td>
                                <td><?= htmlspecialchars($l['delivery_date']) ?></td>
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
