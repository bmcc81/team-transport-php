<?php
$pageTitle = "Live Fleet Map";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-0">Live Fleet Map</h2>
                    <small class="text-muted">
                        Real-time fleet view with trails, clusters, heatmap &amp; geofences
                    </small>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span id="ws-status-pill" class="badge text-bg-secondary connection-pill">
                        WS: Connecting…
                    </span>
                    <button id="btn-fit-all" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrows-fullscreen"></i> Fit All
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <!-- MAP -->
                <div class="col-12 col-xl-8">
                    <div id="live-map" class="border rounded shadow-sm" style="height: 640px;"></div>
                </div>

                <!-- SIDEBAR -->
                <div class="col-12 col-xl-4">
                    <!-- Filters & view options -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-funnel"></i> Filters & View</span>
                        </div>
                        <div class="card-body small">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filter-show-stopped" checked>
                                        <label class="form-check-label" for="filter-show-stopped">
                                            Show stopped (&lt; 5 km/h)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filter-show-heatmap" checked>
                                        <label class="form-check-label" for="filter-show-heatmap">
                                            Heatmap
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filter-show-trails" checked>
                                        <label class="form-check-label" for="filter-show-trails">
                                            Trails
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="filter-show-clusters" checked>
                                        <label class="form-check-label" for="filter-show-clusters">
                                            Clusters
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <label for="filter-min-speed" class="form-label mb-1">
                                        Min speed (km/h)
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="range" min="0" max="110" value="0"
                                               class="form-range flex-grow-1" id="filter-min-speed">
                                        <span id="filter-min-speed-value" class="fw-semibold">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active vehicles table -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-truck"></i> Active Vehicles</span>
                            <small class="text-muted">
                                Last update: <span id="last-update-label">—</span>
                            </small>
                        </div>
                        <div class="table-responsive" style="max-height: 260px; overflow-y: auto;">
                            <table class="table table-sm table-hover mb-0 align-middle">
                                <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Position</th>
                                    <th>Speed</th>
                                    <th>Time (UTC)</th>
                                </tr>
                                </thead>
                                <tbody id="active-vehicles-body">
                                <!-- rows built by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Playback & geofences -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-clock-history"></i> Playback & Geofences</span>
                            <button id="btn-clear-alerts" class="btn btn-outline-secondary btn-sm">
                                Clear alerts
                            </button>
                        </div>
                        <div class="card-body small">
                            <div class="mb-3">
                                <label class="form-label mb-1">
                                    Playback (last 2 minutes)
                                </label>
                                <div class="d-flex gap-2">
                                    <select id="playback-vehicle" class="form-select form-select-sm">
                                        <option value="">Select vehicle…</option>
                                    </select>
                                    <button id="btn-playback" class="btn btn-primary btn-sm">
                                        <i class="bi bi-play-circle"></i>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <div class="progress" style="height: 4px;">
                                        <div id="playback-progress" class="progress-bar" role="progressbar"
                                             style="width: 0;"></div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="form-label mb-1">
                                    Geofence Alerts
                                </label>
                                <div id="geofence-alerts" class="border rounded p-2 bg-light"
                                     style="max-height: 160px; overflow-y:auto; font-size: 0.8rem;">
                                    <div class="text-muted">No alerts yet.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- /sidebar -->
            </div>
        </main>
    </div>
</div>

