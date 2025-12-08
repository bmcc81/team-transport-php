#!/usr/bin/env python3
import requests, random, time, json, datetime

URL = "https://teamtransport.local/api/telemetry/ingest"
VEHICLE_ID = 1

lat = 45.5000
lng = -73.5700

print(f"Telemetry simulator started for vehicle {VEHICLE_ID}")

while True:
    lat += random.uniform(-0.0001, 0.0001)
    lng += random.uniform(-0.0001, 0.0001)

    payload = {
        "vehicle_id": VEHICLE_ID,
        "latitude": lat,
        "longitude": lng,
        "speed": random.randint(20, 110),
        "heading": random.randint(0, 359),
        "timestamp": datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
    }

    print("Sending → ", payload)

    requests.post(URL, json=payload, verify=False)

    time.sleep(2)
