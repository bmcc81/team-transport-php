/**
 * TEAM TRANSPORT – Live Fleet Map
 * --------------------------------
 * Features:
 *  - WebSocket telemetry
 *  - Clusters
 *  - Trails
 *  - Heatmap
 *  - Geofences (circle + polygon) from DB
 *  - Geofence enter/exit alerts
 *  - Active vehicles table
 *  - Playback (last 2 minutes)
 *  - Vehicle detail modal + follow + CSV export
 *  - Geofence drawing tools + modal save
 */

// -----------------------------
// MAP & LAYERS
// -----------------------------
const initialCenter = (window.INITIAL_CENTER && Array.isArray(window.INITIAL_CENTER))
    ? window.INITIAL_CENTER
    : [45.50, -73.57];

let map = L.map("live-map").setView(initialCenter, 14);

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

const btnFitAll = document.getElementById("btn-fit-all");
if (btnFitAll) btnFitAll.addEventListener("click", fitAllVehicles);

// -----------------------------
// FILTERS
// -----------------------------
const filterShowStopped   = document.getElementById("filter-show-stopped");
const filterShowHeatmap   = document.getElementById("filter-show-heatmap");
const filterShowTrails    = document.getElementById("filter-show-trails");
const filterShowClusters  = document.getElementById("filter-show-clusters");
const filterMinSpeed      = document.getElementById("filter-min-speed");
const filterMinSpeedValue = document.getElementById("filter-min-speed-value");

if (filterMinSpeed) {
    filterMinSpeed.addEventListener("input", () => {
        filterMinSpeedValue.textContent = filterMinSpeed.value;
        applyFilters();
    });
}

[filterShowStopped, filterShowHeatmap, filterShowTrails, filterShowClusters]
    .filter(Boolean)
    .forEach(el => el.addEventListener("change", applyFilters));

function applyFilters() {
    const minSpeed    = filterMinSpeed ? parseInt(filterMinSpeed.value, 10) : 0;
    const showStopped = filterShowStopped ? filterShowStopped.checked : true;
    const showHeatmap = filterShowHeatmap ? filterShowHeatmap.checked : true;
    const showTrails  = filterShowTrails ? filterShowTrails.checked : true;
    const showClusters = filterShowClusters ? filterShowClusters.checked : true;

    // markers visibility
    Object.entries(vehicleMarkers).forEach(([id, marker]) => {
        const hist = telemetryHistory[id];
        if (!hist || !hist.length) return;
        const last = hist[hist.length - 1];

        let visible = true;
        if (!showStopped && last.speed < 5) visible = false;
        if (last.speed < minSpeed) visible = false;

        if (visible && showClusters) {
            clusterGroup.addLayer(marker);
        } else {
            clusterGroup.removeLayer(marker);
        }
    });

    // trails
    Object.values(trailPolylines).forEach(poly => {
        if (showTrails) {
            if (!map.hasLayer(poly)) map.addLayer(poly);
        } else if (map.hasLayer(poly)) {
            map.removeLayer(poly);
        }
    });

    // heatmap
    if (showHeatmap) {
        if (!map.hasLayer(heatLayer)) map.addLayer(heatLayer);
    } else if (map.hasLayer(heatLayer)) {
        map.removeLayer(heatLayer);
    }
}

// -----------------------------
// SIDEBAR TABLE
// -----------------------------
const tableBody        = document.getElementById("active-vehicles-body");
const lastUpdateLabel  = document.getElementById("last-update-label");
const playbackSelect   = document.getElementById("playback-vehicle");

function updateVehicleRow(id, latlng, speed, timestamp) {
    if (!tableBody) return;

    const rowId = "vehicle-row-" + id;
    let row = document.getElementById(rowId);

    const posText   = latlng[0].toFixed(5) + ", " + latlng[1].toFixed(5);
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

        if (playbackSelect) {
            const opt = document.createElement("option");
            opt.value = id;
            opt.textContent = "Vehicle " + id;
            playbackSelect.appendChild(opt);
        }
    } else {
        row.querySelector(".veh-pos").textContent   = posText;
        row.querySelector(".veh-speed").textContent = speedText;
        row.querySelector(".veh-time").textContent  = timestamp;
    }
}