<!-- Vehicle detail modal (ADVANCED) -->
<div class="modal fade" id="vehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-truck-front"></i>
                    Vehicle <span id="modal-veh-id"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body small">
                <div class="row g-3">
                    <!-- LEFT: Status + stats + last 10 -->
                    <div class="col-12 col-lg-7">
                        <h6 class="mb-2">Current Status</h6>
                        <ul class="list-unstyled mb-3" id="modal-current-status"></ul>

                        <h6 class="mb-2">Stats (last window)</h6>
                        <ul class="list-unstyled mb-3" id="modal-stats"></ul>

                        <h6 class="mb-2">Last 10 points</h6>
                        <div class="border rounded p-2" style="max-height: 220px; overflow-y:auto;">
                            <ul class="list-unstyled mb-0" id="modal-last-points"></ul>
                        </div>
                    </div>

                    <!-- RIGHT: Mini map + controls -->
                    <div class="col-12 col-lg-5">
                        <div class="d-flex justify-content-end gap-2 mb-2">
                            <button id="modal-follow-btn" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-person-bounding-box"></i> Follow
                            </button>
                            <button id="modal-export-btn" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-file-earmark-arrow-down"></i> Export CSV
                            </button>
                        </div>
                        <h6 class="mb-2">Mini Trail (last 10)</h6>
                        <div id="modal-mini-map" class="border rounded" style="height: 260px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>

<!-- Leaflet + plugins -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Marker clustering -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
/>
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<!-- Heatmap -->
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>

<script>
/**
 * LIVE MAP + ADVANCED FEATURES
 * - WebSocket telemetry
 * - Clusters, trails, heatmap
 * - Playback & geofence alerts
 * - Vehicle detail modal (advanced)
 */

// -----------------------------
// MAP & LAYERS
// -----------------------------
let map = L.map("live-map").setView([45.50, -73.57], 14);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 20
}).addTo(map);

const clusterGroup = L.markerClusterGroup({
    showCoverageOnHover: false,
    maxClusterRadius: 60,
    iconCreateFunction: function (cluster) {
        const count = cluster.getChildCount();
        let c = "marker-cluster-small";
        if (count > 25) c = "marker-cluster-large";
        else if (count > 10) c = "marker-cluster-medium";
        return new L.DivIcon({
            html: "<div><span>" + count + "</span></div>",
            className: "marker-cluster " + c,
            iconSize: new L.Point(40, 40)
        });
    }
});
map.addLayer(clusterGroup);

let heatLayer = L.heatLayer([], {
    radius: 25,
    blur: 15,
    maxZoom: 18
}).addTo(map);

// markers, trails & history
const vehicleMarkers = {};        // id -> L.circleMarker
const vehicleTrails = {};         // id -> [latlng]
const trailPolylines = {};        // id -> L.polyline
const telemetryHistory = {};      // id -> [{lat,lng,speed,heading,tsDate}]
const MAX_TRAIL_POINTS = 120;     // ~last couple of minutes

// -----------------------------
// UI HELPERS
// -----------------------------
function getSpeedColor(speed) {
    if (speed < 5) return "#6c757d";       // gray
    if (speed < 40) return "#0d6efd";      // blue
    if (speed < 80) return "#ffc107";      // yellow
    return "#dc3545";                      // red
}

function fitAllVehicles() {
    const ids = Object.keys(vehicleMarkers);
    if (!ids.length) return;
    const group = L.featureGroup(ids.map(id => vehicleMarkers[id]));
    map.fitBounds(group.getBounds().pad(0.2));
}

document.getElementById("btn-fit-all").addEventListener("click", fitAllVehicles);

// -----------------------------
// FILTERS
// -----------------------------
const filterShowStopped = document.getElementById("filter-show-stopped");
const filterShowHeatmap = document.getElementById("filter-show-heatmap");
const filterShowTrails = document.getElementById("filter-show-trails");
const filterShowClusters = document.getElementById("filter-show-clusters");
const filterMinSpeed = document.getElementById("filter-min-speed");
const filterMinSpeedValue = document.getElementById("filter-min-speed-value");

filterMinSpeed.addEventListener("input", () => {
    filterMinSpeedValue.textContent = filterMinSpeed.value;
    applyFilters();
});

[filterShowStopped, filterShowHeatmap, filterShowTrails, filterShowClusters]
    .forEach(el => el.addEventListener("change", applyFilters));

