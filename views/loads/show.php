<?php
/** @var array $load */
/** @var array $docs */
/** @var bool $canManage */

require_once __DIR__ . '/../../app/Helpers/sanitize.php';
$pageTitle = 'Load ' . h((string)($load['reference_number'] ?? ''));
require __DIR__ . '/../layout/header.php';

/**
 * Safe display helper for nullable values.
 * Uses global h() from app/Helpers/sanitize.php.
 */
if (!function_exists('show_val')) {
    function show_val($v, string $placeholder = '—'): string
    {
        if ($v === null) return $placeholder;
        if (is_string($v) && trim($v) === '') return $placeholder;
        if (is_array($v) || is_object($v)) return $placeholder;
        return h((string)$v);
    }
}

$status = (string)($load['load_status'] ?? 'pending');

$badgeClass = match ($status) {
    'pending'    => 'bg-warning text-dark',
    'in_transit' => 'bg-info text-dark',
    'delivered'  => 'bg-success',
    default      => 'bg-secondary'
};

// Support both schema styles
$pickupDt   = $load['pickup_datetime'] ?? ($load['pickup_date'] ?? null);
$deliveryDt = $load['delivery_datetime'] ?? ($load['delivery_date'] ?? null);

$currency = (string)($load['rate_currency'] ?? 'CAD');
?>

<div class="row g-3">
    <!-- MAIN -->
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h1 class="h5 mb-1">
                            <i class="bi bi-box-seam me-2"></i>
                            Load <?= h((string)($load['reference_number'] ?? '')) ?>
                        </h1>
                        <div class="small text-muted">
                            Customer: <?= h((string)($load['customer_company_name'] ?? '')) ?>
                        </div>
                    </div>
                    <span class="badge <?= $badgeClass ?>">
                        <?= h(ucwords(str_replace('_', ' ', $status))) ?>
                    </span>
                </div>

                <?php if (!empty($load['description'])): ?>
                    <p class="mt-2 mb-3">
                        <?= nl2br(h((string)$load['description'])) ?>
                    </p>
                <?php endif; ?>

                <!-- PICKUP / DELIVERY -->
                <div class="row small">
                    <div class="col-12 col-md-6 mb-3">
                        <h2 class="h6"><i class="bi bi-box-arrow-in-right me-2"></i>Pickup</h2>
                        <div><?= show_val($load['pickup_contact_name'] ?? null) ?></div>
                        <div><?= show_val($load['pickup_address'] ?? null) ?></div>
                        <div>
                            <?= show_val($load['pickup_city'] ?? null) ?>
                            <?php
                            $pp = $load['pickup_postal_code'] ?? null;
                            if ($pp !== null && trim((string)$pp) !== '') echo ' ' . h((string)$pp);
                            ?>
                        </div>
                        <div class="text-muted mt-1"><?= show_val($pickupDt) ?></div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <h2 class="h6"><i class="bi bi-box-arrow-up-right me-2"></i>Delivery</h2>
                        <div><?= show_val($load['delivery_contact_name'] ?? null) ?></div>
                        <div><?= show_val($load['delivery_address'] ?? null) ?></div>
                        <div>
                            <?= show_val($load['delivery_city'] ?? null) ?>
                            <?php
                            $dp = $load['delivery_postal_code'] ?? null;
                            if ($dp !== null && trim((string)$dp) !== '') echo ' ' . h((string)$dp);
                            ?>
                        </div>
                        <div class="text-muted mt-1"><?= show_val($deliveryDt) ?></div>
                    </div>
                </div>

                <!-- META -->
                <div class="row small">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted">Weight (kg)</div>
                        <div class="fw-semibold">
                            <?php
                            $w = $load['total_weight_kg'] ?? null;
                            echo ($w === null || $w === '')
                                ? '—'
                                : h(number_format((float)$w, 2));
                            ?>
                        </div>
                    </div>

                    <div class="col-6 col-md-3 mb-2">
                        <div class="text-muted">Rate</div>
                        <div class="fw-semibold">
                            <?php
                            $r = $load['rate_amount'] ?? null;
                            echo ($r === null || $r === '')
                                ? '—'
                                : h(number_format((float)$r, 2)) . ' ' . h($currency);
                            ?>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-2">
                        <div class="text-muted">Driver</div>
                        <div class="fw-semibold">
                            <?= show_val($load['driver_name'] ?? null, 'Unassigned') ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($load['notes'])): ?>
                    <div class="mt-3">
                        <div class="text-muted small mb-1">Notes</div>
                        <div class="border rounded p-2 bg-light small">
                            <?= nl2br(h((string)$load['notes'])) ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ACTIONS -->
        <?php if (!empty($canManage)): ?>
            <a href="/loads/edit?id=<?= (int)($load['load_id'] ?? 0) ?>" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-pencil me-1"></i>Edit Load
            </a>
        <?php endif; ?>

        <a href="/loads" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Loads
        </a>
    </div>

    <!-- SIDEBAR -->
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h2 class="h6 mb-3">
                    <i class="bi bi-file-earmark-text me-2"></i>Documents
                </h2>

                <div class="d-grid gap-2 mb-3">
                    <a href="/loads/document?id=<?= (int)($load['load_id'] ?? 0) ?>&type=bol"
                       class="btn btn-outline-primary btn-sm">
                        Generate BOL
                    </a>

                    <a href="/loads/document?id=<?= (int)($load['load_id'] ?? 0) ?>&type=pod"
                       class="btn btn-outline-success btn-sm <?= $status !== 'in_transit' ? 'disabled' : '' ?>">
                        Generate POD
                    </a>

                    <?php if ($status !== 'in_transit'): ?>
                        <div class="small text-muted">
                            POD can only be generated once the load is in transit.
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (empty($docs)): ?>
                    <p class="small text-muted mb-0">No documents available.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($docs as $doc): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="text-truncate me-2">
                                    <?= h(basename((string)($doc['file_path'] ?? ''))) ?>
                                </span>
                                <a href="<?= h((string)($doc['file_path'] ?? '#')) ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
