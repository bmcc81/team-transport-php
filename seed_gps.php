<?php
// seed_gps.php
// Generate random-ish Montreal movement for vehicles 17–31

$dsn = 'mysql:host=127.0.0.1;dbname=team_transport;charset=utf8mb4';
$user = 'teamuser';      // adjust to your DB user
$pass = 'TEAM1234';      // adjust to your DB password

$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

// Your depot coordinates (Vaudreuil)
$baseLat = 45.3996718;
$baseLng = -74.0376387;

// Vehicle IDs we know from your DB
$vehicleIds = range(17, 31); // V101..V115

// Fixed start time for telemetry (option B)
$startTime = new DateTime('2025-02-01 09:00:00');

$pointsPerVehicle = 20;

// Prepare insert statement
$stmt = $pdo->prepare("
    INSERT INTO vehicle_gps (vehicle_id, latitude, longitude, speed_kmh, heading, recorded_at)
    VALUES (:vehicle_id, :lat, :lng, :speed, :heading, :recorded_at)
");

foreach ($vehicleIds as $index => $vehicleId) {

    echo "Seeding vehicle ID $vehicleId\n";
    
    // Small per-vehicle offset so they don't all stack on each other
    $vehicleLatOffset = 0.002 * $index;
    $vehicleLngOffset = 0.002 * $index;

    $time = clone $startTime;

    for ($i = 0; $i < $pointsPerVehicle; $i++) {
        // Time-based drift, gives movement from depot
        $tLatOffset = 0.0003 * $i;
        $tLngOffset = -0.00025 * $i;

        $lat = $baseLat + $vehicleLatOffset + $tLatOffset;
        $lng = $baseLng + $vehicleLngOffset + $tLngOffset;

        // Speed between ~35 and 80 km/h
        $speed = 35 + (5 * (($i + $index) % 10));

        // Heading between 0–359, stepping so it kind of "turns"
        $heading = (30 * ($i + $index)) % 360;

        $stmt->execute([
            ':vehicle_id'   => $vehicleId,
            ':lat'          => $lat,
            ':lng'          => $lng,
            ':speed'        => $speed,
            ':heading'      => $heading,
            ':recorded_at'  => $time->format('Y-m-d H:i:s'),
        ]);

        // Next point = +1 minute
        $time->modify('+1 minute');
    }
}

echo "Inserted telemetry for vehicles 17–31 ({$pointsPerVehicle} points each).\n";
