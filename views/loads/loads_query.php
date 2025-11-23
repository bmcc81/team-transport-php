<?php
// views/loads/loads_query.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($conn)) {
    die("DB connection not available in loads_query.php");
}

$userId   = (int)($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['role'] ?? 'driver';

// Helper: status badge
function statusBadge(string $status): string {
    $status = strtolower($status);
    return match ($status) {
        'delivered'   => '<span class="badge bg-success">Delivered</span>',
        'pending'     => '<span class="badge bg-warning text-dark">Pending</span>',
        'in_transit'  => '<span class="badge bg-primary">In Transit</span>',
        'assigned'    => '<span class="badge" style="background:#6f42c1;">Assigned</span>',
        'cancelled'   => '<span class="badge bg-danger">Cancelled</span>',
        default       => '<span class="badge bg-secondary">'.htmlspecialchars($status).'</span>',
    };
}

// Pagination
$perPage = 10;
$page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Filters from GET
$search        = $_GET['search']        ?? '';
$status        = $_GET['status']        ?? '';
$customer      = $_GET['customer']      ?? '';
$driver        = $_GET['driver']        ?? '';
$pickup_from   = $_GET['pickup_from']   ?? '';
$pickup_to     = $_GET['pickup_to']     ?? '';
$delivery_from = $_GET['delivery_from'] ?? '';
$delivery_to   = $_GET['delivery_to']   ?? '';

// Sorting
$allowedSorts = [
    'load_id'       => 'l.load_id',
    'reference'     => 'l.reference_number',
    'customer'      => 'c.customer_company_name',
    'driver'        => 'u.username',
    'pickup_date'   => 'l.pickup_date',
    'delivery_date' => 'l.delivery_date',
    'status'        => 'l.load_status',
];

$sortKey    = $_GET['sort'] ?? 'load_id';
$sortColumn = $allowedSorts[$sortKey] ?? 'l.load_id';
$order      = strtolower($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// WHERE clause building
$whereClauses = [];
$params = [];
$types  = '';

if ($search !== '') {
    $whereClauses[] = "(l.reference_number LIKE ?
                        OR c.customer_company_name LIKE ?
                        OR u.username LIKE ?
                        OR l.pickup_city LIKE ?
                        OR l.delivery_city LIKE ?)";
    for ($i = 0; $i < 5; $i++) {
        $params[] = "%$search%";
        $types   .= 's';
    }
}

if ($status !== '') {
    $whereClauses[] = "l.load_status = ?";
    $params[] = $status;
    $types   .= 's';
}

if ($customer !== '') {
    $whereClauses[] = "l.customer_id = ?";
    $params[] = (int) $customer;
    $types   .= 'i';
}

if ($driver !== '') {
    $whereClauses[] = "l.assigned_driver_id = ?";
    $params[] = (int) $driver;
    $types   .= 'i';
}

if ($pickup_from !== '') {
    $whereClauses[] = "l.pickup_date >= ?";
    $params[] = $pickup_from;
    $types   .= 's';
}

if ($pickup_to !== '') {
    $whereClauses[] = "l.pickup_date <= ?";
    $params[] = $pickup_to;
    $types   .= 's';
}

if ($delivery_from !== '') {
    $whereClauses[] = "l.delivery_date >= ?";
    $params[] = $delivery_from;
    $types   .= 's';
}

if ($delivery_to !== '') {
    $whereClauses[] = "l.delivery_date <= ?";
    $params[] = $delivery_to;
    $types   .= 's';
}

$whereSQL = count($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count rows for pagination
$countSQL = "
    SELECT COUNT(*) AS total
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON l.assigned_driver_id = u.id
    $whereSQL
";

$stmt = $conn->prepare($countSQL);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$totalRows = (int) ($row['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int) ceil($totalRows / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// Fetch current page
$listSQL = "
    SELECT l.*,
           c.customer_company_name,
           u.username AS driver_name
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON l.assigned_driver_id = u.id
    $whereSQL
    ORDER BY $sortColumn $order
    LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($listSQL);
if ($params) {
    $typesWithLimit = $types . 'ii';
    $paramsWithLimit = array_merge($params, [$perPage, $offset]);
    $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
} else {
    $stmt->bind_param('ii', $perPage, $offset);
}
$stmt->execute();
$loads = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