// -----------------------------
// GEOFENCE ALERT UI
// -----------------------------
const alertsBox = document.getElementById("geofence-alerts");
const btnClearAlerts = document.getElementById("btn-clear-alerts");

if (btnClearAlerts && alertsBox) {
    btnClearAlerts.addEventListener("click", () => {
        alertsBox.innerHTML = '<div class="text-muted">No alerts yet.</div>';
    });
}

function logGeofenceAlert(msg) {
    if (!alertsBox) return;
    const div = document.createElement("div");
    div.textContent = new Date().toISOString().slice(11, 19) + " — " + msg;
    alertsBox.prepend(div);
}

// -----------------------------
// GEOFENCES FROM DATABASE
// -----------------------------
const geoLayers = {};   // geofence_id → layer info
const geoInside = {};   // vehicle_id → { geofence_id: true/false }

function leafletPointInPolygon(latlng, poly) {
    let x = latlng.lat, y = latlng.lng;
    let inside = false;
    for (let i = 0, j = poly.length - 1; i < poly.length; j = i++) {
        let xi = poly[i].lat, yi = poly[i].lng;
        let xj = poly[j].lat, yj = poly[j].lng;
        let intersect =
            ((yi > y) !== (yj > y)) &&
            (x < (xj - xi) * (y - yi) / ((yj - yi) || 1) + xi);
        if (intersect) inside = !inside;
    }
    return inside;
}

function renderGeofences() {
    if (!window.GEOFENCES || !Array.isArray(window.GEOFENCES)) return;

    window.GEOFENCES.forEach(g => {
        // CIRCLE
        if (g.type === "circle" && g.center_lat && g.center_lng && g.radius_m) {
            const layer = L.circle([g.center_lat, g.center_lng], {
                radius: g.radius_m,
                color: "#ff6600",
                weight: 2,
                fillColor: "#ffae42",
                fillOpacity: 0.20
            })
            .addTo(map)
            .bindPopup(`<strong>${g.name}</strong><br>Radius: ${g.radius_m} m`);

            geoLayers[g.id] = {
                id: g.id,
                name: g.name,
                type: "circle",
                center: L.latLng(g.center_lat, g.center_lng),
                radius: g.radius_m,
                layer
            };
        }

        // POLYGON
        if (g.type === "polygon" && g.polygon_points) {
            try {
                const points = JSON.parse(g.polygon_points);
                const layer = L.polygon(points, {
                    color: "#0066ff",
                    weight: 2,
                    fillOpacity: 0.15
                })
                .addTo(map)
                .bindPopup(`<strong>${g.name}</strong><br>Polygon Zone`);

                geoLayers[g.id] = {
                    id: g.id,
                    name: g.name,
                    type: "polygon",
                    polygonPoints: points,
                    layer
                };
            } catch (err) {
                console.error("Invalid polygon_points for geofence", g.id, err);
            }
        }
    });

    console.log("Loaded geofences:", geoLayers);
}

renderGeofences();

// ENTER / EXIT DETECTION
function checkGeofences(vehicleId, latlng) {
    if (!geoInside[vehicleId]) geoInside[vehicleId] = {};

    Object.values(geoLayers).forEach(g => {
        let isInside = false;

        if (g.type === "circle") {
            const dist = map.distance(latlng, g.center);
            isInside = dist <= g.radius;
        }

        if (!isInside && g.type === "polygon") {
            isInside =
                g.layer.getBounds().contains(latlng) &&
                leafletPointInPolygon(latlng, g.layer.getLatLngs()[0]);
        }

        const wasInside = !!geoInside[vehicleId][g.id];

        if (isInside && !wasInside) {
            geoInside[vehicleId][g.id] = true;
            logGeofenceAlert(`Vehicle ${vehicleId} ENTERED ${g.name}`);
        } else if (!isInside && wasInside) {
            geoInside[vehicleId][g.id] = false;
            logGeofenceAlert(`Vehicle ${vehicleId} EXITED ${g.name}`);
        }
    });
}

// -----------------------------
// GEOFENCE DRAWING TOOLS
// -----------------------------
let drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

const btnDrawCircle  = document.getElementById("btn-draw-circle");
const btnDrawPolygon = document.getElementById("btn-draw-polygon");

