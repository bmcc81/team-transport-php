<?php

$url = "https://teamtransport.local/api/telemetry/ingest";
$vehicleId = $argv[1] ?? 1;

$lat = 45.5000;
$lng = -73.5700;

echo "Starting PHP telemetry simulator for vehicle $vehicleId\n";

while (true) {

    // Random walk
    $lat += (mt_rand(-20, 20) / 200000.0);
    $lng += (mt_rand(-20, 20) / 200000.0);

    $data = [
        "vehicle_id" => (int)$vehicleId,
        "latitude"    => $lat,
        "longitude"   => $lng,
        "speed"       => mt_rand(10, 120),
        "heading"     => mt_rand(0, 359),
        "timestamp"   => gmdate("Y-m-d H:i:s")
    ];

    echo "POST → " . json_encode($data) . PHP_EOL;

    $opts = [
        "http" => [
            "method"  => "POST",
            "header"  => "Content-Type: application/json",
            "content" => json_encode($data)
        ]
    ];

    file_get_contents($url, false, stream_context_create($opts));

    sleep(2);
}
