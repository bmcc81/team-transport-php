<?php
$pageTitle = "Geofences";
require __DIR__ . "/../../layout/header.php";
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="mb-0"><i class="bi bi-geo-alt"></i> Geofences</h3>

                <a href="/admin/geofences/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> New Geofence
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Applies To</th>
                            <th>Active</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($geofences as $g): ?>
                            <tr>
                                <td><?= htmlspecialchars($g['name']) ?></td>

                                <td>
                                    <?php if ($g['type'] === 'circle'): ?>
                                        <span class="badge bg-info"><i class="bi bi-circle"></i> Circle</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary"><i class="bi bi-vector-pen"></i> Polygon</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if ($g['applies_to_all_vehicles']): ?>
                                        <span class="badge bg-success">All Vehicles</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Assigned</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= $g['active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>' ?>
                                </td>

                                <td><?= date('Y-m-d', strtotime($g['created_at'])) ?></td>

                                <td class="text-end">
                                    <a href="/admin/geofences/edit/<?= $g['id'] ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="/admin/geofences/delete" method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this geofence?');">
                                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
