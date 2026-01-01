<?php
/** @var object $vehicle */
/** @var array $item */
/** @var array $errors */

$pageTitle = "Add Maintenance — " . e($vehicle->vehicle_number ?? '');
require __DIR__ . '/../../layout/header.php';

$statuses = [
    'planned'     => 'Planned',
    'in_progress' => 'In Progress',
    'completed'   => 'Completed',
    'cancelled'   => 'Cancelled',
];
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Add Maintenance Item — <?= e($vehicle->vehicle_number ?? '') ?></h2>

                <a href="/admin/vehicles/<?= e((string)$vehicle->id) ?>/maintenance"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold">Please fix the following issues:</div>
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/admin/vehicles/<?= e((string)$vehicle->id) ?>/maintenance/create">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Maintenance Type <span class="text-danger">*</span></label>
                                <input type="text" name="maintenance_type" class="form-control" required
                                       value="<?= e($item['maintenance_type'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                                <input type="date" name="scheduled_date" class="form-control" required
                                       value="<?= e($item['scheduled_date'] ?? '') ?>">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= (($item['status'] ?? 'planned') === $value) ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Completed Date (optional)</label>
                                <input type="date" name="completed_date" class="form-control"
                                       value="<?= e($item['completed_date'] ?? '') ?>">
                                <div class="form-text">Used only when status is “Completed”.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="4"><?= e($item['notes'] ?? '') ?></textarea>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