// Only configure draw tools if Leaflet.draw is loaded
let drawControl = null;
if (typeof L.Draw !== "undefined") {
    drawControl = {
        circle: new L.Draw.Circle(map, {
            showRadius: true,
            shapeOptions: {
                color: "#ff6600",
                weight: 2
            }
        }),
        polygon: new L.Draw.Polygon(map, {
            allowIntersection: false,
            showArea: true,
            shapeOptions: {
                color: "#0066ff",
                weight: 2
            }
        })
    };
}

if (btnDrawCircle && drawControl && drawControl.circle) {
    btnDrawCircle.addEventListener("click", () => {
        drawControl.circle.enable();
    });
}

if (btnDrawPolygon && drawControl && drawControl.polygon) {
    btnDrawPolygon.addEventListener("click", () => {
        drawControl.polygon.enable();
    });
}

map.on(L.Draw.Event.CREATED, function (e) {
    const layer = e.layer;
    drawnItems.addLayer(layer);

    if (e.layerType === "circle") {
        const center = layer.getLatLng();
        const radius = layer.getRadius();

        openGeofenceCreateModal({
            type: "circle",
            center_lat: center.lat,
            center_lng: center.lng,
            radius_m: radius
        });
    }

    if (e.layerType === "polygon") {
        const latlngs = layer.getLatLngs()[0].map(p => [p.lat, p.lng]);

        openGeofenceCreateModal({
            type: "polygon",
            polygon_points: latlngs
        });
    }
});

// -----------------------------
// GEOFENCE CREATE MODAL + AJAX
// -----------------------------
function openGeofenceCreateModal(data) {
    const modalEl = document.getElementById("geofenceCreateModal");
    if (!modalEl) return;

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const typeInput  = document.getElementById("geo-type");
    const latInput   = document.getElementById("geo-center-lat");
    const lngInput   = document.getElementById("geo-center-lng");
    const radiusInput = document.getElementById("geo-radius");
    const polyInput  = document.getElementById("geo-poly");

    if (!typeInput || !latInput || !lngInput || !radiusInput || !polyInput) {
        console.error("Geofence modal inputs not found");
        return;
    }

    typeInput.value = data.type;

    if (data.type === "circle") {
        latInput.value   = data.center_lat;
        lngInput.value   = data.center_lng;
        radiusInput.value = Math.round(data.radius_m);
        polyInput.value  = "";
    }

    if (data.type === "polygon") {
        polyInput.value  = JSON.stringify(data.polygon_points);
        latInput.value   = "";
        lngInput.value   = "";
        radiusInput.value = "";
    }

    modal.show();
}

const geofenceCreateForm = document.getElementById("geofence-create-form");
if (geofenceCreateForm) {
    geofenceCreateForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        const form = e.target;
        const payload = new FormData(form);

        try {
            const res = await fetch("/admin/geofences/store", {
                method: "POST",
                body: payload
            });

            if (res.ok) {
                // Close modal
                const modalEl = document.getElementById("geofenceCreateModal");
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl) ||
                                  bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();
                }

                // Simple UX feedback
                alert("Geofence created.");
                // Reload to fetch new geofence + draw it
                location.reload();
            } else {
                alert("Failed to save geofence.");
            }
        } catch (err) {
            console.error("Error saving geofence", err);
            alert("Error saving geofence.");
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

    // heatmap points
    const heatPoints = [];
    Object.values(telemetryHistory).forEach(list => {
        const last = list[list.length - 1];
        if (last) heatPoints.push([last.lat, last.lng, 0.6]);
    });
    heatLayer.setLatLngs(heatPoints);

    // sidebar row
    updateVehicleRow(id, latlng, speed, timestamp);
    if (lastUpdateLabel) {
        lastUpdateLabel.textContent = new Date().toISOString().slice(11, 19) + " UTC";
    }

    // geofences
    checkGeofences(id, L.latLng(latlng[0], latlng[1]));

    // follow
    if (followVehicleId === id) {
        map.panTo(latlng);
    }

    // refresh modal if open
    if (activeModalVehicleId === id) {
        renderVehicleModal(id);
    }

    // filters
    applyFilters();
}