function applyFilters() {
    const minSpeed = parseInt(filterMinSpeed.value, 10);
    const showStopped = filterShowStopped.checked;

    // markers visibility
    Object.entries(vehicleMarkers).forEach(([id, marker]) => {
        const hist = telemetryHistory[id];
        if (!hist || !hist.length) return;
        const last = hist[hist.length - 1];

        let visible = true;
        if (!showStopped && last.speed < 5) visible = false;
        if (last.speed < minSpeed) visible = false;

        if (visible) {
            clusterGroup.addLayer(marker);
        } else {
            clusterGroup.removeLayer(marker);
        }
    });

    // trails
    Object.values(trailPolylines).forEach(poly => {
        if (filterShowTrails.checked) {
            if (!map.hasLayer(poly)) map.addLayer(poly);
        } else {
            if (map.hasLayer(poly)) map.removeLayer(poly);
        }
    });

    // heatmap
    if (filterShowHeatmap.checked) {
        if (!map.hasLayer(heatLayer)) map.addLayer(heatLayer);
    } else {
        if (map.hasLayer(heatLayer)) map.removeLayer(heatLayer);
    }
}

// -----------------------------
// SIDEBAR TABLE
// -----------------------------
const tableBody = document.getElementById("active-vehicles-body");
const lastUpdateLabel = document.getElementById("last-update-label");
const playbackSelect = document.getElementById("playback-vehicle");

function updateVehicleRow(id, latlng, speed, timestamp) {
    const rowId = "vehicle-row-" + id;
    let row = document.getElementById(rowId);

    const posText = latlng[0].toFixed(5) + ", " + latlng[1].toFixed(5);
    const speedText = speed + " km/h";

    if (!row) {
        row = document.createElement("tr");
        row.id = rowId;
        row.innerHTML = `
            <td class="veh-id">${id}</td>
            <td class="veh-pos">${posText}</td>
            <td class="veh-speed">${speedText}</td>
            <td class="veh-time">${timestamp}</td>
        `;
        row.addEventListener("click", () => openVehicleModal(id));
        tableBody.appendChild(row);

        // add to playback dropdown
        const opt = document.createElement("option");
        opt.value = id;
        opt.textContent = "Vehicle " + id;
        playbackSelect.appendChild(opt);
    } else {
        row.querySelector(".veh-pos").textContent = posText;
        row.querySelector(".veh-speed").textContent = speedText;
        row.querySelector(".veh-time").textContent = timestamp;
    }
}

// -----------------------------
// GEOFENCES (simple demo)
// -----------------------------
const geofences = [
    {
        id: "DOWNTOWN",
        name: "Downtown Zone",
        polygon: L.polygon([
            [45.506, -73.580],
            [45.506, -73.565],
            [45.495, -73.565],
            [45.495, -73.580]
        ], {color: "#6610f2", weight: 1, fillOpacity: 0.03}).addTo(map),
        inside: {}  // vehicle_id -> boolean
    }
];

const alertsBox = document.getElementById("geofence-alerts");
document.getElementById("btn-clear-alerts").addEventListener("click", () => {
    alertsBox.innerHTML = '<div class="text-muted">No alerts yet.</div>';
});

function logGeofenceAlert(msg) {
    const div = document.createElement("div");
    div.textContent = new Date().toISOString().slice(11, 19) + " — " + msg;
    alertsBox.prepend(div);
}

