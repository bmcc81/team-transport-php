<?php
$pageTitle = 'Loads';
require __DIR__ . '/../layout/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">
        <i class="bi bi-box-seam me-2"></i>Loads
    </h1>
    <a href="/loads/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Load
    </a>
</div>

<form method="get" class="row gy-2 gx-2 mb-3">
    <div class="col-12 col-sm-6 col-md-3">
        <input type="text" name="search" class="form-control form-control-sm"
               placeholder="Search ref, customer, city..."
               value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
    </div>
    <div class="col-6 col-sm-3 col-md-2">
        <select name="status" class="form-select form-select-sm">
            <option value="">All statuses</option>
            <option value="pending"   <?= (($filters['status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
            <option value="in_transit"<?= (($filters['status'] ?? '') === 'in_transit') ? 'selected' : '' ?>>In transit</option>
            <option value="delivered" <?= (($filters['status'] ?? '') === 'delivered') ? 'selected' : '' ?>>Delivered</option>
        </select>
    </div>
    <div class="col-6 col-sm-3 col-md-2 d-grid">
        <button class="btn btn-outline-secondary btn-sm" type="submit">
            <i class="bi bi-search"></i>
        </button>
    </div>
</form>

<!-- Desktop table -->
<div class="d-none d-md-block">
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                <tr>
                    <th>Ref</th>
                    <th>Customer</th>
                    <th>Pickup</th>
                    <th>Delivery</th>
                    <th>Status</th>
                    <th>Driver</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($loads)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-3">No loads found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($loads as $load): ?>
                        <?php
                        $status = $load['load_status'];
                        $badgeClass = match ($status) {
                            'pending'   => 'bg-warning text-dark',
                            'in_transit'=> 'bg-info text-dark',
                            'delivered' => 'bg-success',
                            default     => 'bg-secondary'
                        };
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($load['reference_number']) ?></td>
                            <td><?= htmlspecialchars($load['customer_company_name']) ?></td>
                            <td>
                                <?= htmlspecialchars($load['pickup_city']) ?><br>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('Y-m-d', strtotime($load['pickup_date']))) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($load['delivery_city']) ?><br>
                                <span class="text-muted small">
                                    <?= htmlspecialchars(date('Y-m-d', strtotime($load['delivery_date']))) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($load['driver_name'] ?? 'Unassigned') ?></td>
                            <td class="text-end">
                                <a href="/loads/view?id=<?= (int)$load['load_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="/loads/edit?id=<?= (int)$load['load_id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Mobile cards -->
<div class="d-block d-md-none">
    <?php if (empty($loads)): ?>
        <div class="alert alert-light text-center text-muted">
            No loads found.
        </div>
    <?php else: ?>
        <div class="row g-2">
            <?php foreach ($loads as $load): ?>
                <?php
                $status = $load['load_status'];
                $badgeClass = match ($status) {
                    'pending'   => 'bg-warning text-dark',
                    'in_transit'=> 'bg-info text-dark',
                    'delivered' => 'bg-success',
                    default     => 'bg-secondary'
                };
                ?>
                <div class="col-12">
                    <div class="card shadow-sm load-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($load['reference_number']) ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?= htmlspecialchars($load['customer_company_name']) ?>
                                    </div>
                                </div>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars(ucwords(str_replace('_', ' ', $status))) ?>
                                </span>
                            </div>

                            <div class="small mb-2">
                                <div>
                                    <i class="bi bi-box-arrow-in-right me-1 text-muted"></i>
                                    <?= htmlspecialchars($load['pickup_city']) ?>
                                    (<?= htmlspecialchars(date('Y-m-d', strtotime($load['pickup_date']))) ?>)
                                </div>
                                <div>
                                    <i class="bi bi-box-arrow-up-right me-1 text-muted"></i>
                                    <?= htmlspecialchars($load['delivery_city']) ?>
                                    (<?= htmlspecialchars(date('Y-m-d', strtotime($load['delivery_date']))) ?>)
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <div class="small text-muted">
                                    <i class="bi bi-truck-front me-1"></i>
                                    <?= htmlspecialchars($load['driver_name'] ?? 'Unassigned') ?>
                                </div>
                                <div>
                                    <a href="/loads/view?id=<?= (int)$load['load_id'] ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="/loads/edit?id=<?= (int)$load['load_id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
