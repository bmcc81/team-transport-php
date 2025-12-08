<?php
$pageTitle = "Geofence Alert History";
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
                        <i class="bi bi-bell-fill me-2"></i> Geofence Alert History
                    </h2>
                    <small class="text-muted">
                        Latest enter/exit events for all vehicles.
                    </small>
                </div>
                <a href="/admin/geofences" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-bounding-box-circles"></i> Geofences
                </a>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-body py-2">
                    <form class="row g-2 align-items-end" method="get" action="/admin/geofences/alerts">
                        <div class="col-md-3">
                            <label class="form-label mb-0">Geofence</label>
                            <select name="geofence_id" class="form-select form-select-sm">
                                <option value="">All</option>
                                <?php foreach ($geofences as $g): ?>
                                    <option value="<?= (int)$g['id'] ?>"
                                        <?= isset($geofenceId) && (int)$geofenceId === (int)$g['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($g['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label mb-0">Vehicle ID</label>
                            <input type="number" name="vehicle_id" class="form-control form-control-sm"
                                   value="<?= isset($vehicleId) ? (int)$vehicleId : '' ?>">
                        </div>

                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="bi bi-funnel"></i> Filter
                            </button>
                            <a href="/admin/geofences/alerts" class="btn btn-outline-secondary btn-sm">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Vehicle</th>
                                <th>Geofence</th>
                                <th>Event</th>
                                <th>Location</th>
                                <th>Speed</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($alerts)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No alerts yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($alerts as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars($a['occurred_at']) ?></td>
                                    <td>#<?= (int)$a['vehicle_id'] ?>
                                        <?php if (!empty($a['vehicle_number'])): ?>
                                            (<?= htmlspecialchars($a['vehicle_number']) ?>)
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($a['geofence_name'] ?? '') ?></td>
                                    <td>
                                        <?php if ($a['event'] === 'enter'): ?>
                                            <span class="badge text-bg-success">Enter</span>
                                        <?php elseif ($a['event'] === 'exit'): ?>
                                            <span class="badge text-bg-danger">Exit</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning text-dark">Dwell</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['latitude'] && $a['longitude']): ?>
                                            <?= htmlspecialchars($a['latitude']) ?>,
                                            <?= htmlspecialchars($a['longitude']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($a['speed_kph'] !== null): ?>
                                            <?= htmlspecialchars($a['speed_kph']) ?> km/h
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
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
