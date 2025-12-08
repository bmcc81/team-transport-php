#!/usr/bin/env python3
import requests, random, time, json, datetime, threading
import urllib3

# Disable certificate warnings (mkcert self-signed)
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

URL = "https://teamtransport.local/api/telemetry/ingest"

# Number of vehicles
VEHICLE_COUNT = 10

# Initial Montréal-ish coordinates
BASE_LAT = 45.5000
BASE_LNG = -73.5700


def simulate_vehicle(vehicle_id):
    """Simulates a single vehicle moving indefinitely."""
    lat = BASE_LAT + random.uniform(-0.02, 0.02)
    lng = BASE_LNG + random.uniform(-0.02, 0.02)

    print(f"[START] Vehicle {vehicle_id} simulation launched.")

    while True:
        # Random drifting motion
        lat += random.uniform(-0.00025, 0.00025)
        lng += random.uniform(-0.00025, 0.00025)

        payload = {
            "vehicle_id": vehicle_id,
            "latitude": lat,
            "longitude": lng,
            "speed": random.randint(0, 110),
            "heading": random.randint(0, 359),
            "timestamp": datetime.datetime.now(datetime.UTC).strftime("%Y-%m-%d %H:%M:%S")
        }

        try:
            # Higher timeout fixes Windows PHP concurrency stalls
            response = requests.post(URL, json=payload, timeout=5, verify=False)

            if response.status_code == 200:
                print(f"[V{vehicle_id}] Sent:", payload)
            else:
                print(f"[V{vehicle_id}] ERROR {response.status_code}: {response.text}")

        except Exception as e:
            print(f"[V{vehicle_id}] ERROR: {e}")

        # Avoid hammering too fast
        time.sleep(random.uniform(1.2, 2.5))


def start_fleet():
    """Launch fleet in parallel threads."""
    threads = []

    for vid in range(1, VEHICLE_COUNT + 1):
        t = threading.Thread(target=simulate_vehicle, args=(vid,), daemon=True)
        t.start()
        threads.append(t)

    print("\n🔥 Fleet simulation started: 10 vehicles running.\n")

    # Keep main thread alive
    while True:
        time.sleep(5)


if __name__ == "__main__":
    start_fleet()