// -----------------------------
// VEHICLE MODAL
// -----------------------------
let miniMap = null;
let miniTrail = null;
let miniMarker = null;

const modalIdEl      = document.getElementById("modal-veh-id");
const modalStatusEl  = document.getElementById("modal-current-status");
const modalStatsEl   = document.getElementById("modal-stats");
const modalPointsEl  = document.getElementById("modal-last-points");
const modalFollowBtn = document.getElementById("modal-follow-btn");
const modalExportBtn = document.getElementById("modal-export-btn");
const vehicleModalEl = document.getElementById("vehicleModal");

if (vehicleModalEl) {
    vehicleModalEl.addEventListener("hidden.bs.modal", () => {
        if (followVehicleId === activeModalVehicleId) {
            followVehicleId = null;
        }
        activeModalVehicleId = null;
    });
}

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
    if (!modalIdEl) return;

    const hist = telemetryHistory[id] || [];
    if (!hist.length) return;

    const last = hist[hist.length - 1];

    modalIdEl.textContent = id;
    modalStatusEl.innerHTML = "";
    modalStatsEl.innerHTML = "";
    modalPointsEl.innerHTML = "";

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

    const STAT_POINTS = 30;
    const windowPoints = hist.slice(-STAT_POINTS);
    if (windowPoints.length) {
        const totalSpeed = windowPoints.reduce((s, p) => s + p.speed, 0);
        const maxSpeed   = windowPoints.reduce((m, p) => Math.max(m, p.speed), 0);

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

    const last10 = hist.slice(-10).reverse();
    last10.forEach(p => {
        const li = document.createElement("li");
        li.textContent =
            `${p.tsDate.toISOString().slice(11, 19)} — ${p.lat.toFixed(5)}, ${p.lng.toFixed(5)} (${p.speed} km/h)`;
        modalPointsEl.appendChild(li);
    });

    const miniDiv = document.getElementById("modal-mini-map");
    if (!miniDiv) return;

    if (!miniMap) {
        miniMap = L.map(miniDiv, { zoomControl: false }).setView([last.lat, last.lng], 15);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19
        }).addTo(miniMap);
    } else {
        miniMap.invalidateSize();
        miniMap.setView([last.lat, last.lng], 15);
    }

    if (miniTrail) miniMap.removeLayer(miniTrail);
    if (miniMarker) miniMap.removeLayer(miniMarker);

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

    if (modalFollowBtn) {
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
}

function openVehicleModal(id) {
    if (!vehicleModalEl) return;
    activeModalVehicleId = id;
    renderVehicleModal(id);

    const modal = bootstrap.Modal.getOrCreateInstance(vehicleModalEl);
    modal.show();
}

if (modalFollowBtn) {
    modalFollowBtn.addEventListener("click", () => {
        if (!activeModalVehicleId) return;
        if (followVehicleId === activeModalVehicleId) {
            followVehicleId = null;
        } else {
            followVehicleId = activeModalVehicleId;
        }
        renderVehicleModal(activeModalVehicleId);
    });
}

if (modalExportBtn) {
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
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement("a");
        a.href = url;
        a.download = `vehicle_${id}_telemetry.csv`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
}

// -----------------------------
// PLAYBACK (last 2 minutes)
// -----------------------------
const btnPlayback      = document.getElementById("btn-playback");
const playbackProgress = document.getElementById("playback-progress");
let playbackTimer      = null;

if (btnPlayback && playbackSelect) {
    btnPlayback.addEventListener("click", () => {
        const vid = playbackSelect.value;
        if (!vid) return;
        startPlayback(vid);
    });
}

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
            if (playbackProgress) playbackProgress.style.width = "0%";
            map.removeLayer(ghostMarker);
            return;
        }
        const p = windowPoints[idx];
        ghostMarker.setLatLng([p.lat, p.lng]);
        if (playbackProgress) {
            playbackProgress.style.width = (idx / (windowPoints.length - 1) * 100).toFixed(0) + "%";
        }
    }, 300);
}

// -----------------------------
// WEBSOCKET SETUP
// -----------------------------
let ws;
const wsPill = document.getElementById("ws-status-pill");

function setPill(text, cls) {
    if (!wsPill) return;
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
            console.error("Invalid telemetry payload", e);
        }
    };
}

connectWS();
