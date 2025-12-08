// -----------------------------
//  LIVE MAP INITIALIZATION
// -----------------------------

let map = L.map("live-map").setView([45.50, -73.57], 11);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
}).addTo(map);

// Store all marker + trail objects
const vehicleMarkers = {};
const vehicleTrails = {};
const trailPolylines = {};


// -----------------------------
//  UTILITY FUNCTIONS
// -----------------------------

function getSpeedColor(speed) {
    if (speed < 5) return "#6c757d";       // Gray - stopped
    if (speed < 40) return "#0d6efd";      // Blue
    if (speed < 80) return "#ffc107";      // Yellow
    return "#dc3545";                      // Red
}

function fitAllVehicles() {
    const ids = Object.keys(vehicleMarkers);
    if (ids.length === 0) return;

    const group = L.featureGroup(ids.map(id => vehicleMarkers[id]));
    map.fitBounds(group.getBounds().pad(0.2));
}


// -----------------------------
//  SIDEBAR TABLE UPDATER
// -----------------------------

function updateVehicleTable(id, latlng, speed, timestamp) {
    const rowId = "vehicle-row-" + id;
    let row = document.getElementById(rowId);

    if (!row) {
        const tableBody = document.getElementById("active-vehicles-body");
        row = document.createElement("tr");
        row.id = rowId;

        row.innerHTML = `
            <td>${id}</td>
            <td class="veh-pos">${latlng[0].toFixed(5)}, ${latlng[1].toFixed(5)}</td>
            <td class="veh-speed">${speed} km/h</td>
            <td class="veh-time">${timestamp}</td>
        `;

        tableBody.appendChild(row);
    } else {
        row.querySelector(".veh-pos").textContent = `${latlng[0].toFixed(5)}, ${latlng[1].toFixed(5)}`;
        row.querySelector(".veh-speed").textContent = `${speed} km/h`;
        row.querySelector(".veh-time").textContent = timestamp;
    }
}


// -----------------------------
//  TELEMETRY UPDATE HANDLER
// -----------------------------

function handleTelemetryUpdate(data) {
    const { vehicle_id, latitude, longitude, speed, timestamp } = data;

    const latlng = [latitude, longitude];

    // 1️⃣ Marker creation/update
    if (!vehicleMarkers[vehicle_id]) {
        const marker = L.circleMarker(latlng, {
            radius: 8,
            weight: 1,
            color: "#000",
            fillColor: getSpeedColor(speed),
            fillOpacity: 0.9
        }).addTo(map);

        vehicleMarkers[vehicle_id] = marker;
    } else {
        const marker = vehicleMarkers[vehicle_id];
        marker.setLatLng(latlng);
        marker.setStyle({ fillColor: getSpeedColor(speed) });
    }

    // 2️⃣ Trail history
    if (!vehicleTrails[vehicle_id]) vehicleTrails[vehicle_id] = [];
    vehicleTrails[vehicle_id].push(latlng);

    // Maximum trail length
    if (vehicleTrails[vehicle_id].length > 50) {
        vehicleTrails[vehicle_id].shift();
    }

    // 3️⃣ Draw or update polyline
    if (!trailPolylines[vehicle_id]) {
        trailPolylines[vehicle_id] = L.polyline(vehicleTrails[vehicle_id], {
            color: getSpeedColor(speed),
            weight: 3,
            opacity: 0.6,
        }).addTo(map);
    } else {
        const poly = trailPolylines[vehicle_id];
        poly.setLatLngs(vehicleTrails[vehicle_id]);
        poly.setStyle({ color: getSpeedColor(speed) });
    }

    // 4️⃣ Update sidebar
    updateVehicleTable(vehicle_id, latlng, speed, timestamp);
}


// -----------------------------
//  WEBSOCKET CONNECTION
// -----------------------------

let ws;

// Automatically choose ws:// or wss:// depending on your page
function resolveWsUrl() {
    const host = window.location.hostname;

    if (location.protocol === "https:") {
        return `wss://${host}:8081`;
    }
    return `ws://${host}:8081`;
}

function connectWS() {
    const url = resolveWsUrl();
    ws = new WebSocket(url);

    const pill = document.getElementById("ws-status-pill");

    function setPill(text, cls) {
        pill.textContent = text;
        pill.className = "badge connection-pill " + cls;
    }

    ws.onopen = () => {
        setPill("WS: Connected", "text-bg-success");
    };

    ws.onerror = () => {
        setPill("WS: Error", "text-bg-danger");
    };

    ws.onclose = () => {
        setPill("WS: Reconnecting…", "text-bg-warning");

        // Auto-reconnect after 2 seconds
        setTimeout(connectWS, 2000);
    };

    ws.onmessage = (event) => {
        const data = JSON.parse(event.data);
        handleTelemetryUpdate(data);
    };

    // Initial state:
    setPill("WS: Connecting…", "text-bg-secondary");
}

connectWS();

