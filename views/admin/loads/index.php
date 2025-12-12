<?php
/** @var array $loads */

$pageTitle = "Loads";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-0">Loads</h2>
                    <small class="text-muted">Monitor and manage all freight loads</small>
                </div>

                <a href="/admin/loads/create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i> New Load
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Load #</th>
                                    <th>Customer</th>
                                    <th>Driver</th>
                                    <th>Vehicle</th>
                                    <th>Pickup</th>
                                    <th>Delivery</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($loads)) { ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        No loads found.
                                    </td>
                                </tr>
                            <?php } else { ?>
                                <?php foreach ($loads as $l) { ?>
                                    <tr>
                                        <td class="fw-semibold">
                                            <a href="/admin/loads/<?= e((string)$l['load_id']) ?>"
                                               class="text-decoration-none">
                                                <?= e($l['load_number'] ?? ('L-' . $l['load_id'])) ?>
                                            </a>
                                        </td>
                                        <td><?= e($l['customer_company_name'] ?? '—') ?></td>
                                        <td><?= e($l['driver_name'] ?? 'Unassigned') ?></td>
                                        <td><?= e($l['vehicle_number'] ?? '—') ?></td>
                                        <td>
                                            <?= e($l['pickup_city'] ?? '') ?><br>
                                            <small class="text-muted">
                                                <?= e(substr((string)$l['pickup_date'], 0, 16)) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?= e($l['delivery_city'] ?? '') ?><br>
                                            <small class="text-muted">
                                                <?= e(substr((string)$l['delivery_date'], 0, 16)) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php require __DIR__ . '/_status_badge.php'; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/admin/loads/<?= e((string)$l['load_id']) ?>"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/admin/loads/<?= e((string)$l['load_id']) ?>/edit"
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="/admin/loads/<?= e((string)$l['load_id']) ?>/delete"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this load?');">
                                                <button class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
