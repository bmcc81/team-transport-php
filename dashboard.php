<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Require login
if (!isset($_SESSION['username'], $_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/services/config.php';

$loggedInUserId   = (int) $_SESSION['user_id'];
$loggedInUsername = $_SESSION['username'];
$userRole         = $_SESSION['role'] ?? 'user';
$isAdmin          = ($userRole === 'admin');

$perPage = 25;

/* -------------------------------------------
   Helper to build URLs with preserved filters
--------------------------------------------*/
function tt_url(array $params = []): string {
    $base  = strtok($_SERVER['REQUEST_URI'], '?');
    $query = array_merge($_GET, $params);
    $query = array_filter($query, fn($v) => $v !== '' && $v !== null);
    return htmlspecialchars($base . (empty($query) ? '' : '?' . http_build_query($query)));
}

/* -------------------------------------------
   GET Inputs
--------------------------------------------*/
$page         = max(1, (int) ($_GET['page'] ?? 1));
$search       = trim($_GET['search'] ?? '');
$ownerFilter  = trim($_GET['owner'] ?? '');
$cityFilter   = trim($_GET['city'] ?? '');
$stateFilter  = trim($_GET['state'] ?? '');
$countryFilter= trim($_GET['country'] ?? '');
$createdFrom  = trim($_GET['created_from'] ?? '');
$createdTo    = trim($_GET['created_to'] ?? '');

$sort  = $_GET['sort'] ?? 'created_at';
$order = strtolower($_GET['order'] ?? 'desc');
$order = ($order === 'asc') ? 'asc' : 'desc';

$sortMap = [
    'company'    => 'customer_company_name',
    'owner'      => 'customer_internal_handler_name',
    'first'      => 'customer_contact_first_name',
    'last'       => 'customer_contact_last_name',
    'email'      => 'customer_email',
    'city'       => 'customer_contact_city',
    'country'    => 'customer_contact_country',
    'created_at' => 'created_at',
];
$orderBy = $sortMap[$sort] ?? 'created_at';

/* -------------------------------------------
   WHERE builder (HY093-proof)
--------------------------------------------*/
$whereParts = [];
$params     = [];

if ($isAdmin) {
    $whereParts[] = "1=1";
} else {
    $whereParts[] = "user_id = :uid";
    $params[':uid'] = $loggedInUserId;
}

if ($search !== "") {
    $whereParts[] = "(
        customer_company_name LIKE :search
        OR customer_contact_first_name LIKE :search
        OR customer_contact_last_name LIKE :search
        OR customer_email LIKE :search
        OR customer_contact_city LIKE :search
    )";
    $params[':search'] = '%' . $search . '%';
}

if ($ownerFilter !== "") {
    $whereParts[] = "customer_internal_handler_name = :owner";
    $params[':owner'] = $ownerFilter;
}

if ($cityFilter !== "") {
    $whereParts[] = "customer_contact_city = :city";
    $params[':city'] = $cityFilter;
}

if ($stateFilter !== "") {
    $whereParts[] = "customer_contact_state_or_province = :state";
    $params[':state'] = $stateFilter;
}

if ($countryFilter !== "") {
    $whereParts[] = "customer_contact_country = :country";
    $params[':country'] = $countryFilter;
}

if ($createdFrom !== "") {
    $whereParts[] = "DATE(created_at) >= :created_from";
    $params[':created_from'] = $createdFrom;
}

if ($createdTo !== "") {
    $whereParts[] = "DATE(created_at) <= :created_to";
    $params[':created_to'] = $createdTo;
}

$whereSql = implode(" AND ", $whereParts);

// FINAL PARAM CLEANUP — Remove params not present in final WHERE SQL
preg_match_all('/:\w+/', $whereSql, $found);
$validPlaceholders = $found[0];

foreach ($params as $p => $v) {
    if (!in_array($p, $validPlaceholders, true)) {
        unset($params[$p]);
    }
}

/* -------------------------------------------
   DISTINCT Query Function (RENAMED PARAM)
--------------------------------------------*/
function tt_fetchDistinct(PDO $pdo, string $column, string $whereClause, array $params): array {

    $sql = "SELECT DISTINCT $column
            FROM customers
            WHERE $whereClause
              AND $column <> ''
            ORDER BY $column";

    $stmt = $pdo->prepare($sql);

    // Bind only params actually referenced
    foreach ($params as $k => $v) {
        if (str_contains($sql, $k)) {
            $stmt->bindValue($k, $v);
        }
    }

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/* -------------------------------------------
   DISTINCT filters (correct params!)
--------------------------------------------*/
$distinctWhere  = $isAdmin ? "1=1" : "user_id = :uid";
$distinctParams = $isAdmin ? [] : [':uid' => $loggedInUserId];

$owners    = tt_fetchDistinct($pdo, 'customer_internal_handler_name', $distinctWhere, $distinctParams);
$cities    = tt_fetchDistinct($pdo, 'customer_contact_city',          $distinctWhere, $distinctParams);
$states    = tt_fetchDistinct($pdo, 'customer_contact_state_or_province', $distinctWhere, $distinctParams);
$countries = tt_fetchDistinct($pdo, 'customer_contact_country',       $distinctWhere, $distinctParams);

/* -------------------------------------------
   COUNT filtered rows (HY093-safe)
--------------------------------------------*/
$countSql = "SELECT COUNT(*) FROM customers WHERE $whereSql";
$stmt = $pdo->prepare($countSql);

foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->execute();
$totalFiltered = (int) $stmt->fetchColumn();

/* -------------------------------------------
   Pagination
--------------------------------------------*/
$totalPages = max(1, (int) ceil($totalFiltered / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

/* -------------------------------------------
   Final data SQL
--------------------------------------------*/
$dataSql = "
    SELECT *
    FROM customers
    WHERE $whereSql
    ORDER BY $orderBy $order
    LIMIT :limit OFFSET :offset
";

$stmt = $pdo->prepare($dataSql);

// Bind where params
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}

$stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset,   PDO::PARAM_INT);

$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------------------------------
   Stats (uses distinctWhere + distinctParams)
--------------------------------------------*/
$statWhere  = $distinctWhere;
$statParams = $distinctParams;

function tt_stat(PDO $pdo, string $sql, array $params): int {
    $stmt = $pdo->prepare($sql);

    foreach ($params as $k => $v) {
        if (str_contains($sql, $k)) {
            $stmt->bindValue($k, $v);
        }
    }

    $stmt->execute();
    return (int) $stmt->fetchColumn();
}

$totalCustomers    = tt_stat($pdo, "SELECT COUNT(*) FROM customers WHERE $statWhere", $statParams);
$newThisMonth      = tt_stat($pdo, "SELECT COUNT(*) FROM customers WHERE $statWhere AND created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')", $statParams);
$distinctCities    = tt_stat($pdo, "SELECT COUNT(DISTINCT customer_contact_city) FROM customers WHERE $statWhere", $statParams);
$distinctCountries = tt_stat($pdo, "SELECT COUNT(DISTINCT customer_contact_country) FROM customers WHERE $statWhere", $statParams);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>

<body class="bg-light">

<div class="container-fluid py-3">

    <h3>Customers Dashboard</h3>
    <p class="text-muted">Logged in as <?= htmlspecialchars($loggedInUsername) ?> (<?= htmlspecialchars($userRole) ?>)</p>

    <!-- Stats -->
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card p-3 shadow-sm">Total: <b><?= $totalCustomers ?></b></div></div>
        <div class="col-md-3"><div class="card p-3 shadow-sm">New This Month: <b><?= $newThisMonth ?></b></div></div>
        <div class="col-md-3"><div class="card p-3 shadow-sm">Cities: <b><?= $distinctCities ?></b></div></div>
        <div class="col-md-3"><div class="card p-3 shadow-sm">Countries: <b><?= $distinctCountries ?></b></div></div>
    </div>

    <!-- Filters -->
    <form method="GET" class="card p-3 shadow-sm mb-4">
        <div class="row g-2">

            <div class="col-md-3">
                <input name="search" class="form-control form-control-sm" placeholder="Search..." 
                       value="<?= htmlspecialchars($search) ?>">
            </div>

            <div class="col-md-2">
                <select name="owner" class="form-select form-select-sm">
                    <option value="">Owner</option>
                    <?php foreach ($owners as $o): ?>
                        <option <?= $o === $ownerFilter ? 'selected':'' ?>>
                            <?= htmlspecialchars($o) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="city" class="form-select form-select-sm">
                    <option value="">City</option>
                    <?php foreach ($cities as $c): ?>
                        <option <?= $c === $cityFilter ? 'selected':'' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="state" class="form-select form-select-sm">
                    <option value="">State</option>
                    <?php foreach ($states as $s): ?>
                        <option <?= $s === $stateFilter ? 'selected':'' ?>>
                            <?= htmlspecialchars($s) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-2">
                <select name="country" class="form-select form-select-sm">
                    <option value="">Country</option>
                    <?php foreach ($countries as $c): ?>
                        <option <?= $c === $countryFilter ? 'selected':'' ?>>
                            <?= htmlspecialchars($c) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-2">
                <input type="date" name="created_from" class="form-control form-control-sm" value="<?= $createdFrom ?>">
            </div>

            <div class="col-md-2">
                <input type="date" name="created_to" class="form-control form-control-sm" value="<?= $createdTo ?>">
            </div>

            <div class="col-md-3">
                <button class="btn btn-primary btn-sm">Apply</button>
                <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
            </div>

        </div>
    </form>

    <!-- Table -->
    <div class="table-responsive shadow-sm">
        <table class="table table-sm table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Company</th>
                    <th>Owner</th>
                    <th>First</th>
                    <th>Last</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Country</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

            <?php foreach ($customers as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['customer_company_name']) ?></td>
                    <td><?= htmlspecialchars($c['customer_internal_handler_name']) ?></td>
                    <td><?= htmlspecialchars($c['customer_contact_first_name']) ?></td>
                    <td><?= htmlspecialchars($c['customer_contact_last_name']) ?></td>
                    <td><?= htmlspecialchars($c['customer_email']) ?></td>
                    <td><?= htmlspecialchars($c['customer_contact_city']) ?></td>
                    <td><?= htmlspecialchars($c['customer_contact_country']) ?></td>
                    <td><?= htmlspecialchars($c['created_at']) ?></td>
                    <td>
                        <a class="btn btn-primary btn-sm" href="views/update_client_view.php?id=<?= $c['id'] ?>">Edit</a>
                        <a class="btn btn-info btn-sm" href="views/loads_by_client.php?customer_id=<?= $c['id'] ?>">Loads</a>
                    </td>
                </tr>
            <?php endforeach ?>

            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <nav class="mt-3">
        <ul class="pagination pagination-sm justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled':'' ?>">
                <a class="page-link" href="<?= tt_url(['page'=>$page-1]) ?>">Prev</a>
            </li>

            <?php for ($p=max(1,$page-2); $p<=min($totalPages,$page+2); $p++): ?>
                <li class="page-item <?= $p==$page?'active':'' ?>">
                    <a class="page-link" href="<?= tt_url(['page'=>$p]) ?>"><?= $p ?></a>
                </li>
            <?php endfor ?>

            <li class="page-item <?= $page >= $totalPages ? 'disabled':'' ?>">
                <a class="page-link" href="<?= tt_url(['page'=>$page+1]) ?>">Next</a>
            </li>
        </ul>
    </nav>

</div>

</body>
</html>
