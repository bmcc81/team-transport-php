<?php
/** @var array $vehicles (optional, not strictly needed now) */
$pageTitle = "Live Vehicle Map";
require __DIR__ . '/../../layout/header.php';

use App\Database\Database;

// Optional: you *can* still pull vehicles if you want meta info later
$pdo = Database::pdo();

// Accept ?focus={id} to zoom on a specific vehicle
$focusId = isset($_GET['focus']) ? (int)$_GET['focus'] : null;
?>

<!-- Leaflet CSS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>

<style>
    #live-map {
        width: 100%;
        height: 70vh;
        min-height: 480px;
        border-radius: .75rem;
        overflow: hidden;
    }

    .vehicle-popup {
        font-size: 0.85rem;
    }

    .vehicle-badge {
        font-weight: 600;
    }

    .leaflet-control-layers {
        font-size: 0.8rem;
    }

    .map-sidebar-card {
        max-height: 70vh;
        overflow-y: auto;
    }

    .vehicle-row {
        cursor: pointer;
    }

    .vehicle-row:hover {
        background-color: #f8f9fa;
    }

    .status-dot {
        display: inline-block;
        width: 9px;
        height: 9px;
        border-radius: 50%;
        margin-right: 4px;
    }

    .status-available { background-color: #28a745; }
    .status-in_service { background-color: #0d6efd; }
    .status-maintenance { background-color: #ffc107; }
    .status-offline { background-color: #6c757d; }

    .connection-pill {
        font-size: 0.75rem;
    }
</style>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="h4 mb-0">Live Vehicle Map</h2>
                    <small class="text-muted">
                        Real-time positions with GPS trails (simulated via TelemetryService)
                    </small>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span id="ws-status-pill"
                          class="badge text-bg-secondary connection-pill">
                        WS: Connecting…
                    </span>

                    <button id="btn-fit-all" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrows-fullscreen"></i> Fit All
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-xl-9">
                    <div class="card shadow-sm">
                        <div id="live-map"></div>
                    </div>
                </div>

                <div class="col-12 col-xl-3">
                    <div class="card shadow-sm map-sidebar-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">
                                <i class="bi bi-truck-front me-1"></i> Active Vehicles
                            </span>
                            <button id="btn-clear-trails"
                                    class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-backspace"></i> Clear trails
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0 align-middle">
                                    <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Location</th>
                                        <th>Speed</th>
                                        <th>Last update</th>
                                    </tr>
                                    </thead>
                                    <tbody id="vehicle-list-body">
                                    <!-- Filled by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer text-muted small">
                            <span id="last-refresh-text">Waiting for data…</span>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Leaflet JS -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>

<script>
(function () {
    // ---- CONFIG ----
    const API_LATEST_URL = "/api/telemetry/latest";
    const API_HISTORY_URL = "/api/telemetry/history/";
    const WS_URL = "ws://teamtransport.local:8081";

    // UI Behavior
    const MAX_TRAIL_POINTS = 80;
    const ENABLE_CLUSTERING = true;

    // Marker Color Logic
    const SPEED_COLORS = {
        stopped: "#6c757d",
        slow: "#0d6efd",
        normal: "#28a745",
        fast: "#dc3545"
    };

    function colorFromSpeed(speed) {
        if (speed <= 1) return SPEED_COLORS.stopped;
        if (speed <= 30) return SPEED_COLORS.slow;
        if (speed <= 70) return SPEED_COLORS.normal;
        return SPEED_COLORS.fast;
    }

    // -------------------------
    // MAP INITIALIZATION
    // -------------------------
    const map = L.map('live-map', {
        center: [45.5019, -73.5674],
        zoom: 12,
        worldCopyJump: true
    });

    const baseLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Optional clustering
    let clusterGroup = ENABLE_CLUSTERING ? L.markerClusterGroup() : null;
    if (clusterGroup) map.addLayer(clusterGroup);

    // Vehicle State OBJ
    const vehicles = {}; // { [id]: { marker, polyline, trailLatLngs, filters, lastPayload } }

    // DOM
    const vehicleListBody = document.getElementById('vehicle-list-body');
    const wsStatusPill = document.getElementById("ws-status-pill");
    const lastRefreshText = document.getElementById("last-refresh-text");
    const btnFitAll = document.getElementById("btn-fit-all");
    const btnClearTrails = document.getElementById("btn-clear-trails");

    // Filters
    const filters = {
        showOnlyMine: false,
        showMoving: true,
        showStopped: true,
        showOffline: true
    };

    // "My vehicles" (example dispatcher logic)
    const MY_USER_ID = window?.authUserId ?? null;
    const MY_VEHICLE_IDS = window?.assignedVehicleIds ?? [];

    function applyFilters(vehicleId) {
        const state = vehicles[vehicleId];
        if (!state) return false;

        const speed = state.lastPayload.speed;
        const isMoving = speed > 1;

        // Only my vehicles
        if (filters.showOnlyMine && !MY_VEHICLE_IDS.includes(vehicleId)) {
            return false;
        }

        if (!filters.showMoving && isMoving) return false;
        if (!filters.showStopped && !isMoving) return false;

        return true;
    }

    // -------------------------
    // UI: Generate table rows
    // -------------------------
    function updateSidebarRow(id) {
        const v = vehicles[id];
        if (!v) return;

        const p = v.lastPayload;
        const rowId = "vehicle-row-" + id;
        let tr = document.getElementById(rowId);

        const speed = Math.round(p.speed ?? 0);
        const status = speed <= 1 ? "Stopped" : "Moving";

        const html = `
            <td>
                <strong>#${id}</strong><br>
                <small>${status}</small>
            </td>
            <td>
                <small>Lat:</small> ${p.latitude.toFixed(4)}<br>
                <small>Lng:</small> ${p.longitude.toFixed(4)}
            </td>
            <td>${speed} km/h</td>
            <td><small>${p.timestamp.replace(" ", "<br>")}</small></td>
        `;

        if (!tr) {
            tr = document.createElement("tr");
            tr.id = rowId;
            tr.className = "vehicle-row";
            tr.dataset.vehicleId = id;

            tr.addEventListener("click", () => {
                map.setView(v.marker.getLatLng(), 15, { animate: true });
                v.marker.openPopup();
            });

            vehicleListBody.appendChild(tr);
        }

        tr.innerHTML = html;
        tr.style.display = applyFilters(id) ? "" : "none";
    }

    // -------------------------
    // Update or create marker
    // -------------------------
    function upsertMarker(p, isHistory = false) {
        const id = p.vehicle_id;

        let v = vehicles[id];
        const latLng = [p.latitude, p.longitude];

        if (!v) {
            const marker = L.circleMarker(latLng, {
                radius: 8,
                stroke: false,
                fillColor: colorFromSpeed(p.speed),
                fillOpacity: 0.9
            });

            if (clusterGroup) clusterGroup.addLayer(marker);
            else marker.addTo(map);

            const polyline = L.polyline([], {
                color: "#0d6efd",
                weight: 2,
                opacity: 0.7
            }).addTo(map);

            vehicles[id] = v = {
                marker,
                polyline,
                trailLatLngs: [],
                lastPayload: p
            };
        }

        v.marker.setLatLng(latLng);
        v.marker.setStyle({ fillColor: colorFromSpeed(p.speed) });

        v.marker.bindPopup(`
            <strong>Vehicle #${id}</strong><br>
            Speed: ${p.speed} km/h<br>
            Heading: ${p.heading}°<br>
            <small>${p.timestamp}</small>
        `);

        // Trail logic
        if (!isHistory) {
            v.trailLatLngs.push(latLng);
            if (v.trailLatLngs.length > MAX_TRAIL_POINTS)
                v.trailLatLngs.shift();

            v.polyline.setLatLngs(v.trailLatLngs);
        }

        v.lastPayload = p;

        updateSidebarRow(id);
    }

    // -------------------------
    // Load initial data
    // -------------------------
    async function loadInitial() {
        try {
            const res = await fetch(API_LATEST_URL);
            const list = await res.json();

            list.forEach(p => upsertMarker(p, true));

            lastRefreshText.textContent = "Seeded initial GPS positions.";

        } catch (err) {
            console.error("Initial load failed", err);
        }
    }

    // -------------------------
    // Filter UI
    // -------------------------
    function createFilterControls() {
        const filterBox = document.createElement("div");
        filterBox.className = "mb-3";

        filterBox.innerHTML = `
            <div class="btn-group w-100 mb-2">
                <button id="flt-mine" class="btn btn-outline-primary btn-sm">My Vehicles</button>
                <button id="flt-all" class="btn btn-outline-secondary btn-sm">Show All</button>
            </div>
            <div class="btn-group w-100">
                <button id="flt-moving" class="btn btn-outline-success btn-sm">Moving</button>
                <button id="flt-stopped" class="btn btn-outline-warning btn-sm">Stopped</button>
            </div>
        `;

        document.querySelector(".map-sidebar-card .card-header").appendChild(filterBox);

        document.getElementById("flt-mine").onclick = () => {
            filters.showOnlyMine = true;
            refreshFilterView();
        };

        document.getElementById("flt-all").onclick = () => {
            filters.showOnlyMine = false;
            refreshFilterView();
        };

        document.getElementById("flt-moving").onclick = () => {
            filters.showMoving = !filters.showMoving;
            refreshFilterView();
        };

        document.getElementById("flt-stopped").onclick = () => {
            filters.showStopped = !filters.showStopped;
            refreshFilterView();
        };
    }

    function refreshFilterView() {
        Object.keys(vehicles).forEach(updateSidebarRow);
    }

    // -------------------------
    // WebSocket live updates
    // -------------------------
    let ws = null;
    let reconnectTimer = null;

    function setWSStatus(text, cls) {
        wsStatusPill.textContent = text;
        wsStatusPill.className = "badge connection-pill " + cls;
    }

    function connectWS() {
        if (ws) try { ws.close(); } catch {}

        setWSStatus("Connecting…", "text-bg-secondary");

        ws = new WebSocket(WS_URL);

        ws.onopen = () => setWSStatus("Connected", "text-bg-success");

        ws.onclose = () => {
            setWSStatus("Disconnected", "text-bg-danger");
            scheduleReconnect();
        };

        ws.onerror = () => setWSStatus("Error", "text-bg-danger");

        ws.onmessage = evt => {
            lastRefreshText.textContent = "Last update: " + new Date().toLocaleTimeString();

            try {
                const payload = JSON.parse(evt.data);

                if (Array.isArray(payload))
                    payload.forEach(p => upsertMarker(p));
                else
                    upsertMarker(payload);

            } catch (e) {
                console.error("Invalid WS packet:", evt.data);
            }
        };
    }

    function scheduleReconnect() {
        if (reconnectTimer) return;
        reconnectTimer = setTimeout(() => {
            reconnectTimer = null;
            connectWS();
        }, 3000);
    }

    // -------------------------
    // Buttons
    // -------------------------
    btnFitAll.onclick = () => {
        const ids = Object.keys(vehicles);
        if (!ids.length) return;

        const group = L.featureGroup(ids.map(id => vehicles[id].marker));
        map.fitBounds(group.getBounds().pad(0.2));
    };

    btnClearTrails.onclick = () => {
        Object.values(vehicles).forEach(v => {
            v.trailLatLngs = [];
            v.polyline.setLatLngs([]);
        });
    };

    // -------------------------
    // Boot
    // -------------------------
    loadInitial();
    connectWS();
    createFilterControls();

})();
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
