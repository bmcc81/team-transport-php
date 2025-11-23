<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

// LOAD TCPDF AUTOLOADER (Correct Path)
require_once __DIR__ . "/../vendor/autoload.php";

// LOAD DATABASE CONFIG (Correct Path)
require_once __DIR__ . "/config.php";

// VALIDATE INPUT -----------------------------
$docType = $_GET['type'] ?? '';
$loadId = (int) ($_GET['load_id'] ?? 0);

$validTypes = ['pod', 'bol', 'summary'];

if (!in_array($docType, $validTypes)) {
    die("Invalid PDF type.");
}
if ($loadId <= 0) {
    die("Invalid load ID.");
}

// FETCH LOAD FROM DB
$stmt = $conn->prepare("
    SELECT l.*, 
           c.customer_company_name,
           u.username AS driver_name
    FROM loads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN users u ON l.assigned_driver_id = u.id
    WHERE l.load_id = ?
");
$stmt->bind_param("i", $loadId);
$stmt->execute();
$load = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$load) {
    die("Load not found.");
}

// PDF SETUP ----------------------------------
$pdf = new TCPDF();
$pdf->SetCreator('Team Transport System');
$pdf->SetAuthor('Team Transport');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 20);

$pdf->Cell(0, 15, strtoupper($docType) . " - Load #$loadId", 0, 1, 'C');

$pdf->Ln(4);
$pdf->SetFont('helvetica', '', 12);

$html = "";


// =====================================================
// HTML TEMPLATE BUILDER
// =====================================================
switch ($docType) {

    case 'pod':
        $html = "
        <h2>Proof of Delivery</h2>
        <p><strong>Customer:</strong> {$load['customer_company_name']}</p>
        <p><strong>Delivered:</strong> " . date('Y-m-d H:i:s') . "</p>
        <h3>Delivery Location</h3>
        <p>{$load['delivery_address']}<br>{$load['delivery_city']}</p>
        <h3>Driver</h3>
        <p>{$load['driver_name']}</p>
        <hr>
        <p>Signature: ___________________________</p>
        ";
        break;

    case 'bol':
        $html = "
        <h2>Bill of Lading (BOL)</h2>
        <h3>Shipper Information</h3>
        <p>{$load['pickup_address']}<br>{$load['pickup_city']}</p>
        <h3>Consignee Information</h3>
        <p>{$load['delivery_address']}<br>{$load['delivery_city']}</p>
        <h3>Freight Details</h3>
        <p><strong>Weight:</strong> {$load['total_weight_kg']} kg</p>
        <p><strong>Description:</strong> {$load['description']}</p>
        <hr>
        <p>Driver Signature: ___________________________</p>
        <p>Date: " . date('Y-m-d') . "</p>
        ";
        break;

    case 'summary':
        $html = "
        <h2>Load Summary</h2>
        <p><strong>Reference #:</strong> {$load['reference_number']}</p>
        <p><strong>Status:</strong> {$load['load_status']}</p>
        <h3>Customer</h3>
        <p>{$load['customer_company_name']}</p>
        <h3>Pickup</h3>
        <p>{$load['pickup_address']}<br>{$load['pickup_city']}<br>{$load['pickup_date']}</p>
        <h3>Delivery</h3>
        <p>{$load['delivery_address']}<br>{$load['delivery_city']}<br>{$load['delivery_date']}</p>
        <h3>Driver</h3>
        <p>{$load['driver_name']}</p>
        <h3>Rate</h3>
        <p>{$load['rate_amount']} {$load['rate_currency']}</p>
        ";
        break;
}

$pdf->writeHTML($html, true, false, true, false, '');


// =====================================================
// SAVE PDF TO SERVER
// =====================================================

$folder = __DIR__ . "/../uploads/$docType/";

if (!is_dir($folder)) {
    mkdir($folder, 0777, true);
}

$filename = strtoupper($docType) . "_LOAD{$loadId}_" . time() . ".pdf";
$filePath = $folder . $filename;

$webPath = "uploads/$docType/" . $filename;

// SAVE FILE
$pdf->Output($filePath, 'F');


// =====================================================
// INSERT INTO load_documents
// =====================================================
// MAP DOCUMENT TYPE TO ENUM VALUES
switch ($docType) {
    case 'pod':
        $docType = 'pod';
        break;

    case 'bol':
        $docType = 'bol';
        break;

    case 'summary':    // summary not allowed → map to 'other'
    default:
        $docType = 'other';
        break;
}

$stmt = $conn->prepare("
    INSERT INTO load_documents 
    (load_id, uploaded_by_user_id, document_type, file_path, file_extension)
    VALUES (?, ?, ?, ?, 'pdf')
");
$stmt->bind_param("iiss", $loadId, $_SESSION['user_id'], $docType, $webPath);

$stmt->bind_param("iiss", $loadId, $_SESSION['user_id'], $docType, $webPath);
$stmt->execute();
$stmt->close();

$conn->close();


// =====================================================
// REDIRECT BACK TO LOAD VIEW
// =====================================================
header("Location: ../views/loads/load_view.php?id=$loadId");
exit;
