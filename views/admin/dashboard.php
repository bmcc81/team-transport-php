<?php
$pageTitle = "Admin Dashboard";
require __DIR__ . '/../layout/header.php';

use App\Database\Database;

$pdo = Database::pdo();

/** =====================
 *  KPI COUNTS
 *  ===================== */
$totalVehicles   = (int)$pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalDrivers    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn();
$totalLoads      = (int)$pdo->query("SELECT COUNT(*) FROM loads")->fetchColumn();
$totalCustomers  = (int)$pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

/** =====================
 *  MAINTENANCE ALERTS
 *  ===================== */
$maintenanceOverdue = (int)$pdo->query("
    SELECT COUNT(*)
    FROM vehicle_maintenance
    WHERE status = 'planned'
      AND scheduled_date < CURDATE()
")->fetchColumn();

$maintenanceWeek = (int)$pdo->query("
    SELECT COUNT(*)
    FROM vehicle_maintenance
    WHERE status = 'planned'
      AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();

/** =====================
 *  CHART DATA
 *  ===================== */

/**
 * Loads chart: loads.load_status (NOT status)
 */
$loadStatusCounts = $pdo->query("
    SELECT load_status, COUNT(*) AS total
    FROM loads
    GROUP BY load_status
")->fetchAll(PDO::FETCH_KEY_PAIR);

/**
 * Vehicles chart: vehicles.status exists
 */
$vehicleStatusCounts = $pdo->query("
    SELECT status, COUNT(*) AS total
    FROM vehicles
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

/** =====================
 *  RECENT LOADS
 *  ===================== */
$recentLoads = $pdo->query("
    SELECT
        load_id,
        load_number,
        load_status AS status,
        pickup_date
    FROM loads
    ORDER BY created_at DESC, load_id DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

function normalizeLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Page Header -->
            <div class="mb-4">
                <h2 class="h4 mb-0">Dashboard</h2>
                <small class="text-muted">Operational overview & alerts</small>
            </div>

            <!-- ALERT STRIP -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card border-danger shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-danger mb-1">Overdue Maintenance</h6>
                                    <div class="display-6 fw-bold text-danger">
                                        <?= $maintenanceOverdue ?>
                                    </div>
                                </div>
                                <i class="bi bi-exclamation-triangle fs-1 text-danger opacity-50"></i>
                            </div>
                            <a href="/admin/vehicles" class="small text-decoration-none">
                                Review vehicles →
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-warning shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-warning mb-1">Maintenance This Week</h6>
                                    <div class="display-6 fw-bold text-warning">
                                        <?= $maintenanceWeek ?>
                                    </div>
                                </div>
                                <i class="bi bi-calendar-week fs-1 text-warning opacity-50"></i>
                            </div>
                            <a href="/admin/vehicles" class="small text-decoration-none">
                                View schedule →
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-primary shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="text-primary mb-2">Quick Actions</h6>
                            <div class="d-grid gap-2">
                                <a href="/loads/create" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> New Load
                                </a>
                                <a href="/admin/vehicles/create" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-truck"></i> Add Vehicle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- CORE KPIs -->
            <div class="row g-3 mb-4">
                <?php
                $stats = [
                    ['Vehicles', $totalVehicles, 'truck', '/admin/vehicles'],
                    ['Drivers', $totalDrivers, 'person-badge', '/admin/drivers'],
                    ['Loads', $totalLoads, 'box-seam', '/admin/loads'],
                    ['Customers', $totalCustomers, 'building', '/admin/customers'],
                ];

                foreach ($stats as [$label, $value, $icon, $link]):
                ?>
                    <div class="col-md-3">
                        <div class="card shadow-sm text-center h-100">
                            <div class="card-body">
                                <i class="bi bi-<?= $icon ?> fs-3 text-muted"></i>
                                <h6 class="text-muted mt-2"><?= e($label) ?></h6>
                                <div class="display-6 fw-bold"><?= (int)$value ?></div>
                                <a href="<?= e($link) ?>" class="small text-decoration-none">Manage →</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- CHARTS -->
            <div class="row g-3 mb-4">

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Loads by Status
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center" style="min-height:220px;">
                            <?php if (!empty($loadStatusCounts)): ?>
                                <canvas id="loadsStatusChart" style="max-height:180px; width:100%;"></canvas>
                            <?php else: ?>
                                <span class="text-muted small">No load data available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Vehicles by Status
                        </div>
                        <div class="card-body d-flex justify-content-center align-items-center" style="min-height:220px;">
                            <?php if (!empty($vehicleStatusCounts)): ?>
                                <canvas id="vehicleStatusChart" style="max-height:180px; width:100%;"></canvas>
                            <?php else: ?>
                                <span class="text-muted small">No vehicle data available</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>

            <!-- RECENT LOADS -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-light fw-semibold">
                    Recent Loads
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentLoads)): ?>
                        <div class="p-3 text-muted">No recent loads.</div>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Load</th>
                                    <th>Status</th>
                                    <th>Pickup Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLoads as $load): ?>
                                    <tr>
                                        <td><?= e($load['load_number'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?= e(normalizeLabel((string)($load['status'] ?? ''))) ?>
                                            </span>
                                        </td>
                                        <td><?= e((string)($load['pickup_date'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(() => {

    function renderDonutChart(canvasId, labels, data, colorMap) {
        const ctx = document.getElementById(canvasId);
        if (!ctx || !data.length) return;

        const colors = labels.map(l => colorMap[l] ?? '#adb5bd');

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels,
                datasets: [{
                    data,
                    backgroundColor: colors,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    }

    // Loads by Status (pending/assigned/in_transit/delivered)
    renderDonutChart(
        'loadsStatusChart',
        <?= json_encode(array_map('normalizeLabel', array_keys($loadStatusCounts))) ?>,
        <?= json_encode(array_values($loadStatusCounts)) ?>,
        {
            'Pending': '#ffc107',
            'Assigned': '#0d6efd',
            'In Transit': '#0dcaf0',
            'Delivered': '#198754',
        }
    );

    // Vehicles by Status (available/in_service/maintenance)
    renderDonutChart(
        'vehicleStatusChart',
        <?= json_encode(array_map('normalizeLabel', array_keys($vehicleStatusCounts))) ?>,
        <?= json_encode(array_values($vehicleStatusCounts)) ?>,
        {
            'Available': '#198754',
            'In Service': '#0d6efd',
            'Maintenance': '#ffc107',
        }
    );

})();
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
