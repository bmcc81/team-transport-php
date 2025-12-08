#!/usr/bin/env python3
import asyncio
import json
import threading
from http.server import HTTPServer, BaseHTTPRequestHandler

import websockets

# -----------------------
# CONFIG
# -----------------------

WS_HOST = "0.0.0.0"
WS_PORT = 8081

HTTP_HOST = "127.0.0.1"
HTTP_PORT = 8082

# -----------------------
# GLOBAL STATE
# -----------------------

loop = None          # asyncio event loop (set in main)
queue = None         # asyncio.Queue() for incoming telemetry
connected_clients = set()  # set of websockets
vehicles_state = {}        # vehicle_id -> last packet
vehicles_trails = {}       # vehicle_id -> list of [lat, lng]


# -----------------------
# HTTP INGEST HANDLER
# -----------------------

class IngestHandler(BaseHTTPRequestHandler):
    def log_message(self, format, *args):
        # Silence default HTTPServer logging
        return

    def do_POST(self):
        if self.path != "/ingest":
            self.send_error(404, "Not found")
            return

        content_length = int(self.headers.get("Content-Length", 0))
        raw = self.rfile.read(content_length)

        try:
            data = json.loads(raw.decode("utf-8"))
        except Exception:
            self.send_error(400, "Invalid JSON")
            return

        required = ["vehicle_id", "latitude", "longitude", "speed", "heading", "timestamp"]
        if not all(k in data for k in required):
            self.send_error(400, "Missing fields")
            return

        global loop, queue
        # enqueue into asyncio loop for broadcast
        loop.call_soon_threadsafe(queue.put_nowait, data)

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.end_headers()
        self.wfile.write(b'{"status":"ok"}')


def start_http_server():
    httpd = HTTPServer((HTTP_HOST, HTTP_PORT), IngestHandler)
    print(f"🚀 Starting Telemetry ingest HTTP listener on http://{HTTP_HOST}:{HTTP_PORT}/ingest")
    httpd.serve_forever()


# -----------------------
# WEBSOCKET SERVER
# -----------------------

async def ws_handler(websocket):
    """Handle a WebSocket client connection."""
    connected_clients.add(websocket)
    print(f"🔌 WS client connected ({len(connected_clients)} total)")

    try:
        # We don't expect any messages from clients right now,
        # but keep the connection open by consuming.
        async for _ in websocket:
            pass
    except Exception as e:
        print(f"⚠ WS error: {e}")
    finally:
        connected_clients.discard(websocket)
        print(f"🔌 WS client disconnected ({len(connected_clients)} remaining)")


async def broadcast_consumer():
    """Consume telemetry messages from the queue and broadcast to all clients."""
    while True:
        data = await queue.get()

        try:
            vid = int(data["vehicle_id"])
            lat = float(data["latitude"])
            lng = float(data["longitude"])
            speed = float(data["speed"])
            heading = float(data["heading"])
            ts = str(data["timestamp"])
        except Exception as e:
            print(f"⚠ Invalid telemetry packet skipped: {e} | data={data}")
            continue

        # Update in-memory state
        vehicles_state[vid] = {
            "vehicle_id": vid,
            "latitude": lat,
            "longitude": lng,
            "speed": speed,
            "heading": heading,
            "timestamp": ts,
        }

        if vid not in vehicles_trails:
            vehicles_trails[vid] = []
        vehicles_trails[vid].append([lat, lng])

        # Limit trail length
        if len(vehicles_trails[vid]) > 50:
            vehicles_trails[vid].pop(0)

        # Build outbound message (flat object; matches frontend handler)
        outbound = {
            "vehicle_id": vid,
            "latitude": lat,
            "longitude": lng,
            "speed": speed,
            "heading": heading,
            "timestamp": ts,
        }

        if connected_clients:
            msg = json.dumps(outbound)
            # Broadcast to all clients; ignore any send errors
            await asyncio.gather(
                *[safe_send(ws, msg) for ws in list(connected_clients)],
                return_exceptions=True
            )


async def safe_send(ws, message: str):
    try:
        await ws.send(message)
    except Exception as e:
        print(f"⚠ Failed to send to client: {e}")


# -----------------------
# MAIN
# -----------------------

def main():
    global loop, queue

    loop = asyncio.new_event_loop()
    asyncio.set_event_loop(loop)
    queue = asyncio.Queue()

    # Start HTTP ingest server in a background thread
    http_thread = threading.Thread(target=start_http_server, daemon=True)
    http_thread.start()

    # Start WebSocket server
    print(f"🚀 Starting Telemetry WebSocket server on ws://{WS_HOST}:{WS_PORT}")
    ws_server = websockets.serve(ws_handler, WS_HOST, WS_PORT)

    loop.run_until_complete(ws_server)
    loop.create_task(broadcast_consumer())

    print("✅ Telemetry hub is up: WS + HTTP ingest running.")
    loop.run_forever()


if __name__ == "__main__":
    main()
