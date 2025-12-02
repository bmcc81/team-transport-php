<?php
$pageTitle = "Maintenance — " . htmlspecialchars($vehicle['vehicle_number']);
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/vehicles">Vehicles</a></li>
                    <li class="breadcrumb-item">
                        <a href="/admin/vehicles/<?= $vehicle['id'] ?>">
                            <?= htmlspecialchars($vehicle['vehicle_number']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Maintenance</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4">Maintenance for <?= htmlspecialchars($vehicle['vehicle_number']) ?></h2>

                <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/create"
                   class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-lg"></i> Add Maintenance
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 28%">Title</th>
                            <th style="width: 18%">Scheduled</th>
                            <th style="width: 15%">Status</th>
                            <th style="width: 18%">Completed</th>
                            <th style="width: 10%"></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $item): ?>
                                <?php
                                $scheduled = $item['scheduled_date'];
                                $completed = $item['completed_date'] ?: '—';

                                $isOverdue = $item['status'] === 'planned'
                                    && $scheduled < date('Y-m-d');
                                ?>

                                <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                                    <td><?= htmlspecialchars($item['title']) ?></td>

                                    <td><?= htmlspecialchars($scheduled) ?></td>

                                    <td>
                                        <?php if ($item['status'] === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif ($item['status'] === 'cancelled'): ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php else: ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Planned</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($completed) ?></td>

                                    <td class="text-end">

                                        <?php if ($item['status'] === 'planned'): ?>
                                            <form method="POST"
                                                  action="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/<?= $item['id'] ?>/complete"
                                                  class="d-inline">
                                                <button class="btn btn-sm btn-outline-success"
                                                        title="Mark Completed">
                                                    <i class="bi bi-check2"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST"
                                              action="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/<?= $item['id'] ?>/delete"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete this maintenance record?');">
                                            <button class="btn btn-sm btn-outline-danger"
                                                    title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No maintenance items yet.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>
            </div>

        </main>

    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>