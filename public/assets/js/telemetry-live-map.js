// telemetry-live-map.js

let map;
const vehicleMarkers = {};
const vehicleTrails = {};
const trailPolylines = {};

function initMap() {
    map = L.map("live-map").setView([45.5, -73.57], 11);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
    }).addTo(map);
}

// -----------------------------
//  UTILITIES
// -----------------------------

function getSpeedColor(speed) {
    if (speed < 5) return "#6c757d";   // stopped
    if (speed < 40) return "#0d6efd";  // slow
    if (speed < 80) return "#ffc107";  // medium
    return "#dc3545";                  // fast
}

function fitAllVehicles() {
    const ids = Object.keys(vehicleMarkers);
    if (ids.length === 0) return;

    const group = L.featureGroup(ids.map(id => vehicleMarkers[id]));
    map.fitBounds(group.getBounds().pad(0.2));
}

function updateVehicleTable(id, latlng, speed, timestamp) {
    const rowId = "vehicle-row-" + id;
    let row = document.getElementById(rowId);

    if (!row) {
        const tbody = document.getElementById("active-vehicles-body");
        row = document.createElement("tr");
        row.id = rowId;
        row.innerHTML = `
            <td>${id}</td>
            <td class="veh-pos"></td>
            <td class="veh-speed"></td>
            <td class="veh-time"></td>
        `;
        tbody.appendChild(row);
    }

    row.querySelector(".veh-pos").textContent =
        latlng[0].toFixed(5) + ", " + latlng[1].toFixed(5);
    row.querySelector(".veh-speed").textContent = `${speed} km/h`;
    row.querySelector(".veh-time").textContent = timestamp;

    const lbl = document.getElementById("last-update-label");
    if (lbl) {
        lbl.textContent = "Last update: " + new Date().toLocaleTimeString();
    }
}

// -----------------------------
//  TELEMETRY UPDATE HANDLER
// -----------------------------

function handleTelemetryUpdate(data) {
    const vehicleId = data.vehicle_id;
    const lat = parseFloat(data.latitude);
    const lng = parseFloat(data.longitude);
    const speed = parseFloat(data.speed);
    const timestamp = data.timestamp;

    const latlng = [lat, lng];

    // 1) Marker create / update
    if (!vehicleMarkers[vehicleId]) {
        const marker = L.circleMarker(latlng, {
            radius: 8,
            weight: 1,
            color: "#000",
            fillColor: getSpeedColor(speed),
            fillOpacity: 0.9,
        }).addTo(map);

        vehicleMarkers[vehicleId] = marker;
    } else {
        const marker = vehicleMarkers[vehicleId];
        marker.setLatLng(latlng);
        marker.setStyle({ fillColor: getSpeedColor(speed) });
    }

    // 2) Trail history
    if (!vehicleTrails[vehicleId]) vehicleTrails[vehicleId] = [];
    vehicleTrails[vehicleId].push(latlng);

    if (vehicleTrails[vehicleId].length > 50) {
        vehicleTrails[vehicleId].shift();
    }

    // 3) Polyline
    if (!trailPolylines[vehicleId]) {
        trailPolylines[vehicleId] = L.polyline(vehicleTrails[vehicleId], {
            color: getSpeedColor(speed),
            weight: 3,
            opacity: 0.6,
        }).addTo(map);
    } else {
        const poly = trailPolylines[vehicleId];
        poly.setLatLngs(vehicleTrails[vehicleId]);
        poly.setStyle({ color: getSpeedColor(speed) });
    }

    // 4) Sidebar table update
    updateVehicleTable(vehicleId, latlng, speed, timestamp);
}

// -----------------------------
//  WEBSOCKET CLIENT
// -----------------------------

let ws;

function setPill(text, cls) {
    const pill = document.getElementById("ws-status-pill");
    if (!pill) return;
    pill.textContent = text;
    pill.className = "badge connection-pill " + cls;
}

function connectWS() {
    const proto = window.location.protocol === "https:" ? "wss://" : "ws://";
    const wsUrl = proto + window.location.host + "/ws";

    setPill("WS: Connecting…", "text-bg-secondary");

    ws = new WebSocket(wsUrl);

    ws.onopen = () => {
        setPill("WS: Connected", "text-bg-success");
        console.log("[WS] Connected:", wsUrl);
    };

    ws.onerror = (err) => {
        console.error("[WS] Error:", err);
        setPill("WS: Error", "text-bg-danger");
    };

    ws.onclose = () => {
        console.warn("[WS] Closed. Reconnecting in 2s…");
        setPill("WS: Reconnecting…", "text-bg-warning");
        setTimeout(connectWS, 2000);
    };

    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            handleTelemetryUpdate(data);
        } catch (e) {
            console.error("Invalid WS payload:", e, event.data);
        }
    };
}

// -----------------------------
//  BOOTSTRAP
// -----------------------------

document.addEventListener("DOMContentLoaded", () => {
    initMap();

    const fitBtn = document.getElementById("btn-fit-all");
    if (fitBtn) {
        fitBtn.addEventListener("click", fitAllVehicles);
    }

    connectWS();
});
