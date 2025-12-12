<?php

use App\Models\VehicleMaintenance;
use App\Database\Database;

/** @var \App\Models\Vehicle[] $vehicles */

$pageTitle = "Vehicles";

$pdo = Database::pdo();

// Fetch drivers for filter and display
$drivers = $pdo->query("SELECT id, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
$driverMap = [];
foreach ($drivers as $d) {
    $driverMap[$d['id']] = $d['full_name'];
}

// Filters / sorting / pagination
$search       = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$driverFilter = $_GET['driver'] ?? 'all';
$sort         = $_GET['sort'] ?? 'vehicle_number';
$order        = strtolower($_GET['order'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 20;

// Columns allowed to sort
$sortableCols = [
    'vehicle_number',
    'make',
    'license_plate',
    'status',
    'assigned_driver_id',
];

if (!in_array($sort, $sortableCols, true)) {
    $sort = 'vehicle_number';
}

// Step 1: Filter (on objects)
$filtered = $vehicles;

// Search
if ($search !== '') {
    $searchLower = strtolower($search);
    $filtered = array_filter($filtered, function ($v) use ($searchLower) {
        $haystack = strtolower(
            ($v->vehicle_number ?? '') . ' ' .
            ($v->make ?? '') . ' ' .
            ($v->model ?? '') . ' ' .
            ($v->license_plate ?? '')
        );
        return strpos($haystack, $searchLower) !== false;
    });
}

// Status filter
if ($statusFilter !== 'all') {
    $filtered = array_filter($filtered, function ($v) use ($statusFilter) {
        return ($v->status ?? 'unknown') === $statusFilter;
    });
}

// Driver filter
if ($driverFilter !== 'all') {
    $filtered = array_filter($filtered, function ($v) use ($driverFilter) {
        return (string)($v->assigned_driver_id ?? '') === $driverFilter;
    });
}

// Step 2: Sort
usort($filtered, function ($a, $b) use ($sort, $order) {
    $valA = strtolower((string)($a->{$sort} ?? ''));
    $valB = strtolower((string)($b->{$sort} ?? ''));

    if ($valA === $valB) {
        return 0;
    }

    if ($order === 'asc') {
        return ($valA < $valB) ? -1 : 1;
    }
    return ($valA > $valB) ? -1 : 1;
});

// Step 3: Pagination
$total      = count($filtered);
$totalPages = max(1, (int)ceil($total / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$pagedVehicles = array_slice($filtered, $offset, $perPage);

// Helper: keep filters in links
function vehicles_build_query(array $override = []): string
{
    $base = [
        'q'      => $_GET['q'] ?? '',
        'status' => $_GET['status'] ?? 'all',
        'driver' => $_GET['driver'] ?? 'all',
        'sort'   => $_GET['sort'] ?? 'vehicle_number',
        'order'  => $_GET['order'] ?? 'asc',
        'page'   => $_GET['page'] ?? 1,
    ];

    $params = array_merge($base, $override);
    return http_build_query($params);
}

// Helper: render sort link with arrow icon
function vehicles_sort_link(string $label, string $col, string $curSort, string $curOrder): string
{
    $newOrder = ($curSort === $col && $curOrder === 'asc') ? 'desc' : 'asc';
    $query    = vehicles_build_query([
        'sort'  => $col,
        'order' => $newOrder,
        'page'  => 1,
    ]);

    $icon = '';
    if ($curSort === $col) {
        $icon = $curOrder === 'asc'
            ? '<i class="bi bi-chevron-up ms-1"></i>'
            : '<i class="bi bi-chevron-down ms-1"></i>';
    } else {
        $icon = '<i class="bi bi-chevron-expand ms-1 text-muted"></i>';
    }

    return '<a href="?' . $query . '" class="text-decoration-none d-inline-flex align-items-center">'
        . e($label) . $icon . '</a>';
}

require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h4 mb-0">Vehicles</h2>
                    <div class="small text-muted">
                        <?= e((string)$total) ?> vehicle<?= $total === 1 ? '' : 's' ?> found
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="/admin/vehicles/map" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-map"></i> Live Map
                    </a>
                    <a href="/admin/vehicles/create" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add Vehicle
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" class="card mb-3 shadow-sm">
                <div class="card-body py-2">
                    <div class="row g-2 align-items-end">

                        <div class="col-sm-6 col-md-4 col-lg-4">
                            <label class="form-label small text-muted mb-1">Search</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text"><i class="bi bi-search"></i></span>
                                <input
                                    type="search"
                                    name="q"
                                    class="form-control"
                                    placeholder="Vehicle #, make, model, plate..."
                                    value="<?= e($search) ?>"
                                >
                            </div>
                        </div>

                        <div class="col-sm-4 col-md-3 col-lg-2">
                            <label class="form-label small text-muted mb-1">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="all"        <?= $statusFilter === 'all' ? 'selected' : '' ?>>All</option>
                                <option value="available"  <?= $statusFilter === 'available' ? 'selected' : '' ?>>Available</option>
                                <option value="in_service" <?= $statusFilter === 'in_service' ? 'selected' : '' ?>>In Service</option>
                                <option value="maintenance"<?= $statusFilter === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            </select>
                        </div>

                        <div class="col-sm-4 col-md-3 col-lg-3">
                            <label class="form-label small text-muted mb-1">Driver</label>
                            <select name="driver" class="form-select form-select-sm">
                                <option value="all" <?= $driverFilter === 'all' ? 'selected' : '' ?>>All</option>
                                <?php foreach ($drivers as $d): ?>
                                    <option
                                        value="<?= e((string)$d['id']) ?>"
                                        <?= ($driverFilter == $d['id']) ? 'selected' : '' ?>
                                    >
                                        <?= e($d['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-sm-4 col-md-2 col-lg-2">
                            <button class="btn btn-outline-secondary btn-sm w-100">
                                <i class="bi bi-funnel"></i> Apply
                            </button>
                        </div>

                        <div class="col-sm-4 col-md-2 col-lg-1">
                            <a href="/admin/vehicles" class="btn btn-link btn-sm w-100 text-nowrap">
                                Reset
                            </a>
                        </div>

                    </div>
                </div>
            </form>

            <!-- Table -->
            <div class="card shadow-sm">
                <div class="table-responsive" style="max-height: 75vh; overflow-y: auto;">
                    <table class="table table-hover table-sm mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th><?= vehicles_sort_link('Vehicle #', 'vehicle_number', $sort, $order) ?></th>
                                <th><?= vehicles_sort_link('Make / Model', 'make', $sort, $order) ?></th>
                                <th><?= vehicles_sort_link('Plate', 'license_plate', $sort, $order) ?></th>
                                <th><?= vehicles_sort_link('Status', 'status', $sort, $order) ?></th>
                                <th>Maintenance</th>
                                <th><?= vehicles_sort_link('Driver', 'assigned_driver_id', $sort, $order) ?></th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($pagedVehicles)): ?>
                            <tr>
                                <td colspan="7" class="text-center p-4 text-muted">
                                    No vehicles match your filters.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pagedVehicles as $v): ?>
                                <?php
                                $overdue = VehicleMaintenance::countDueOrOverdueForVehicle($v->id);

                                $status      = $v->status ?? '';
                                $statusBadge = 'bg-secondary';
                                $statusLabel = ucfirst($status ?: 'Unknown');
                                $statusIcon  = 'bi-question-circle';

                                if ($status === 'available') {
                                    $statusBadge = 'bg-success';
                                    $statusIcon  = 'bi-check-circle';
                                    $statusLabel = 'Available';
                                } elseif ($status === 'in_service') {
                                    $statusBadge = 'bg-primary';
                                    $statusIcon  = 'bi-truck';
                                    $statusLabel = 'In Service';
                                } elseif ($status === 'maintenance') {
                                    $statusBadge = 'bg-warning text-dark';
                                    $statusIcon  = 'bi-wrench-adjustable-circle';
                                    $statusLabel = 'Maintenance';
                                }

                                $driverId = $v->assigned_driver_id ?? null;
                                ?>
                                <tr>
                                    <td>
                                        <a href="/admin/vehicles/<?= e((string)$v->id) ?>"
                                           class="fw-semibold text-decoration-none">
                                            <?= e($v->vehicle_number ?? '') ?>
                                        </a>
                                        <div class="small text-muted">
                                            <?= e($v->license_plate ?? '') ?>
                                        </div>
                                    </td>
                                    <td><?= e(trim(($v->make ?? '') . ' ' . ($v->model ?? ''))) ?></td>
                                    <td><?= e($v->license_plate ?? '—') ?></td>
                                    <td>
                                        <span class="badge <?= $statusBadge ?>">
                                            <i class="bi <?= $statusIcon ?> me-1"></i>
                                            <?= e($statusLabel) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($overdue > 0): ?>
                                            <a href="/admin/vehicles/<?= e((string)$v->id) ?>/maintenance"
                                               class="badge bg-danger text-decoration-none">
                                                <i class="bi bi-exclamation-octagon me-1"></i>
                                                <?= e((string)$overdue) ?> overdue
                                            </a>
                                        <?php else: ?>
                                            <a href="/admin/vehicles/<?= e((string)$v->id) ?>/maintenance"
                                               class="badge bg-success text-decoration-none">
                                                <i class="bi bi-check-circle me-1"></i>
                                                OK
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($driverId): ?>
                                            <span class="d-inline-flex align-items-center">
                                                <i class="bi bi-person-badge me-1"></i>
                                                <?= e($driverMap[$driverId] ?? "Driver #{$driverId}") ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">None</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/admin/vehicles/<?= e((string)$v->id) ?>"
                                               class="btn btn-outline-primary" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="/admin/vehicles/map?focus=<?= e((string)$v->id) ?>"
                                               class="btn btn-outline-info" title="View on Map">
                                                <i class="bi bi-geo-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= vehicles_build_query(['page' => max(1, $page - 1)]) ?>">
                                Prev
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?<?= vehicles_build_query(['page' => $i]) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?<?= vehicles_build_query(['page' => min($totalPages, $page + 1)]) ?>">
                                Next
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
