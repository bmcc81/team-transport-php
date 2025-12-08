// Leaflet init
const depotLat = 45.3996718;
const depotLng = -74.0376387;

const map = L.map('map').setView([depotLat, depotLng], 12);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
}).addTo(map);

// Marker storage
let markers = {};

// Auto-focus if coming from vehicle profile
const focusId = window.__FOCUS_ID__;

// -----------------------------
// FETCH LATEST TELEMETRY
// -----------------------------
async function loadTelemetry() {
    const res = await fetch('/api/telemetry/latest');
    const vehicles = await res.json();

    updateVehicleList(vehicles);
    updateMapMarkers(vehicles);
}

// -----------------------------
// UPDATE SIDEBAR LIST
// -----------------------------
function updateVehicleList(vehicles) {
    const list = document.getElementById("vehicle-list");
    list.innerHTML = "";

    vehicles.forEach(v => {
        const isOnline = v.latitude && v.longitude;

        const item = document.createElement("div");
        item.className = "vehicle-item" + (focusId == v.id ? " active" : "");
        item.dataset.id = v.id;

        item.innerHTML = `
            <strong>#${v.vehicle_number}</strong><br>
            ${v.make ?? ""} ${v.model ?? ""}<br>

            <small class="text-muted">
                <span class="status-dot ${isOnline ? "status-online" : "status-offline"}"></span>
                ${isOnline ? `Updated ${v.recorded_at}` : "No Signal"}
            </small>
        `;

        item.onclick = () => {
            if (markers[v.id]) {
                map.setView(markers[v.id].getLatLng(), 16);
                markers[v.id].openPopup();
            }
        };

        list.appendChild(item);
    });
}

// -----------------------------
// UPDATE MAP MARKERS
// -----------------------------
function updateMapMarkers(vehicles) {
    vehicles.forEach(v => {
        if (!v.latitude || !v.longitude) return;

        const latlng = [v.latitude, v.longitude];

        const popup = `
            <strong>#${v.vehicle_number}</strong><br>
            ${v.make} ${v.model}<br>
            Speed: ${v.speed_kmh ?? "0"} km/h<br>
            Heading: ${v.heading ?? "N/A"}<br>
            Updated: ${v.recorded_at}
        `;

        if (markers[v.id]) {
            markers[v.id].setLatLng(latlng).setPopupContent(popup);
        } else {
            markers[v.id] = L.marker(latlng).addTo(map).bindPopup(popup);

            if (focusId == v.id) {
                map.setView(latlng, 16);
                markers[v.id].openPopup();
            }
        }
    });
}

// First load + 10-second refresh
loadTelemetry();
setInterval(loadTelemetry, 10000);
