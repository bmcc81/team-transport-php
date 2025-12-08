<?php
require_once '../bootstrap.php';

$data = json_decode(file_get_contents('php://input'), true);

$vehicleId = $data['vehicle_id'];
$lat = $data['lat'];
$lng = $data['lng'];
$speed = $data['speed'] ?? 0;
$heading = $data['heading'] ?? 0;

$pdo = Database::pdo();

$stmt = $pdo->prepare("
    INSERT INTO telemetry (vehicle_id, latitude, longitude, speed, heading)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([$vehicleId, $lat, $lng, $speed, $heading]);

// Update cached vehicle coords
$pdo->prepare("
    UPDATE vehicles
    SET last_lat = ?, last_lng = ?, last_telemetry_at = NOW()
    WHERE id = ?
")->execute([$lat, $lng, $vehicleId]);

// Broadcast to WebSocket
WebSocketBroadcaster::send(json_encode([
    'vehicle_id' => $vehicleId,
    'lat' => $lat,
    'lng' => $lng,
    'speed' => $speed,
    'heading' => $heading
]));

echo json_encode(['status' => 'ok']);
