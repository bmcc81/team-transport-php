<?php
session_start();

if (!isset($_SESSION['user_id'], $_SESSION['username'])) {
    header("Location: ../index.php");
    exit;
}

require_once __DIR__ . '/../services/config.php';
require_once __DIR__ . '/../includes/header.php';

if (!isset($_GET['customer_id']) || !is_numeric($_GET['customer_id'])) {
    $_SESSION['error'] = "Invalid customer ID.";
    header("Location: ../dashboard.php");
    exit;
}

$customerId   = (int) $_GET['customer_id'];
$userRole     = $_SESSION['role'] ?? 'user';
$loggedUserId = (int) $_SESSION['user_id'];

$perPage = 25;

// Helper to build URLs keeping existing query params
function tt_build_url(array $params = []): string {
    $base  = strtok($_SERVER['REQUEST_URI'], '?');
    $query = array_merge($_GET, $params);
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return htmlspecialchars($base . (!empty($query) ? ('?' . http_build_query($query)) : ''));
}

try {
    // Verify customer belongs to this user (if not admin)
    if ($userRole === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = :id");
        $stmt->execute([':id' => $customerId]);
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM customers 
            WHERE id = :id AND user_id = :uid
        ");
        $stmt->execute([
            ':id'  => $customerId,
            ':uid' => $loggedUserId
        ]);
    }

    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customer) {
        $_SESSION['error'] = "Customer not found or access denied.";
        header("Location: ../dashboard.php");
        exit;
    }

    // ---- Filters & sorting ----
    $page          = max(1, (int) ($_GET['page'] ?? 1));
    $search        = trim($_GET['search'] ?? '');
    $statusFilter  = trim($_GET['status'] ?? '');
    $pickupFrom    = $_GET['pickup_from'] ?? '';
    $pickupTo      = $_GET['pickup_to'] ?? '';
    $deliveryFrom  = $_GET['delivery_from'] ?? '';
    $deliveryTo    = $_GET['delivery_to'] ?? '';

    $sort  = $_GET['sort'] ?? 'created_at';
    $order = strtolower($_GET['order'] ?? 'desc');
    $order = $order === 'asc' ? 'asc' : 'desc';

    $sortMap = [
        'id'           => 'l.load_id',
        'reference'    => 'l.reference_number',
        'pickup_date'  => 'l.pickup_date',
        'delivery_date'=> 'l.delivery_date',
        'status'       => 'l.load_status',
        'created_at'   => 'l.created_at',
    ];

    $orderBy = $sortMap[$sort] ?? 'l.created_at';

    // ---- Build WHERE clause ----
    $where  = ['l.customer_id = :customer_id'];
    $params = [':customer_id' => $customerId];

    if ($search !== '') {
        $where[] = "(
            l.reference_number LIKE :search
            OR l.pickup_city LIKE :search
            OR l.delivery_city LIKE :search
            OR l.load_id = :search_exact_id
        )";
        $params[':search'] = '%' . $search . '%';
        $params[':search_exact_id'] = (int) $search ?: 0;
    }

    if ($statusFilter !== '') {
        $where[]              = 'l.load_status = :status';
        $params[':status']    = $statusFilter;
    }

    if ($pickupFrom !== '') {
        $where[]                 = 'DATE(l.pickup_date) >= :pickup_from';
        $params[':pickup_from']  = $pickupFrom;
    }
    if ($pickupTo !== '') {
        $where[]               = 'DATE(l.pickup_date) <= :pickup_to';
        $params[':pickup_to']  = $pickupTo;
    }

    if ($deliveryFrom !== '') {
        $where[]                 = 'DATE(l.delivery_date) >= :delivery_from';
        $params[':delivery_from'] = $deliveryFrom;
    }
    if ($deliveryTo !== '') {
        $where[]               = 'DATE(l.delivery_date) <= :delivery_to';
        $params[':delivery_to'] = $deliveryTo;
    }

    $whereSql = implode(' AND ', $where);

    // Distinct statuses for dropdown
    $stmt = $pdo->prepare("
        SELECT DISTINCT l.load_status
        FROM loads l
        WHERE l.customer_id = :cid
          AND l.load_status IS NOT NULL
          AND l.load_status <> ''
        ORDER BY l.load_status ASC
    ");
    $stmt->execute([':cid' => $customerId]);
    $statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Count total filtered
    $countSql = "
        SELECT COUNT(*) 
        FROM loads l
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
    <title>Loads for Customer</title>
    <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
    <style>
      @media (max-width: 768px) {
        .table-responsive table thead {
          display: none;
        }
        .table-responsive table tbody tr {
          display: block;
          margin-bottom: 1rem;
          border-radius: 0.75rem;
          box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.08);
          background: #fff;
          overflow: hidden;
        }
        .table-responsive table tbody tr td {
          display: flex;
          justify-content: space-between;
          padding: 0.5rem 0.75rem;
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
<div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="../dashboard.php" class="btn btn-outline-secondary btn-sm mb-2">← Back to Dashboard</a>
            <h3 class="mb-0">
                Loads for <?= htmlspecialchars($customer['customer_company_name']); ?>
            </h3>
            <small class="text-muted">Customer ID: <?= $customerId; ?></small>
        </div>
        <div>
            <a href="customer_profile.php?id=<?= $customerId; ?>" class="btn btn-primary btn-sm">
                View Customer Profile
            </a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" class="card mb-3">
        <div class="card-body">
            <input type="hidden" name="customer_id" value="<?= $customerId; ?>">

            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Reference, city, ID"
                           value="<?= htmlspecialchars($search); ?>">
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <?php foreach ($statuses as $status): ?>
                          <option value="<?= htmlspecialchars($status); ?>"
                            <?= $statusFilter === $status ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($status); ?>
                          </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Pickup From</label>
                    <input type="date" name="pickup_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($pickupFrom); ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label mb-1">Pickup To</label>
                    <input type="date" name="pickup_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($pickupTo); ?>">
                </div>

                <div class="col-md-3 mt-2">
                    <label class="form-label mb-1">Delivery From</label>
                    <input type="date" name="delivery_from" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($deliveryFrom); ?>">
                </div>

                <div class="col-md-3 mt-2">
                    <label class="form-label mb-1">Delivery To</label>
                    <input type="date" name="delivery_to" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($deliveryTo); ?>">
                </div>

                <div class="col-md-6 mt-2 d-flex justify-content-md-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">Apply Filters</button>
                    <a href="loads_by_client.php?customer_id=<?= $customerId; ?>"
                       class="btn btn-sm btn-outline-secondary">Reset</a>
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
                                    'sort'  => 'id',
                                    'order' => $sort === 'id' && $order === 'asc' ? 'desc' : 'asc'
                                ]); ?>">
                                    ID<?= $sort === 'id' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= tt_build_url([
                                    'sort'  => 'reference',
                                    'order' => $sort === 'reference' && $order === 'asc' ? 'desc' : 'asc'
                                ]); ?>">
                                    Reference<?= $sort === 'reference' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= tt_build_url([
                                    'sort'  => 'pickup_date',
                                    'order' => $sort === 'pickup_date' && $order === 'asc' ? 'desc' : 'asc'
                                ]); ?>">
                                    Pickup<?= $sort === 'pickup_date' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= tt_build_url([
                                    'sort'  => 'delivery_date',
                                    'order' => $sort === 'delivery_date' && $order === 'asc' ? 'desc' : 'asc'
                                ]); ?>">
                                    Delivery<?= $sort === 'delivery_date' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="<?= tt_build_url([
                                    'sort'  => 'status',
                                    'order' => $sort === 'status' && $order === 'asc' ? 'desc' : 'asc'
                                ]); ?>">
                                    Status<?= $sort === 'status' ? ($order === 'asc' ? ' ▲' : ' ▼') : ''; ?>
                                </a>
                            </th>
                            <th>Rate</th>
                            <th>
                                <a href="<?= tt_build_url([
                                    'sort'  => 'created_at',
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
                                <td data-label="Reference"><?= htmlspecialchars($load['reference_number']); ?></td>
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
                                <td data-label="Rate">
                                    <?= htmlspecialchars($load['rate_amount']); ?>
                                    <?= htmlspecialchars($load['currency']); ?>
                                </td>
                                <td data-label="Created">
                                    <?= htmlspecialchars($load['created_at']); ?>
                                </td>
                                <td data-label="Actions" class="text-nowrap">
                                    <!-- <a href="/views/loads/loads_by_client.php?client_id=<?= (int) $customer['id']; ?>"
                                       class="btn btn-sm btn-primary">View</a> -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="8" class="text-center py-3">No loads found with current filters.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <nav class="mt-3" aria-label="Loads pagination">
      <ul class="pagination pagination-sm justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : ''; ?>">
          <a class="page-link" href="<?= $page > 1 ? tt_build_url(['page' => $page - 1]) : '#'; ?>">
            Previous
          </a>
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
          <a class="page-link" href="<?= $page < $totalPages ? tt_build_url(['page' => $page + 1]) : '#'; ?>">
            Next
          </a>
        </li>
      </ul>
    </nav>

</div>

<script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
