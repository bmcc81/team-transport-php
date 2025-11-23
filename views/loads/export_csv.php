<?php
// views/loads/export_csv.php
session_start();
require_once __DIR__ . '/../../services/config.php';

if (!isset($_SESSION['user_id'])) {
    die("Not logged in");
}

$userId = (int) $_SESSION['user_id'];

// Reuse the filter logic from loads_query.php BUT without limit/offset.
require_once __DIR__ . '/loads_query.php';

// At this point, loads_query.php has already built:
// $whereSQL, $params, $types, $sortColumn, $order
// but it ALSO executed paginated SELECT and COUNT.
// We want a full export query instead.

$exportSQL = "
    SELECT l.*,
           c.customer_company_name,
           u.username AS driver_name
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON l.assigned_driver_id = u.id
    $whereSQL
    ORDER BY $sortColumn $order
";

$stmt = $conn->prepare($exportSQL);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=loads_export.csv");

$out = fopen("php://output", "w");
fputcsv($out, [
    'Load ID',
    'Reference',
    'Customer',
    'Driver',
    'Pickup City',
    'Pickup Date',
    'Delivery City',
    'Delivery Date',
    'Status',
    'Rate Amount',
    'Currency',
]);

foreach ($rows as $l) {
    fputcsv($out, [
        $l['load_id'],
        $l['reference_number'],
        $l['customer_company_name'],
        $l['driver_name'],
        $l['pickup_city'],
        $l['pickup_date'],
        $l['delivery_city'],
        $l['delivery_date'],
        $l['load_status'],
        $l['rate_amount'],
        $l['rate_currency'],
    ]);
}
exit;
