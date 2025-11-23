<?php
session_start();
require_once __DIR__ . '/../../services/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId   = (int) $_SESSION['user_id'];
$userRole = $_SESSION['role'] ?? 'driver';

// Load saved views for this user
$savedViews = [];
$svStmt = $conn->prepare("SELECT view_id, view_name, config_json FROM user_saved_views WHERE user_id = ? ORDER BY created_at DESC");
$svStmt->bind_param("i", $userId);
$svStmt->execute();
$svRes = $svStmt->get_result();
while ($row = $svRes->fetch_assoc()) {
    $savedViews[] = $row;
}
$svStmt->close();

// Get dropdown options
$customersRes = $conn->query("SELECT id, customer_company_name FROM customers ORDER BY customer_company_name ASC");
$driversRes   = $conn->query("SELECT id, username FROM users WHERE role='driver' ORDER BY username ASC");

// Core query (filters, sorting, pagination, loads)
require_once __DIR__ . '/loads_query.php';
?>
<!DOCTYPE html>
<html>
<head>
    <link href="../../styles/css/bootstrap.min.css" rel="stylesheet">
    <title>Loads</title>
    <style>
        .btn-purple { background:#6f42c1; color:#fff; }
        .btn-purple:hover { background:#5a379c; color:#fff; }
    </style>
</head>
<body class="p-4 bg-light">
<a href="../../dashboard.php" class="btn btn-primary mb-3">&larr; Back to Dashboard</a>
<h3 class="mb-3">Loads</h3>

<!-- Quick status buttons -->
<div class="mb-3">
    <div class="btn-group btn-group-sm">
        <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'pending', 'page' => 1])) ?>" class="btn btn-warning">Pending</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'assigned', 'page' => 1])) ?>" class="btn btn-purple">Assigned</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'in_transit', 'page' => 1])) ?>" class="btn btn-primary">In Transit</a>
        <a href="?<?= http_build_query(array_merge($_GET, ['status' => 'delivered', 'page' => 1])) ?>" class="btn btn-success">Delivered</a>
        <a href="loads_list.php" class="btn btn-secondary">All</a>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="row g-2 mb-4 bg-white p-3 border rounded">

    <div class="col-md-3">
        <input type="text" name="search" class="form-control"
               placeholder="Search (ref, customer, driver, city)..."
               value="<?= htmlspecialchars($search) ?>">
    </div>

    <div class="col-md-2">
        <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="pending"     <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="assigned"    <?= $status === 'assigned' ? 'selected' : '' ?>>Assigned</option>
            <option value="in_transit"  <?= $status === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
            <option value="delivered"   <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
        </select>
    </div>

    <div class="col-md-2">
        <select name="customer" class="form-select">
            <option value="">All Customers</option>
            <?php while ($c = $customersRes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>" <?= (string)$customer === (string)$c['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['customer_company_name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-2">
        <select name="driver" class="form-select">
            <option value="">All Drivers</option>
            <?php while ($d = $driversRes->fetch_assoc()): ?>
                <option value="<?= $d['id'] ?>" <?= (string)$driver === (string)$d['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['username']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="col-md-3 text-end">
        <button class="btn btn-primary">Apply Filters</button>
        <?php if ($_GET): ?>
            <a href="loads_list.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </div>

    <div class="col-12"><hr></div>

    <div class="col-md-3">
        <label class="form-label">Pickup From</label>
        <input type="datetime-local" name="pickup_from" class="form-control"
               value="<?= htmlspecialchars($pickup_from) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Pickup To</label>
        <input type="datetime-local" name="pickup_to" class="form-control"
               value="<?= htmlspecialchars($pickup_to) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Delivery From</label>
        <input type="datetime-local" name="delivery_from" class="form-control"
               value="<?= htmlspecialchars($delivery_from) ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Delivery To</label>
        <input type="datetime-local" name="delivery_to" class="form-control"
               value="<?= htmlspecialchars($delivery_to) ?>">
    </div>

    <div class="col-12 mt-3 d-flex justify-content-between align-items-center">
        <!-- Saved Views -->
        <div class="input-group input-group-sm" style="max-width:350px;">
            <label class="input-group-text">Saved Views</label>
            <select id="savedViewSelect" class="form-select">
                <option value="">Select view...</option>
                <?php foreach ($savedViews as $v): 
                    $config = json_decode($v['config_json'], true) ?? [];
                    $qs = http_build_query(array_merge($config, ['page' => 1]));
                ?>
                    <option data-url="loads_list.php?<?= htmlspecialchars($qs) ?>"
                            value="<?= $v['view_id'] ?>">
                        <?= htmlspecialchars($v['view_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Export -->
        <div>
            <a class="btn btn-outline-success btn-sm"
               href="export_csv.php?<?= http_build_query($_GET) ?>">
                Export CSV
            </a>
        </div>
    </div>
</form>

<!-- Save / rename / delete view -->
<div class="row g-2 mb-4">
    <div class="col-md-4">
        <form method="POST" action="saved_views_handler.php" class="d-flex gap-2">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="current_filters" value='<?= htmlspecialchars(json_encode($_GET)) ?>'>
            <input type="text" name="view_name" class="form-control form-control-sm" placeholder="New view name">
            <button class="btn btn-outline-info btn-sm">Save View</button>
        </form>
    </div>

    <?php if (!empty($savedViews)): ?>
        <div class="col-md-4">
            <form method="POST" action="saved_views_handler.php" class="d-flex gap-2">
                <input type="hidden" name="action" value="rename">
                <select name="view_id" class="form-select form-select-sm">
                    <?php foreach ($savedViews as $v): ?>
                        <option value="<?= $v['view_id'] ?>"><?= htmlspecialchars($v['view_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="new_name" class="form-control form-control-sm" placeholder="New name">
                <button class="btn btn-outline-secondary btn-sm">Rename</button>
            </form>
        </div>

        <div class="col-md-4">
            <form method="POST" action="saved_views_handler.php" class="d-flex gap-2">
                <input type="hidden" name="action" value="delete">
                <select name="view_id" class="form-select form-select-sm">
                    <?php foreach ($savedViews as $v): ?>
                        <option value="<?= $v['view_id'] ?>"><?= htmlspecialchars($v['view_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-danger btn-sm">Delete</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Bulk actions + table -->
<form method="POST" action="bulk_actions.php">
    <input type="hidden" name="return_query" value="<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>">

    <div class="row mb-2">
        <div class="col-md-6 d-flex gap-2">
            <select name="bulk_action" class="form-select form-select-sm" style="max-width:200px;">
                <option value="">Bulk Actions</option>
                <option value="assign_driver">Assign Driver</option>
                <option value="mark_in_transit">Mark In Transit</option>
                <option value="mark_delivered">Mark Delivered</option>
                <?php if ($userRole === 'admin'): ?>
                    <option value="delete">Delete Loads</option>
                <?php endif; ?>
            </select>

            <?php
            // separate driver list for bulk assign
            $driversRes2 = $conn->query("SELECT id, username FROM users WHERE role='driver' ORDER BY username ASC");
            ?>
            <select name="bulk_driver_id" class="form-select form-select-sm" style="max-width:200px;">
                <option value="">Select Driver...</option>
                <?php while ($dr = $driversRes2->fetch_assoc()): ?>
                    <option value="<?= $dr['id'] ?>"><?= htmlspecialchars($dr['username']) ?></option>
                <?php endwhile; ?>
            </select>

            <button class="btn btn-outline-primary btn-sm">Apply</button>
        </div>

        <div class="col-md-6 text-end">
            <small class="text-muted">
                Showing <?= count($loads) ?> of <?= (int) $totalRows ?> loads |
                Page <?= $page ?> / <?= $totalPages ?>
            </small>
        </div>
    </div>

    <table class="table table-striped table-bordered align-middle">
        <thead class="table-dark">
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <?php
                // helper to render sortable header link
                function sortHeader(string $label, string $key, string $currentSort, string $order, array $extraParams): string {
                    $newOrder = ($currentSort === $key && strtolower($order) === 'asc') ? 'desc' : 'asc';
                    $icon = '';
                    if ($currentSort === $key) {
                        $icon = strtolower($order) === 'asc' ? '▲' : '▼';
                    }
                    $params = array_merge($extraParams, ['sort' => $key, 'order' => $newOrder, 'page' => 1]);
                    $url = '?' . http_build_query($params);
                    return '<a href="'.htmlspecialchars($url).'" class="link-light text-decoration-none">'
                           . htmlspecialchars($label) . ' ' . $icon . '</a>';
                }

                $extra = $_GET;
                ?>
                <th><?= sortHeader('ID', 'load_id', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Reference', 'reference', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Customer', 'customer', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Driver', 'driver', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Pickup', 'pickup_date', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Delivery', 'delivery_date', $sortKey, $order, $extra) ?></th>
                <th><?= sortHeader('Status', 'status', $sortKey, $order, $extra) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($loads)): ?>
            <?php foreach ($loads as $l): ?>
                <tr>
                    <td><input type="checkbox" name="load_ids[]" value="<?= $l['load_id'] ?>"></td>
                    <td><?= $l['load_id'] ?></td>
                    <td><?= htmlspecialchars($l['reference_number']) ?></td>
                    <td><?= htmlspecialchars($l['customer_company_name']) ?></td>
                    <td><?= htmlspecialchars($l['driver_name'] ?? '—') ?></td>
                    <td>
                        <?= htmlspecialchars($l['pickup_city']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($l['pickup_date']) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($l['delivery_city']) ?><br>
                        <small class="text-muted"><?= htmlspecialchars($l['delivery_date']) ?></small>
                    </td>
                    <td><?= statusBadge($l['load_status']) ?></td>
                    <td>
                        <a href="load_view.php?id=<?= $l['load_id'] ?>" class="btn btn-sm btn-primary">View</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="9" class="text-center text-muted">No loads found for the selected filters.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</form>

<!-- Pagination -->
<nav>
    <ul class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
                    <?= $i ?>
                </a>
            </li>
        <?php endfor; ?>
    </ul>
</nav>

<script>
// Select/deselect all checkboxes
document.getElementById('selectAll')?.addEventListener('change', function () {
    const checked = this.checked;
    document.querySelectorAll('input[name="load_ids[]"]').forEach(cb => cb.checked = checked);
});

// Saved view selection
document.getElementById('savedViewSelect')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    const url = opt.getAttribute('data-url');
    if (url) window.location.href = url;
});
</script>

</body>
</html>