function leafletPointInPolygon(latlng, poly) {
    let x = latlng.lat, y = latlng.lng;
    let inside = false;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        let xi = poly[i].lat, yi = poly[i].lng;
        let xj = poly[j].lat, yj = poly[j].lng;
        let intersect = ((yi > y) !== (yj > y))
            && (x < (xj - xi) * (y - yi) / (yj - yi + 1e-9) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
}

function checkGeofences(vehicleId, latlng) {
    geofences.forEach(g => {
        const isInside = g.polygon.getBounds().contains(latlng)
            && leafletPointInPolygon(latlng, g.polygon.getLatLngs()[0]);

        const wasInside = !!g.inside[vehicleId];
        if (isInside && !wasInside) {
            g.inside[vehicleId] = true;
            logGeofenceAlert(`Vehicle ${vehicleId} ENTERED ${g.name}`);
        } else if (!isInside && wasInside) {
            g.inside[vehicleId] = false;
            logGeofenceAlert(`Vehicle ${vehicleId} EXITED ${g.name}`);
        }
    });
}

// -----------------------------
// TELEMETRY HANDLER
// -----------------------------
let activeModalVehicleId = null;
let followVehicleId = null;

function handleTelemetryUpdate(data) {
    const {
        vehicle_id,
        latitude,
        longitude,
        speed,
        heading,
        timestamp
    } = data;

    const id = String(vehicle_id);
    const latlng = [latitude, longitude];

    // history
    const tsDate = new Date(timestamp.replace(" ", "T") + "Z");
    if (!telemetryHistory[id]) telemetryHistory[id] = [];
    telemetryHistory[id].push({
        lat: latitude,
        lng: longitude,
        speed: speed,
        heading: (typeof heading === "number") ? heading : null,
        tsDate: tsDate
    });
    if (telemetryHistory[id].length > MAX_TRAIL_POINTS) {
        telemetryHistory[id].shift();
    }

    // marker
    if (!vehicleMarkers[id]) {
        const marker = L.circleMarker(latlng, {
            radius: 8,
            weight: 1,
            color: "#000",
            fillColor: getSpeedColor(speed),
            fillOpacity: 0.9
        });
        marker.on("click", () => openVehicleModal(id));
        vehicleMarkers[id] = marker;
        clusterGroup.addLayer(marker);
    } else {
        const marker = vehicleMarkers[id];
        marker.setLatLng(latlng);
        marker.setStyle({ fillColor: getSpeedColor(speed) });
    }

    // trail
    if (!vehicleTrails[id]) vehicleTrails[id] = [];
    vehicleTrails[id].push(latlng);
    if (vehicleTrails[id].length > MAX_TRAIL_POINTS) vehicleTrails[id].shift();

    if (!trailPolylines[id]) {
        trailPolylines[id] = L.polyline(vehicleTrails[id], {
            color: getSpeedColor(speed),
            weight: 3,
            opacity: 0.6
        }).addTo(map);
    } else {
        const poly = trailPolylines[id];
        poly.setLatLngs(vehicleTrails[id]);
        poly.setStyle({ color: getSpeedColor(speed) });
    }

    // heatmap point (use latest from each vehicle)
    const heatPoints = [];
    Object.values(telemetryHistory).forEach(list => {
        const last = list[list.length - 1];
        if (last) heatPoints.push([last.lat, last.lng, 0.6]);
    });
    heatLayer.setLatLngs(heatPoints);

    // sidebar row
    updateVehicleRow(id, latlng, speed, timestamp);
    lastUpdateLabel.textContent = new Date().toISOString().slice(11, 19) + " UTC";

    // geofences
    checkGeofences(id, L.latLng(latlng[0], latlng[1]));

    // map follow
    if (followVehicleId === id) {
        map.panTo(latlng);
    }

    // refresh modal if open for this vehicle
    if (activeModalVehicleId === id) {
        renderVehicleModal(id);
    }

    // reapply filters
    applyFilters();
}

// -----------------------------
// VEHICLE MODAL (advanced)
// -----------------------------
let miniMap = null;
let miniTrail = null;
let miniMarker = null;

const modalIdEl = document.getElementById("modal-veh-id");
const modalStatusEl = document.getElementById("modal-current-status");
const modalStatsEl = document.getElementById("modal-stats");
const modalPointsEl = document.getElementById("modal-last-points");
const modalFollowBtn = document.getElementById("modal-follow-btn");
const modalExportBtn = document.getElementById("modal-export-btn");
const vehicleModalEl = document.getElementById("vehicleModal");

vehicleModalEl.addEventListener("hidden.bs.modal", () => {
    if (followVehicleId === activeModalVehicleId) {
        followVehicleId = null;
    }
    activeModalVehicleId = null;
});

function haversineKm(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a =
        Math.sin(dLat / 2) * Math.sin(dLat / 2) +
        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon / 2) * Math.sin(dLon / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
}

function renderVehicleModal(id) {
    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    const last = hist[hist.length - 1];

    modalIdEl.textContent = id;
    modalStatusEl.innerHTML = "";
    modalStatsEl.innerHTML = "";
    modalPointsEl.innerHTML = "";

    // current status
    const liSpeed = document.createElement("li");
    liSpeed.innerHTML = `<strong>Speed:</strong> ${last.speed} km/h`;

    const liPos = document.createElement("li");
    liPos.innerHTML = `<strong>Position:</strong> ${last.lat.toFixed(5)}, ${last.lng.toFixed(5)}`;

    const liTime = document.createElement("li");
    liTime.innerHTML = `<strong>Last ping (UTC):</strong> ${last.tsDate.toISOString().slice(11, 19)}`;

    modalStatusEl.appendChild(liSpeed);
    modalStatusEl.appendChild(liPos);
    modalStatusEl.appendChild(liTime);

    if (last.heading !== null && last.heading !== undefined) {
        const liHeading = document.createElement("li");
        liHeading.innerHTML = `<strong>Heading:</strong> ${last.heading}°`;
        modalStatusEl.appendChild(liHeading);
    }

    // stats window (last N points)
    const STAT_POINTS = 30;
    const windowPoints = hist.slice(-STAT_POINTS);
    if (windowPoints.length) {
        const totalSpeed = windowPoints.reduce((s, p) => s + p.speed, 0);
        const maxSpeed = windowPoints.reduce((m, p) => Math.max(m, p.speed), 0);

        let totalDist = 0;
        for (let i = 1; i < windowPoints.length; i++) {
            const p1 = windowPoints[i - 1];
            const p2 = windowPoints[i];
            totalDist += haversineKm(p1.lat, p1.lng, p2.lat, p2.lng);
        }

        const liAvg = document.createElement("li");
        liAvg.innerHTML = `<strong>Avg speed:</strong> ${(totalSpeed / windowPoints.length).toFixed(1)} km/h`;

        const liMax = document.createElement("li");
        liMax.innerHTML = `<strong>Max speed:</strong> ${maxSpeed.toFixed(0)} km/h`;

        const liDist = document.createElement("li");
        liDist.innerHTML = `<strong>Distance:</strong> ${totalDist.toFixed(2)} km`;

        modalStatsEl.appendChild(liAvg);
        modalStatsEl.appendChild(liMax);
        modalStatsEl.appendChild(liDist);
    }

    // last 10 points list
    const last10 = hist.slice(-10).reverse();
    last10.forEach(p => {
        const li = document.createElement("li");
        li.textContent =
            `${p.tsDate.toISOString().slice(11, 19)} — ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)} (${p.speed} km/h)`;
        modalPointsEl.appendChild(li);
    });

    // mini map
    const miniDiv = document.getElementById("modal-mini-map");
    if (!miniMap) {
        miniMap = L.map(miniDiv, { zoomControl: false }).setView([last.lat, last.lng], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19
        }).addTo(miniMap);
    } else {
        miniMap.invalidateSize();
        miniMap.setView([last.lat, last.lng], 15);
    }

    if (miniTrail) {
        miniMap.removeLayer(miniTrail);
    }
    if (miniMarker) {
        miniMap.removeLayer(miniMarker);
    }

    const miniPoints = hist.slice(-10).map(p => [p.lat, p.lng]);
    miniTrail = L.polyline(miniPoints, {
        color: "#0d6efd",
        weight: 3
    }).addTo(miniMap);

    miniMarker = L.circleMarker([last.lat, last.lng], {
        radius: 7,
        weight: 2,
        color: "#000",
        fillColor: "#0d6efd",
        fillOpacity: 0.9
    }).addTo(miniMap);

    // follow button label
    if (followVehicleId === id) {
        modalFollowBtn.classList.remove("btn-outline-primary");
        modalFollowBtn.classList.add("btn-primary");
        modalFollowBtn.innerHTML = '<i class="bi bi-person-bounding-box"></i> Following';
    } else {
        modalFollowBtn.classList.add("btn-outline-primary");
        modalFollowBtn.classList.remove("btn-primary");
        modalFollowBtn.innerHTML = '<i class="bi bi-person-bounding-box"></i> Follow';
    }
}

