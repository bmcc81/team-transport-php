<?php
/** @var object $vehicle */
/** @var array $items */
/** @var int $overdue */

$pageTitle = "Vehicle Maintenance — " . e($vehicle->vehicle_number ?? '');
require __DIR__ . '/../../layout/header.php';

$today = new DateTimeImmutable('today');
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h4 mb-1">Maintenance — <?= e($vehicle->vehicle_number ?? 'Vehicle') ?></h2>
                    <div class="text-muted small">
                        Plate: <?= e($vehicle->license_plate ?? '—') ?>
                        <?php $mm = trim(($vehicle->make ?? '') . ' ' . ($vehicle->model ?? '')); ?>
                        <?php if ($mm !== ''): ?> • <?= e($mm) ?><?php endif; ?>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a class="btn btn-sm btn-outline-secondary" href="/admin/vehicles/view/<?= e((string)$vehicle->id) ?>">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                    <a class="btn btn-sm btn-primary" href="/admin/vehicles/<?= e((string)$vehicle->id) ?>/maintenance/create">
                        <i class="bi bi-plus-circle"></i> Add Item
                    </a>
                </div>
            </div>

            <?php if ($overdue > 0): ?>
                <div class="alert alert-warning shadow-sm">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong><?= e((string)$overdue) ?></strong> item(s) overdue.
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Status</th>
                            <th>Type</th>
                            <th>Scheduled</th>
                            <th>Completed</th>
                            <th>Notes</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="6" class="text-muted p-3">No maintenance records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $m): ?>
                                <?php
                                $status = $m['status'] ?? 'planned';
                                $scheduled = !empty($m['scheduled_date']) ? new DateTimeImmutable($m['scheduled_date']) : null;
                                $completed = !empty($m['completed_date']) ? new DateTimeImmutable($m['completed_date']) : null;

                                $isCompleted = ($status === 'completed');
                                $isOverdue = (!$isCompleted && $scheduled && $scheduled < $today);

                                if ($isCompleted) { $badge='bg-success'; $label='Completed'; }
                                elseif ($isOverdue) { $badge='bg-danger'; $label='Overdue'; }
                                elseif ($status === 'in_progress') { $badge='bg-primary'; $label='In progress'; }
                                elseif ($status === 'cancelled') { $badge='bg-secondary'; $label='Cancelled'; }
                                else { $badge='bg-info text-dark'; $label='Planned'; }
                                ?>
                                <tr>
                                    <td><span class="badge <?= $badge ?>"><?= e($label) ?></span></td>
                                    <td class="fw-semibold"><?= e($m['maintenance_type'] ?? '—') ?></td>
                                    <td><?= e($scheduled ? $scheduled->format('Y-m-d') : '—') ?></td>
                                    <td><?= e($completed ? $completed->format('Y-m-d') : '—') ?></td>
                                    <td style="max-width: 420px;">
                                        <?php if (!empty($m['notes'])): ?>
                                            <?= nl2br(e($m['notes'])) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <?php if (in_array(($m['status'] ?? ''), ['planned','in_progress'], true)): ?>
                                                <form method="POST"
                                                      action="/admin/vehicles/<?= e((string)$vehicle->id) ?>/maintenance/<?= e((string)$m['id']) ?>/complete"
                                                      class="d-inline">
                                                    <button class="btn btn-outline-success" title="Mark completed">
                                                        <i class="bi bi-check2"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST"
                                                  action="/admin/vehicles/<?= e((string)$vehicle->id) ?>/maintenance/<?= e((string)$m['id']) ?>/delete"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Delete this maintenance record?');">
                                                <button class="btn btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>

                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
