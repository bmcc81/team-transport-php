#!/usr/bin/env python3
import requests, random, time, json, datetime, threading

# IMPORTANT: This MUST match the ingest server output:
# "Starting Telemetry ingest HTTP listener on http://127.0.0.1:8082/ingest"
URL = "http://127.0.0.1:8082/ingest"

VEHICLE_COUNT = 10

# Base Montréal-ish coordinates
BASE_LAT = 45.5000
BASE_LNG = -73.5700


def simulate_vehicle(vehicle_id):
    """Simulates one moving vehicle indefinitely."""
    lat = BASE_LAT + random.uniform(-0.02, 0.02)
    lng = BASE_LNG + random.uniform(-0.02, 0.02)

    print(f"[START] Vehicle {vehicle_id} simulation launched.")

    while True:
        # Random motion drift
        lat += random.uniform(-0.00025, 0.00025)
        lng += random.uniform(-0.00025, 0.00025)

        payload = {
            "vehicle_id": vehicle_id,
            "latitude": lat,
            "longitude": lng,
            "speed": random.randint(0, 110),
            "heading": random.randint(0, 359),
            "timestamp": datetime.datetime.utcnow().strftime("%Y-%m-%d %H:%M:%S")
        }

        try:
            requests.post(URL, json=payload, timeout=1)
            print(f"[V{vehicle_id}] Sent: {payload}")
        except Exception as e:
            print(f"[V{vehicle_id}] ERROR: {e}")

        time.sleep(random.uniform(1.2, 2.5))


def start_fleet():
    """Launch all vehicles in parallel."""
    threads = []

    for vid in range(1, VEHICLE_COUNT + 1):
        t = threading.Thread(target=simulate_vehicle, args=(vid,), daemon=True)
        threads.append(t)
        t.start()

    print("\n🔥 Fleet simulation started: 10 vehicles running.\n")

    # Prevent script exit
    while True:
        time.sleep(5)


if __name__ == "__main__":
    start_fleet()
