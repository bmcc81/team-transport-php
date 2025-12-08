#!/usr/bin/env bash

URL="https://teamtransport.local/api/telemetry/ingest"
VEHICLE_ID=${1:-1}

lat=45.5000
lng=-73.5700

echo "Starting telemetry simulator for vehicle ID $VEHICLE_ID"
echo "POST → $URL"
echo

while true; do
    # Simulate random drift
    lat=$(echo "$lat + (RANDOM - 16384)/2000000" | bc -l)
    lng=$(echo "$lng + (RANDOM - 16384)/2000000" | bc -l)

    timestamp=$(date -u +"%Y-%m-%d %H:%M:%S")

    json=$(cat <<EOF
{
  "vehicle_id": $VEHICLE_ID,
  "latitude": $lat,
  "longitude": $lng,
  "speed": $(shuf -i 20-110 -n 1),
  "heading": $(shuf -i 0-359 -n 1),
  "timestamp": "$timestamp"
}
EOF
)

    echo "Sending → $json"

    curl -s -X POST "$URL" \
        -H "Content-Type: application/json" \
        -d "$json" >/dev/null

    sleep 2
done
