<?php

function createLoad(mysqli $conn, array $data, int $createdBy, array $files)
{
    $stmt = $conn->prepare("
        INSERT INTO loads (
            customer_id, created_by_user_id, assigned_driver_id,
            reference_number, description,
            pickup_contact_name, pickup_address, pickup_city, pickup_postal_code, pickup_date,
            delivery_contact_name, delivery_address, delivery_city, delivery_postal_code, delivery_date,
            total_weight_kg, rate_amount, rate_currency,
            load_status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $assignedDriver = $data['assigned_driver_id'] ?: null;

    $stmt->bind_param(
        "iiissssssssssssddsss",
        $data['customer_id'],
        $createdBy,
        $assignedDriver,
        $data['reference_number'],
        $data['description'],
        $data['pickup_contact_name'],
        $data['pickup_address'],
        $data['pickup_city'],
        $data['pickup_postal_code'],
        $data['pickup_date'],
        $data['delivery_contact_name'],
        $data['delivery_address'],
        $data['delivery_city'],
        $data['delivery_postal_code'],
        $data['delivery_date'],
        $data['total_weight_kg'],
        $data['rate_amount'],
        $data['rate_currency'],
        $data['load_status'],
        $data['notes']
    );

    if (!$stmt->execute()) {
        throw new Exception("DB Error: " . $stmt->error);
    }

    $loadId = $stmt->insert_id;

    handleLoadDocuments($conn, $loadId, $files);

    return $loadId;
}
function updateLoad(mysqli $conn, int $loadId, array $data, array $files)
{
    $assignedDriver = $data['assigned_driver_id'] ?: null;

    $stmt = $conn->prepare("
        UPDATE loads SET
            customer_id = ?, 
            assigned_driver_id = ?,
            reference_number = ?, 
            description = ?,
            pickup_contact_name = ?, pickup_address = ?, pickup_city = ?, pickup_postal_code = ?, pickup_date = ?,
            delivery_contact_name = ?, delivery_address = ?, delivery_city = ?, delivery_postal_code = ?, delivery_date = ?,
            total_weight_kg = ?, rate_amount = ?, rate_currency = ?,
            load_status = ?, notes = ?
        WHERE load_id = ?
    ");

    $stmt->bind_param(
         "iiissssssssssssddsssi",
        $data['customer_id'],
        $assignedDriver,
        $data['reference_number'],
        $data['description'],
        $data['pickup_contact_name'],
        $data['pickup_address'],
        $data['pickup_city'],
        $data['pickup_postal_code'],
        $data['pickup_date'],
        $data['delivery_contact_name'],
        $data['delivery_address'],
        $data['delivery_city'],
        $data['delivery_postal_code'],
        $data['delivery_date'],
        $data['total_weight_kg'],
        $data['rate_amount'],
        $data['rate_currency'],
        $data['load_status'],
        $data['notes'],
        $loadId
    );

    if (!$stmt->execute()) {
        throw new Exception("Update error: " . $stmt->error);
    }

    // NEW UPLOADS
    handleLoadDocuments($conn, $loadId, $files);

    return true;
}


function handleLoadDocuments(mysqli $conn, int $loadId, array $files)
{
    if (empty($files['documents']['name'][0])) return;

    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    foreach ($files['documents']['name'] as $i => $filename) {
        if (!$files['documents']['tmp_name'][$i]) continue;

        $newName = time() . "_" . basename($filename);
        $path = $uploadDir . $newName;
        move_uploaded_file($files['documents']['tmp_name'][$i], $path);

        $stmt = $conn->prepare("
            INSERT INTO load_documents (load_id, file_name, original_name)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $loadId, $newName, $filename);
        $stmt->execute();
    }
}
