<?php
/** @var array $load */

$pageTitle = "Load Details — " . e($load['load_number'] ?? ('L-' . $load['load_id']));
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-1">
                        Load <?= e($load['load_number'] ?? ('L-' . $load['load_id'])) ?>
                    </h2>
                    <small class="text-muted">
                        <?= e($load['customer_company_name'] ?? '') ?>
                    </small>
                </div>

                <div class="d-flex gap-2">
                    <a href="/admin/loads/<?= e((string)$load['load_id']) ?>/edit"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <form action="/admin/loads/<?= e((string)$load['load_id']) ?>/delete"
                          method="POST"
                          onsubmit="return confirm('Delete this load?');">
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- STATUS CARD -->
            <div class="card shadow-sm mb-3">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-2">Status</h5>
                        <?php require __DIR__ . '/_status_badge.php'; ?>
                    </div>
                    <div class="text-end small text-muted">
                        Created at: <?= e((string)$load['created_at']) ?><br>
                        Updated at: <?= e((string)$load['updated_at']) ?>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Pickup
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong><?= e($load['pickup_contact_name'] ?? '') ?></strong>
                            </div>
                            <div><?= e($load['pickup_address']) ?></div>
                            <div><?= e($load['pickup_city']) ?> <?= e($load['pickup_postal_code'] ?? '') ?></div>
                            <div class="text-muted small mt-1">
                                <?= e(substr((string)$load['pickup_date'], 0, 16)) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Delivery
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <strong><?= e($load['delivery_contact_name'] ?? '') ?></strong>
                            </div>
                            <div><?= e($load['delivery_address']) ?></div>
                            <div><?= e($load['delivery_city']) ?> <?= e($load['delivery_postal_code'] ?? '') ?></div>
                            <div class="text-muted small mt-1">
                                <?= e(substr((string)$load['delivery_date'], 0, 16)) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold">
                    Notes
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <?= nl2br(e($load['notes'] ?? 'No notes.')) ?>
                    </p>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
