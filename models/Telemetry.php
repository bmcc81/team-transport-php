<?php
$vehicleId = $_GET['vehicle_id'];

$stmt = $pdo->prepare("
    SELECT latitude, longitude, event_time
    FROM telemetry
    WHERE vehicle_id = ?
    ORDER BY event_time ASC
");
$stmt->execute([$vehicleId]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
