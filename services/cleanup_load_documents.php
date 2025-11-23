<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Only allow ADMINs
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

require_once __DIR__ . "/config.php";

echo "<h2>Load Documents Cleanup Script</h2>";
echo "<pre>";

$result = $conn->query("SELECT document_id, file_path, file_extension FROM load_documents ORDER BY document_id ASC");

$fixed = 0;
$deleted = 0;

while ($row = $result->fetch_assoc()) {
    $id  = $row['document_id'];
    $path = $row['file_path'];
    $originalPath = $path;

    // Skip if correct
    if (preg_match('#^uploads/(pod|bol|summary)/#i', $path)) {
        continue;
    }

    echo "Checking ID $id: $path\n";

    // 1. Remove local Windows base path
    $path = preg_replace('#^[A-Za-z]:\\\\xampp\\\\htdocs\\\\TeamTransport\\\\#i', '', $path);
    $path = str_replace('C:\xampp\htdocs\TeamTransport\\', '', $path);
    $path = str_replace('C:/xampp/htdocs/TeamTransport/', '', $path);

    // 2. Remove ../../../ or ../../services/ etc.
    $path = str_replace('../', '', $path);
    $path = str_replace('./', '', $path);
    $path = str_replace('services/../../', '', $path);

    // 3. Normalize slashes
    $path = str_replace('\\', '/', $path);

    // 4. Remove accidental file://
    $path = preg_replace('#^file://#i', '', $path);

    // 5. Remove absolute filesystem paths
    if (preg_match('#^C:/#i', $path)) {
        $path = '';
    }

    // 6. Verify it starts with uploads/
    if (!preg_match('#^uploads/#i', $path)) {

        // If cannot fix → delete the row
        echo " ❌ Could not fix path → deleting row $id\n";
        $conn->query("DELETE FROM load_documents WHERE document_id = $id");
        $deleted++;
        continue;
    }

    // UPDATE FIXED PATH
    $stmt = $conn->prepare("UPDATE load_documents SET file_path = ? WHERE document_id = ?");
    $stmt->bind_param("si", $path, $id);
    $stmt->execute();

    echo " ✔ Fixed: $originalPath  →  $path\n";
    $fixed++;
}

echo "\n-----------------------------\n";
echo "Cleanup Completed!\n";
echo "Fixed paths:  $fixed\n";
echo "Deleted rows: $deleted\n";
echo "-----------------------------\n";

echo "</pre>";
?>
