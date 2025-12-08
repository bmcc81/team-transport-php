<?php
$pageTitle = "Geofence Alerts";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-0">
                        <i class="bi bi-bounding-box-circles me-2"></i> Geofences
                    </h2>
                    <small class="text-muted">
                        Manage zones that trigger alerts when vehicles enter or exit.
                    </small>
                </div>
                <div class="d-flex gap-2">
                    <a href="/admin/geofences/alerts" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-clock-history me-1"></i> Alert History
                    </a>
                    <a href="/admin/geofences/create" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> New Geofence
                    </a>
                </div>
            </div>

            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="alert alert-success py-2">
                    <?= htmlspecialchars($_SESSION['flash_success']) ?>
                    <?php unset($_SESSION['flash_success']); ?>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Center</th>
                                <th>Radius</th>
                                <th>Active</th>
                                <th>Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($geofences)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No geofences defined yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($geofences as $g): ?>
                                <tr>
                                    <td><?= (int)$g['id'] ?></td>
                                    <td><?= htmlspecialchars($g['name']) ?></td>
                                    <td><?= htmlspecialchars($g['type']) ?></td>
                                    <td>
                                        <?php if ($g['center_lat'] && $g['center_lng']): ?>
                                            <?= htmlspecialchars($g['center_lat']) ?>,
                                            <?= htmlspecialchars($g['center_lng']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($g['radius_m']): ?>
                                            <?= (int)$g['radius_m'] ?> m
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($g['active']): ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Disabled</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($g['created_at']) ?></td>
                                    <td class="text-end">
                                        <a href="/admin/geofences/edit?id=<?= (int)$g['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="/admin/geofences/delete/<?= $g['id'] ?>" method="post">
                                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
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
