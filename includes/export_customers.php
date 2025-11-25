<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized.");
}

require_once __DIR__ . '/../services/config.php';

// Export format
$format = $_GET['export'] ?? 'csv';

// If exporting selected
$selectedIds = $_SESSION['export_ids'] ?? null;

// Build query
if ($selectedIds) {
    $ids = implode(",", array_map('intval', $selectedIds));
    $sql = "SELECT * FROM customers WHERE id IN ($ids)";
} else {
    // Export all filtered results
    $sql = "SELECT * FROM customers";
}

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$data) {
    echo "No data to export.";
    exit;
}

/* ========================
   EXPORT: CSV 
=========================*/
if ($format === 'csv') {
    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=customers.csv");

    $out = fopen("php://output", "w");
    fputcsv($out, array_keys($data[0]));

    foreach ($data as $row) {
        fputcsv($out, $row);
    }

    fclose($out);
    exit;
}

/* ========================
   EXPORT: EXCEL (.xlsx)
=========================*/
if ($format === 'xlsx') {

    require_once __DIR__ . "/../vendor/autoload.php";

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();

    $col = 1;
    foreach (array_keys($data[0]) as $header) {
        $sheet->setCellValueByColumnAndRow($col++, 1, $header);
    }

    $row = 2;
    foreach ($data as $item) {
        $col = 1;
        foreach ($item as $value) {
            $sheet->setCellValueByColumnAndRow($col++, $row, $value);
        }
        $row++;
    }

    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header("Content-Disposition: attachment; filename=customers.xlsx");

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save("php://output");
    exit;
}

/* ========================
   EXPORT: PDF
=========================*/
if ($format === 'pdf') {

    require_once __DIR__ . '/../vendor/autoload.php';

    $mpdf = new \Mpdf\Mpdf();

    $html = "<h2>Customers Export</h2><table border='1' cellpadding='5'><thead><tr>";

    foreach (array_keys($data[0]) as $h) {
        $html .= "<th>$h</th>";
    }

    $html .= "</tr></thead><tbody>";

    foreach ($data as $row) {
        $html .= "<tr>";
        foreach ($row as $v) {
            $html .= "<td>" . htmlspecialchars($v) . "</td>";
        }
        $html .= "</tr>";
    }

    $html .= "</tbody></table>";

    $mpdf->WriteHTML($html);
    $mpdf->Output("customers.pdf", "D");
    exit;
}

echo "Invalid export format.";