function openVehicleModal(id) {
    activeModalVehicleId = id;
    renderVehicleModal(id);

    const modal = bootstrap.Modal.getOrCreateInstance(vehicleModalEl);
    modal.show();
}

modalFollowBtn.addEventListener("click", () => {
    if (!activeModalVehicleId) return;
    if (followVehicleId === activeModalVehicleId) {
        followVehicleId = null;
    } else {
        followVehicleId = activeModalVehicleId;
    }
    renderVehicleModal(activeModalVehicleId);
});

modalExportBtn.addEventListener("click", () => {
    if (!activeModalVehicleId) return;
    const id = activeModalVehicleId;
    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    let csv = "time_utc,latitude,longitude,speed_kmh,heading\n";
    hist.forEach(p => {
        const t = p.tsDate.toISOString();
        const h = (p.heading === null || p.heading === undefined) ? "" : p.heading;
        csv += `${t},${p.lat},${p.lng},${p.speed},${h}\n`;
    });

    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `vehicle_${id}_telemetry.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
});

// -----------------------------
// PLAYBACK (last 2 minutes)
// -----------------------------
const btnPlayback = document.getElementById("btn-playback");
const playbackProgress = document.getElementById("playback-progress");
let playbackTimer = null;

btnPlayback.addEventListener("click", () => {
    const vid = playbackSelect.value;
    if (!vid) return;
    startPlayback(vid);
});

function startPlayback(id) {
    if (playbackTimer) {
        clearInterval(playbackTimer);
        playbackTimer = null;
    }
    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    const cutoff = new Date(hist[hist.length - 1].tsDate.getTime() - 2 * 60 * 1000);
    const windowPoints = hist.filter(p => p.tsDate >= cutoff);
    if (windowPoints.length < 2) return;

    let idx = 0;
    const ghostMarker = L.circleMarker([windowPoints[0].lat, windowPoints[0].lng], {
        radius: 9,
        weight: 2,
        color: "#000",
        fillColor: "#20c997",
        fillOpacity: 0.9
    }).addTo(map);

    playbackTimer = setInterval(() => {
        idx++;
        if (idx >= windowPoints.length) {
            clearInterval(playbackTimer);
            playbackTimer = null;
            playbackProgress.style.width = "0%";
            map.removeLayer(ghostMarker);
            return;
        }
        const p = windowPoints[idx];
        ghostMarker.setLatLng([p.lat, p.lng]);
        playbackProgress.style.width = (idx / (windowPoints.length - 1) * 100).toFixed(0) + "%";
    }, 300);
}

// -----------------------------
// WEBSOCKET SETUP
// -----------------------------
let ws;
const wsPill = document.getElementById("ws-status-pill");

function setPill(text, cls) {
    wsPill.textContent = text;
    wsPill.className = "badge connection-pill " + cls;
}

function connectWS() {
    const url = (location.protocol === "https:" ? "wss://" : "ws://") + location.host + "/ws";
    console.log("Connecting WS:", url);
    ws = new WebSocket(url);

    setPill("WS: Connecting…", "text-bg-secondary");

    ws.onopen = () => {
        setPill("WS: Connected", "text-bg-success");
    };
    ws.onerror = () => {
        setPill("WS: Error", "text-bg-danger");
    };
    ws.onclose = () => {
        setPill("WS: Reconnecting…", "text-bg-warning");
        setTimeout(connectWS, 2000);
    };
    ws.onmessage = (event) => {
        try {
            const data = JSON.parse(event.data);
            handleTelemetryUpdate(data);
        } catch (e) {
            console.error("Bad telemetry payload", e);
        }
    };
}

connectWS();
</script>
