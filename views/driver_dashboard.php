<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/services/config.php';

$userId   = (int) $_SESSION['user_id'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'] ?? 'user';

$perPage = 25;

// Only allow drivers, admins, dispatchers to use this
$allowedRoles = ['driver', 'admin', 'dispatcher'];
if (!in_array($role, $allowedRoles, true)) {
    $_SESSION['error'] = "Unauthorized.";
    header("Location: dashboard.php");
    exit;
}

// Which driver are we showing?
// - If you're a driver: always your own loads
// - If admin/dispatcher: optional ?driver_id= to view that driver, otherwise all loads
$driverIdFilter = null;
if ($role === 'driver') {
    $driverIdFilter = $userId;
} elseif (isset($_GET['driver_id']) && is_numeric($_GET['driver_id'])) {
    $driverIdFilter = (int) $_GET['driver_id'];
}

// Helper url builder
function tt_build_url(array $params = []): string {
    $base  = strtok($_SERVER['REQUEST_URI'], '?');
    $query = array_merge($_GET, $params);
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return htmlspecialchars($base . (!empty($query) ? ('?' . http_build_query($query)) : ''));
}

// Filters & sorting
$page         = max(1, (int) ($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$dateFrom     = $_GET['date_from'] ?? '';
$dateTo       = $_GET['date_to'] ?? '';

$sort  = $_GET['sort'] ?? 'pickup_date';
$order = strtolower($_GET['order'] ?? 'asc');
$order = $order === 'desc' ? 'desc' : 'asc';

$sortMap = [
    'pickup_date'   => 'l.pickup_date',
    'delivery_date' => 'l.delivery_date',
    'created_at'    => 'l.created_at',
    'status'        => 'l.load_status',
    'reference'     => 'l.reference_number',
    'id'            => 'l.load_id',
];

$orderBy = $sortMap[$sort] ?? 'l.pickup_date';

// Build WHERE
$where  = ['1=1'];
$params = [];

if ($driverIdFilter !== null) {
    $where[]               = 'l.assigned_driver_id = :driver_id';
    $params[':driver_id']  = $driverIdFilter;
}

if ($search !== '') {
    $where[] = "(
        l.reference_number LIKE :search
        OR l.pickup_city LIKE :search
        OR l.delivery_city LIKE :search
        OR c.customer_company_name LIKE :search
        OR l.load_id = :search_id
    )";
    $params[':search']    = '%' . $search . '%';
    $params[':search_id'] = (int) $search ?: 0;
}

if ($statusFilter !== '') {
    $where[]              = 'l.load_status = :status';
    $params[':status']    = $statusFilter;
}

// Date filter (on pickup_date, for driver planning)
if ($dateFrom !== '') {
    $where[]              = 'DATE(l.pickup_date) >= :date_from';
    $params[':date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $where[]            = 'DATE(l.pickup_date) <= :date_to';
    $params[':date_to'] = $dateTo;
}

$whereSql = implode(' AND ', $where);

try {
    // Distinct statuses
    $statusSql = "
        SELECT DISTINCT l.load_status
        FROM loads l
        " . ($driverIdFilter !== null ? "WHERE l.assigned_driver_id = :driver_id" : "") . "
        AND l.load_status IS NOT NULL
        AND l.load_status <> ''
        ORDER BY l.load_status ASC
    ";
    $stmt = $pdo->prepare($statusSql);
    if ($driverIdFilter !== null) {
        $stmt->bindValue(':driver_id', $driverIdFilter, PDO::PARAM_INT);
    }
    $stmt->execute();
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Status counts for summary cards
    $cardsSql = "
        SELECT l.load_status, COUNT(*) as total
        FROM loads l
        JOIN customers c ON l.customer_id = c.id
        WHERE $whereSql
        GROUP BY l.load_status
    ";
    $stmt = $pdo->prepare($cardsSql);
    $stmt->execute($params);
    $statusCounts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // [status => total]

    // Count total filtered
    $countSql = "
        SELECT COUNT(*)
        FROM loads l
        JOIN customers c ON l.customer_id = c.id
        WHERE $whereSql
    ";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalFiltered = (int) $stmt->fetchColumn();

    $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
    if ($page > $totalPages) $page = $totalPages;
    $offset = ($page - 1) * $perPage;

    // Fetch page of loads
    $dataSql = "
        SELECT l.*, c.customer_company_name
        FROM loads l
        JOIN customers c ON l.customer_id = c.id
        WHERE $whereSql
        ORDER BY $orderBy $order
        LIMIT :limit OFFSET :offset
    ";
    $stmt = $pdo->prepare($dataSql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Loads</title>
    <link href="./styles/css/bootstrap.min.css" rel="stylesheet">
    <link href="./styles/shared.css" rel="stylesheet">
    <style>
      .stat-card {
        border-radius: .75rem;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.05);
      }
      @media (max-width: 768px) {
        .table-responsive table thead {
          display: none;
        }
        .table-responsive table tbody tr {
          display: block;
          margin-bottom: 1rem;
          border-radius: .75rem;
          box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.08);
          background: #fff;
          overflow: hidden;
        }
        .table-responsive table tbody tr td {
          display: flex;
          justify-content: space-between;
          padding: .5rem .75rem;
          border-top: none !important;
          border-bottom: 1px solid #f0f0f0;
        }
        .table-responsive table tbody tr td:last-child {
          border-bottom: none;
        }
        .table-responsive table tbody tr td::before {
          content: attr(data-label);
          font-weight: 600;
          margin-right: .75rem;
          color: #555;
        }
      }
    </style>
</head>
<body class="bg-light">

<header class="py-3 border-bottom mb-3">
  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h4 class="mb-0">
          <?= $role === 'driver' ? 'My Loads' : 'Driver Loads'; ?>
        </h4>
        <div class="text-muted small">
          Logged in as <?= htmlspecialchars($username); ?> (<?= htmlspecialchars($role); ?>)
        </div>
      </div>
      <div>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">Customers Dashboard</a>
      </div>
    </div>
  </div>
</header>

<div class="container-fluid mb-4">

  <!-- Status cards -->
  <div class="row g-3 mb-3">
    <?php if (!empty($statusCounts)): ?>
      <?php foreach ($statusCounts as $status => $count): ?>
        <div class="col-sm-6 col-md-3">
          <div class="card stat-card border-0">
            <div class="card-body py-2">
              <div class="text-muted small"><?= htmlspecialchars($status); ?></div>
              <div class="h4 mb-0"><?= (int) $count; ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="col-12">
        <div class="card stat-card border-0">
          <div class="card-body py-2">
            <div class="text-muted small">Loads</div>
            <div class="h4 mb-0">0</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Filters -->
  <form method="GET" class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-md-3">
          <label class="form-label mb-1">Search</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 placeholder="Ref, city, customer, ID"
                 value="<?= htmlspecialchars($search); ?>">
        </div>

        <div class="col-md-2">
          <label class="form-label mb-1">Status</label>
          <select name="status" class="form-select form-select-sm">
            <option value="">All</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= htmlspecialchars($s); ?>" <?= $statusFilter === $s ? 'selected' : ''; ?>>
                <?= htmlspecialchars($s); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Pickup From</label>
          <input type="date" name="date_from" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($dateFrom); ?>">
        </div>

        <div class="col-md-3">
          <label class="form-label mb-1">Pickup To</label>
          <input type="date" name="date_to" class="form-control form-control-sm"
                 value="<?= htmlspecialchars($dateTo); ?>">
        </div>

        <?php if ($role !== 'driver'): ?>
          <div class="col-md-3 mt-2">
            <label class="form-label mb-1">Driver ID (optional)</label>
            <input type="number" name="driver_id" class="form-control form-control-sm"
                   value="<?= $driverIdFilter !== null && $role !== 'driver' ? (int) $driverIdFilter : ''; ?>">
          </div>
        <?php endif; ?>

        <div class="col-md-6 mt-2 d-flex justify-content-md-end gap-2">
          <button type="submit" class="btn btn-sm btn-primary">Apply Filters</button>
          <a href="driver_dashboard.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
      </div>
    </div>
  </form>

  <!-- Summary -->
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="text-muted small">
      Showing
      <?= $totalFiltered > 0 ? ($offset + 1) : 0; ?> –
      <?= min($offset + $perPage, $totalFiltered); ?>
      of <?= number_format($totalFiltered); ?> loads
    </div>
  </div>

  <!-- Loads table -->
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
          <thead class="table-theme">
            <tr>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'id',
                  'order' => $sort === 'id' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  ID<?= $sort === 'id' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'reference',
                  'order' => $sort === 'reference' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  Reference<?= $sort === 'reference' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>Customer</th>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'pickup_date',
                  'order' => $sort === 'pickup_date' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  Pickup<?= $sort === 'pickup_date' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'delivery_date',
                  'order' => $sort === 'delivery_date' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  Delivery<?= $sort === 'delivery_date' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'status',
                  'order' => $sort === 'status' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  Status<?= $sort === 'status' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>
                <a href="<?= tt_build_url([
                  'sort' => 'created_at',
                  'order' => $sort === 'created_at' && $order === 'asc' ? 'desc' : 'asc'
                ]); ?>">
                  Created<?= $sort === 'created_at' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                </a>
              </th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!empty($loads)): ?>
            <?php foreach ($loads as $load): ?>
              <tr>
                <td data-label="ID">#<?= (int) $load['load_id']; ?></td>
                <td data-label="Ref"><?= htmlspecialchars($load['reference_number']); ?></td>
                <td data-label="Customer">
                  <?= htmlspecialchars($load['customer_company_name']); ?>
                </td>
                <td data-label="Pickup">
                  <?= htmlspecialchars($load['pickup_city']); ?><br>
                  <small class="text-muted"><?= htmlspecialchars($load['pickup_date']); ?></small>
                </td>
                <td data-label="Delivery">
                  <?= htmlspecialchars($load['delivery_city']); ?><br>
                  <small class="text-muted"><?= htmlspecialchars($load['delivery_date']); ?></small>
                </td>
                <td data-label="Status">
                  <span class="badge bg-info text-dark">
                    <?= htmlspecialchars($load['load_status']); ?>
                  </span>
                </td>
                <td data-label="Created">
                  <?= htmlspecialchars($load['created_at']); ?>
                </td>
                <td data-label="Actions" class="text-nowrap">
                  <a href="views/load_view.php?id=<?= (int) $load['load_id']; ?>"
                     class="btn btn-sm btn-primary mb-1">
                    View
                  </a>
                  <!-- If you have a status update endpoint, you can wire quick buttons here -->
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="8" class="text-center py-3">No loads found.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination -->
  <nav class="mt-3" aria-label="Driver loads pagination">
    <ul class="pagination pagination-sm justify-content-center">
      <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?= $page > 1 ? tt_build_url(['page' => $page - 1]) : '#'; ?>">Previous</a>
      </li>
      <?php
        $startPage = max(1, $page - 2);
        $endPage   = min($totalPages, $page + 2);
        for ($p = $startPage; $p <= $endPage; $p++): ?>
        <li class="page-item <?= $p === $page ? 'active' : ''; ?>">
          <a class="page-link" href="<?= tt_build_url(['page' => $p]); ?>"><?= $p; ?></a>
        </li>
      <?php endfor; ?>
      <li class="page-item <?= $page >= $totalPages ? 'disabled' : ''; ?>">
        <a class="page-link" href="<?= $page < $totalPages ? tt_build_url(['page' => $page + 1]) : '#'; ?>">Next</a>
      </li>
    </ul>
  </nav>

</div>

<script src="./styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
