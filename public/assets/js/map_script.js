document.addEventListener("DOMContentLoaded", () => {

    // Read ?focus=ID from URL
    const urlParams = new URLSearchParams(window.location.search);
    const focusVehicleId = urlParams.get("focus");

    if (focusVehicleId) {
        document.getElementById("mode-live").click();
    }

    // =====================================================
    //  MAP INIT
    // =====================================================
    const mapEl = document.getElementById("vehicle-map");
    if (!mapEl || typeof L === "undefined") {
        console.error("Leaflet map element or library not found.");
        return;
    }

    const map = L.map("vehicle-map").setView([45.5019, -73.5674], 11);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19
    }).addTo(map);

    // Pulsing icon CSS
    const styleEl = document.createElement("style");
    styleEl.innerHTML = `
        .pulsing-dot {
            width: 18px;
            height: 18px;
            background: rgba(0, 123, 255, 0.9);
            border-radius: 50%;
            box-shadow: 0 0 10px rgba(0,123,255,0.9);
            animation: pulse 1.2s infinite ease-in-out;
        }
        @keyframes pulse {
            0%   { transform: scale(0.6); opacity: 0.6; }
            50%  { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.6); opacity: 0.6; }
        }
    `;
    document.head.appendChild(styleEl);

    const pulsingIcon = L.divIcon({
        className: "pulsing-dot",
        iconSize: [20, 20]
    });

    // =====================================================
    //  DOM REFERENCES
    // =====================================================
    const modeLiveBtn      = document.getElementById("mode-live");
    const modePlaybackBtn  = document.getElementById("mode-playback");
    const playbackControls = document.getElementById("playback-controls");
    const liveLabel        = document.getElementById("live-label");

    const vehicleSelect    = document.getElementById("vehicle-select");
    const followToggle     = document.getElementById("follow-toggle");

    const trailModeNormal  = document.getElementById("trail-mode-normal");
    const trailModeHeatmap = document.getElementById("trail-mode-heatmap");

    const tripDateInput    = document.getElementById("trip-date");
    const loadTripBtn      = document.getElementById("btn-load-trip");
    const sliderEl         = document.getElementById("trip-slider");
    const playBtn          = document.getElementById("btn-play");
    const pauseBtn         = document.getElementById("btn-pause");
    const tripTimeLabel    = document.getElementById("trip-time-label");

    const tripAnalyticsWrap = document.getElementById("trip-analytics");
    const distEl = document.getElementById("trip-distance");
    const durEl  = document.getElementById("trip-duration");
    const avgEl  = document.getElementById("trip-avg-speed");
    const maxEl  = document.getElementById("trip-max-speed");
    const ptsEl  = document.getElementById("trip-points");

    const exportCsvBtn = document.getElementById("export-csv");
    const exportGpxBtn = document.getElementById("export-gpx");
    const exportKmlBtn = document.getElementById("export-kml");

    // =====================================================
    //  STATE
    // =====================================================
    let currentMode = "live"; // "live" | "playback"

    // Live tracking
    const liveMarkers   = {}; // vehicleId -> Leaflet Marker
    const liveTrails    = {}; // vehicleId -> [Polyline...]
    const livePositions = {}; // vehicleId -> [[lat,lng], ...]

    // Metadata (for dropdown + labels)
    const vehicleMeta   = {}; // vehicleId -> { vehicle_number, make, model, license_plate }

    // Playback tracking
    let selectedVehicleIds = [];                       // array of vehicle ids (strings)
    const playbackDataByVehicle = {};                  // id -> { points: [], summary: {} }
    const playbackTrailLayers  = {};                   // id -> [Polyline...]
    const playbackMarkers      = {};                   // id -> Marker

    let primaryVehicleId = null;                       // used for animation
    let playbackIndex    = 0;
    let playbackTimer    = null;

    // Behaviour flags
    let followEnabled = false;                         // auto-center on selected vehicle
    let trailMode     = "basic";                       // "basic" | "heatmap"

    // Colors for multi-vehicle playback
    const playbackColors = [
        "#0d6efd", // blue
        "#198754", // green
        "#fd7e14", // orange
        "#dc3545", // red
        "#6f42c1", // purple
        "#20c997"  // teal
    ];

    // =====================================================
    //  HELPERS
    // =====================================================

    function toNumber(v, fallback = null) {
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : fallback;
    }

    function haversineMeters(lat1, lon1, lat2, lon2) {
        const R = 6371e3;
        const toRad = (deg) => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);
        const a =
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }

    function getSpeedColor(speedKmh) {
        if (speedKmh > 100) return "#ff0000";  // red
        if (speedKmh > 60)  return "#ff7f00";  // orange
        if (speedKmh > 30)  return "#ffc107";  // yellow
        return "#198754";                      // green
    }

    function downloadFile(filename, content, mimeType) {
        const blob = new Blob([content], { type: mimeType });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement("a");
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    function getSelectedVehicleIds() {
        if (!vehicleSelect) return [];
        return Array.from(vehicleSelect.selectedOptions).map(opt => opt.value);
    }

    // =====================================================
    //  TRAILS (LIVE + PLAYBACK)
    // =====================================================

    function clearTrail(layersObj, vehicleId) {
        if (!layersObj[vehicleId]) return;
        layersObj[vehicleId].forEach(line => map.removeLayer(line));
        layersObj[vehicleId] = [];
    }

    function clearAllTrails(layersObj) {
        Object.keys(layersObj).forEach(id => clearTrail(layersObj, id));
    }

    /**
     * Redraw a trail for a set of points.
     * points: [{lat, lng, time?}] or [[lat,lng], ...] depending on call site.
     */
    function redrawTrail(layersObj, vehicleId, points, isPlayback, colorOverride = null) {
        clearTrail(layersObj, vehicleId);
        const segments = [];

        if (!points || points.length < 2) {
            layersObj[vehicleId] = segments;
            return;
        }

        for (let i = 1; i < points.length; i++) {
            const p1 = points[i - 1];
            const p2 = points[i];

            const lat1 = Array.isArray(p1) ? p1[0] : p1.lat;
            const lng1 = Array.isArray(p1) ? p1[1] : p1.lng;
            const lat2 = Array.isArray(p2) ? p2[0] : p2.lat;
            const lng2 = Array.isArray(p2) ? p2[1] : p2.lng;

            let color = colorOverride || "#0d6efd";
            let opacity = 0.75;

            if (trailMode === "heatmap" && p1.time && p2.time) {
                const distM = haversineMeters(lat1, lng1, lat2, lng2);
                const t1 = new Date(p1.time).getTime();
                const t2 = new Date(p2.time).getTime();
                const dtSeconds = Math.max(1, (t2 - t1) / 1000);
                const speedKmh  = (distM / 1000) * (3600 / dtSeconds);
                color = getSpeedColor(speedKmh);
            }

            const seg = L.polyline([[lat1, lng1], [lat2, lng2]], {
                color,
                weight: isPlayback ? 5 : 4,
                opacity,
                lineCap: "round"
            });

            segments.push(seg);
        }

        segments.forEach(seg => seg.addTo(map));
        layersObj[vehicleId] = segments;
    }

    function animateMarker(marker, from, to, duration = 700) {
        if (!marker) return;
        const start = performance.now();

        function step(now) {
            const progress = Math.min((now - start) / duration, 1);
            const lat = from.lat + (to.lat - from.lat) * progress;
            const lng = from.lng + (to.lng - from.lng) * progress;
            marker.setLatLng([lat, lng]);

            if (progress < 1) {
                requestAnimationFrame(step);
            }
        }

        requestAnimationFrame(step);
    }

    // =====================================================
    //  LIVE MODE
    // =====================================================

    function populateVehicleSelectFromLive(data) {
        if (!vehicleSelect) return;

        const previouslySelected = new Set(getSelectedVehicleIds());

        // Clear all options
        vehicleSelect.innerHTML = "";

        data.forEach((v) => {
            vehicleMeta[v.id] = {
                vehicle_number: v.vehicle_number,
                make: v.make,
                model: v.model,
                license_plate: v.license_plate
            };

            const label = `[${v.vehicle_number}] ${v.make} ${v.model} (${v.license_plate})`;
            const opt   = document.createElement("option");
            opt.value   = String(v.id);
            opt.textContent = label;

            if (previouslySelected.size === 0) {
                opt.selected = true; // first time, select all
            } else if (previouslySelected.has(opt.value)) {
                opt.selected = true;
            }

            vehicleSelect.appendChild(opt);
        });

        selectedVehicleIds = getSelectedVehicleIds();
        if (loadTripBtn) {
            loadTripBtn.disabled = selectedVehicleIds.length === 0;
        }
    }

    function fetchLivePositions() {
        if (currentMode !== "live") return;

        fetch("/admin/api/vehicles/live")
            .then(r => r.json())
            .then(data => {
                if (!Array.isArray(data)) return;

                // Update vehicle dropdown (only in-service vehicles)
                populateVehicleSelectFromLive(data);

                data.forEach(v => {
                    const id = String(v.id);

                    const lat = toNumber(v.latitude);
                    const lng = toNumber(v.longitude);
                    if (lat === null || lng === null) return;

                    const pos = L.latLng(lat, lng);

                    // Marker
                    if (!liveMarkers[id]) {
                        liveMarkers[id] = L.marker(pos, { icon: pulsingIcon }).addTo(map);
                        livePositions[id] = [[lat, lng]];
                    } else {
                        const from = liveMarkers[id].getLatLng();
                        animateMarker(liveMarkers[id], from, pos, 650);
                        if (!Array.isArray(livePositions[id])) {
                            livePositions[id] = [];
                        }
                        livePositions[id].push([lat, lng]);
                        if (livePositions[id].length > 80) {
                            livePositions[id].shift();
                        }
                    }

                    // Trail
                    redrawTrail(liveTrails, id, (livePositions[id] || []), false);

                    // Auto-center follow
                    if (followEnabled && selectedVehicleIds.length > 0 && id === String(selectedVehicleIds[0])) {
                        map.setView(pos, Math.max(13, map.getZoom()));
                    }
                });

            })
            .catch(err => console.error("Live fetch error:", err));
    }

    // Initial live fetch + interval
    fetchLivePositions();
    setInterval(fetchLivePositions, 5000);

    // =====================================================
    //  MODE SWITCH (LIVE / PLAYBACK)
    // =====================================================
    function setMode(mode) {
        currentMode = mode;

        if (mode === "live") {
            if (playbackControls) playbackControls.style.display = "none";
            if (liveLabel) liveLabel.style.display = "inline-block";
            if (modeLiveBtn) modeLiveBtn.classList.add("btn-success");
            if (modePlaybackBtn) modePlaybackBtn.classList.remove("btn-primary");

            // Stop playback
            clearInterval(playbackTimer);
            playbackTimer = null;

        } else if (mode === "playback") {
            if (playbackControls) playbackControls.style.display = "block";
            if (liveLabel) liveLabel.style.display = "none";
            if (modePlaybackBtn) modePlaybackBtn.classList.add("btn-primary");
            if (modeLiveBtn) modeLiveBtn.classList.remove("btn-success");
        }
    }

    if (modeLiveBtn) {
        modeLiveBtn.addEventListener("click", () => setMode("live"));
    }
    if (modePlaybackBtn) {
        modePlaybackBtn.addEventListener("click", () => setMode("playback"));
    }

    // =====================================================
    //  PLAYBACK MODE
    // =====================================================

    function hideTripAnalytics() {
        if (tripAnalyticsWrap) tripAnalyticsWrap.style.display = "none";
    }

    function updateTripAnalyticsFromSummaries(summaries) {
        if (!tripAnalyticsWrap) return;
        if (!summaries || summaries.length === 0) {
            hideTripAnalytics();
            return;
        }

        let totalDistance = 0;
        let totalDuration = 0;
        let maxSpeed      = 0;
        let totalPoints   = 0;

        summaries.forEach(s => {
            const d = toNumber(s.total_distance_km, 0);
            const m = toNumber(s.total_duration_minutes, 0);
            const a = toNumber(s.avg_speed_kmh, 0);
            const x = toNumber(s.max_speed_kmh, 0);
            const c = parseInt(s.point_count ?? 0, 10);

            totalDistance += d;
            totalDuration += m;
            maxSpeed      = Math.max(maxSpeed, x);
            totalPoints   += Number.isNaN(c) ? 0 : c;
        });

        const totalHours = totalDuration / 60;
        const avgSpeed   = totalHours > 0 ? (totalDistance / totalHours) : 0;

        tripAnalyticsWrap.style.display = "flex";

        if (distEl) distEl.textContent = totalDistance.toFixed(1);
        if (durEl)  durEl.textContent  = totalDuration.toFixed(0);
        if (avgEl)  avgEl.textContent  = avgSpeed.toFixed(1);
        if (maxEl)  maxEl.textContent  = maxSpeed.toFixed(1);
        if (ptsEl)  ptsEl.textContent  = totalPoints;
    }

    function clearPlaybackLayers() {
        clearAllTrails(playbackTrailLayers);

        Object.keys(playbackMarkers).forEach(id => {
            map.removeLayer(playbackMarkers[id]);
            delete playbackMarkers[id];
        });
    }

    function updatePlaybackFrameForPrimary(index) {
        if (!primaryVehicleId) return;

        const id = String(primaryVehicleId);
        const data = playbackDataByVehicle[id];
        if (!data || !data.points || data.points.length === 0) return;

        const pts = data.points;

        const idx = Math.min(Math.max(0, index), pts.length - 1);
        const p   = pts[idx];

        const lat = toNumber(p.latitude ?? p.lat);
        const lng = toNumber(p.longitude ?? p.lng);
        if (lat === null || lng === null) return;

        // Marker
        if (!playbackMarkers[id]) {
            playbackMarkers[id] = L.marker([lat, lng], { icon: pulsingIcon }).addTo(map);
        } else {
            const from = playbackMarkers[id].getLatLng();
            animateMarker(playbackMarkers[id], from, L.latLng(lat, lng), 500);
        }

        // Trail up to current index
        const partial = pts.slice(0, idx + 1).map(pt => ({
            lat: toNumber(pt.latitude ?? pt.lat),
            lng: toNumber(pt.longitude ?? pt.lng),
            time: pt.created_at ?? pt.time
        }));
        redrawTrail(playbackTrailLayers, id, partial, true, getVehicleColor(id));

        if (tripTimeLabel && p.created_at) {
            tripTimeLabel.textContent = new Date(p.created_at).toLocaleTimeString();
        }

        if (followEnabled) {
            map.setView([lat, lng], Math.max(13, map.getZoom()));
        }
    }

    function getVehicleColor(vehicleId) {
        const ids = Object.keys(playbackDataByVehicle).sort();
        const idx = ids.indexOf(String(vehicleId));
        if (idx === -1) return "#0d6efd";
        return playbackColors[idx % playbackColors.length];
    }

    function drawComparisonVehicles() {
        // Draw full trail for all non-primary vehicles
        Object.keys(playbackDataByVehicle).forEach(id => {
            if (String(id) === String(primaryVehicleId)) return;

            const data = playbackDataByVehicle[id];
            if (!data || !Array.isArray(data.points) || data.points.length < 2) return;

            const pts = data.points.map(pt => ({
                lat: toNumber(pt.latitude ?? pt.lat),
                lng: toNumber(pt.longitude ?? pt.lng),
                time: pt.created_at ?? pt.time
            }));

            redrawTrail(playbackTrailLayers, id, pts, true, getVehicleColor(id));

            // Marker at last point for that vehicle
            const last = pts[pts.length - 1];
            if (!last) return;
            if (!playbackMarkers[id]) {
                playbackMarkers[id] = L.marker([last.lat, last.lng], { icon: pulsingIcon }).addTo(map);
            }
        });
    }

    function fitMapToPlaybackBounds() {
        const bounds = [];
        Object.values(playbackDataByVehicle).forEach(data => {
            if (!data || !Array.isArray(data.points)) return;
            data.points.forEach(pt => {
                const lat = toNumber(pt.latitude ?? pt.lat);
                const lng = toNumber(pt.longitude ?? pt.lng);
                if (lat !== null && lng !== null) {
                    bounds.push([lat, lng]);
                }
            });
        });

        if (bounds.length > 0) {
            map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    function reRenderAllPlaybackTrails() {
        // Called when switching trail mode (basic <-> heatmap)
        clearAllTrails(playbackTrailLayers);

        // Re-draw everything using current slider position / full trails
        if (!primaryVehicleId) return;
        const primaryId = String(primaryVehicleId);

        // Primary partial
        const dataPrimary = playbackDataByVehicle[primaryId];
        if (dataPrimary && Array.isArray(dataPrimary.points) && dataPrimary.points.length > 0) {
            const idx = Math.min(
                playbackIndex,
                dataPrimary.points.length - 1
            );
            const partial = dataPrimary.points.slice(0, idx + 1).map(pt => ({
                lat: toNumber(pt.latitude ?? pt.lat),
                lng: toNumber(pt.longitude ?? pt.lng),
                time: pt.created_at ?? pt.time
            }));
            redrawTrail(playbackTrailLayers, primaryId, partial, true, getVehicleColor(primaryId));
        }

        // Others full
        drawComparisonVehicles();
    }

    function startPlayback() {
        if (!primaryVehicleId) return;
        const id   = String(primaryVehicleId);
        const data = playbackDataByVehicle[id];
        if (!data || !Array.isArray(data.points) || data.points.length === 0) return;

        clearInterval(playbackTimer);

        playbackTimer = setInterval(() => {
            const pts = data.points;
            if (playbackIndex >= pts.length) {
                clearInterval(playbackTimer);
                return;
            }

            updatePlaybackFrameForPrimary(playbackIndex);

            if (sliderEl) {
                sliderEl.value = String(playbackIndex);
            }

            playbackIndex += 1;

        }, 800);
    }

    function stopPlayback() {
        clearInterval(playbackTimer);
        playbackTimer = null;
    }

    if (playBtn) {
        playBtn.addEventListener("click", () => {
            setMode("playback");
            startPlayback();
        });
    }

    if (pauseBtn) {
        pauseBtn.addEventListener("click", () => {
            stopPlayback();
        });
    }

    if (sliderEl) {
        sliderEl.addEventListener("input", (e) => {
            const val = parseInt(e.target.value || "0", 10);
            playbackIndex = val;
            stopPlayback();
            updatePlaybackFrameForPrimary(playbackIndex);
        });
    }

    if (vehicleSelect) {
        vehicleSelect.addEventListener("change", () => {
            selectedVehicleIds = getSelectedVehicleIds();
            if (loadTripBtn) {
                loadTripBtn.disabled = selectedVehicleIds.length === 0;
            }
        });
    }

    if (followToggle) {
        followToggle.addEventListener("change", () => {
            followEnabled = !!followToggle.checked;
        });
    }

    if (trailModeNormal) {
        trailModeNormal.addEventListener("click", () => {
            trailMode = "basic";
            trailModeNormal.classList.add("active");
            if (trailModeHeatmap) trailModeHeatmap.classList.remove("active");
            reRenderAllPlaybackTrails();
            // Live trails will adapt automatically on next redraw.
        });
    }

    if (trailModeHeatmap) {
        trailModeHeatmap.addEventListener("click", () => {
            trailMode = "heatmap";
            trailModeHeatmap.classList.add("active");
            if (trailModeNormal) trailModeNormal.classList.remove("active");
            reRenderAllPlaybackTrails();
        });
    }

    if (loadTripBtn) {
        loadTripBtn.addEventListener("click", () => {
            if (currentMode !== "playback") setMode("playback");

            selectedVehicleIds = getSelectedVehicleIds();
            if (!selectedVehicleIds || selectedVehicleIds.length === 0) {
                alert("Select at least one vehicle from the list.");
                return;
            }

            if (!tripDateInput || !tripDateInput.value) {
                alert("Pick a trip date.");
                return;
            }

            const date = tripDateInput.value;
            stopPlayback();
            clearPlaybackLayers();
            hideTripAnalytics();

            playbackIndex = 0;
            primaryVehicleId = String(selectedVehicleIds[0]);

            // Fetch for each selected vehicle
            const promises = selectedVehicleIds.map(id => {
                const url = `/admin/api/vehicles/${encodeURIComponent(id)}/history?date=${encodeURIComponent(date)}`;
                return fetch(url)
                    .then(r => r.json())
                    .then(data => ({ id: String(id), data }))
                    .catch(err => {
                        console.error("History error for vehicle", id, err);
                        return { id: String(id), data: null };
                    });
            });

            Promise.all(promises).then(results => {
                const summaries = [];

                results.forEach(({ id, data }) => {
                    if (!data || !Array.isArray(data.points) || data.points.length === 0) {
                        return;
                    }

                    playbackDataByVehicle[id] = {
                        points: data.points,
                        summary: data.summary || null
                    };

                    if (data.summary) {
                        summaries.push(data.summary);
                    }

                    // Prepare slider bounds for primary
                    if (id === primaryVehicleId && sliderEl) {
                        sliderEl.min      = "0";
                        sliderEl.max      = String(data.points.length - 1);
                        sliderEl.value    = "0";
                        sliderEl.disabled = false;
                    }
                });

                if (summaries.length === 0) {
                    alert("No GPS data for selected vehicles on this date.");
                    return;
                }

                updateTripAnalyticsFromSummaries(summaries);

                // Initial frame + comparison trails
                updatePlaybackFrameForPrimary(0);
                drawComparisonVehicles();
                fitMapToPlaybackBounds();

                if (playBtn)  playBtn.disabled  = false;
                if (pauseBtn) pauseBtn.disabled = false;
            });
        });
    }

    // =====================================================
    //  EXPORT: CSV / GPX / KML
    // =====================================================
    function getCurrentPlaybackPointsMerged() {
        const out = [];
        Object.entries(playbackDataByVehicle).forEach(([id, data]) => {
            if (!data || !Array.isArray(data.points)) return;

            const meta = vehicleMeta[id] || {};
            const label = meta.vehicle_number || `Vehicle ${id}`;

            data.points.forEach(pt => {
                out.push({
                    vehicle_id: id,
                    vehicle_label: label,
                    lat: toNumber(pt.latitude ?? pt.lat),
                    lng: toNumber(pt.longitude ?? pt.lng),
                    time: pt.created_at ?? pt.time ?? null
                });
            });
        });
        return out;
    }

    function exportCsv() {
        const rows = getCurrentPlaybackPointsMerged();
        if (!rows.length) {
            alert("No trip data loaded to export.");
            return;
        }

        const header = ["vehicle_id", "vehicle_label", "latitude", "longitude", "timestamp"];
        const lines  = [header.join(",")];

        rows.forEach(r => {
            const cols = [
                `"${r.vehicle_id}"`,
                `"${(r.vehicle_label || "").replace(/"/g, '""')}"`,
                r.lat != null ? r.lat.toFixed(7) : "",
                r.lng != null ? r.lng.toFixed(7) : "",
                r.time ? `"${r.time}"` : ""
            ];
            lines.push(cols.join(","));
        });

        downloadFile("trip.csv", lines.join("\n"), "text/csv;charset=utf-8;");
    }

    function exportGpx() {
        const rows = getCurrentPlaybackPointsMerged();
        if (!rows.length) {
            alert("No trip data loaded to export.");
            return;
        }

        let xml = `<?xml version="1.0" encoding="UTF-8"?>
<gpx version="1.1" creator="TeamTransport" xmlns="http://www.topografix.com/GPX/1/1">
  <trk>
    <name>TeamTransport Trip</name>
    <trkseg>
`;

        rows.forEach(r => {
            if (r.lat == null || r.lng == null) return;
            xml += `      <trkpt lat="${r.lat.toFixed(7)}" lon="${r.lng.toFixed(7)}">`;
            if (r.time) {
                const iso = new Date(r.time).toISOString();
                xml += `<time>${iso}</time>`;
            }
            xml += `</trkpt>\n`;
        });

        xml += `    </trkseg>
  </trk>
</gpx>
`;
        downloadFile("trip.gpx", xml, "application/gpx+xml;charset=utf-8;");
    }

    function exportKml() {
        const rows = getCurrentPlaybackPointsMerged();
        if (!rows.length) {
            alert("No trip data loaded to export.");
            return;
        }

        let xml = `<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>TeamTransport Trip</name>
    <Placemark>
      <name>Trip</name>
      <LineString>
        <tessellate>1</tessellate>
        <coordinates>
`;
        rows.forEach(r => {
            if (r.lat == null || r.lng == null) return;
            xml += `          ${r.lng.toFixed(7)},${r.lat.toFixed(7)},0\n`;
        });

        xml += `        </coordinates>
      </LineString>
    </Placemark>
  </Document>
</kml>
`;
        downloadFile("trip.kml", xml, "application/vnd.google-earth.kml+xml;charset=utf-8;");
    }

    if (exportCsvBtn) exportCsvBtn.addEventListener("click", exportCsv);
    if (exportGpxBtn) exportGpxBtn.addEventListener("click", exportGpx);
    if (exportKmlBtn) exportKmlBtn.addEventListener("click", exportKml);

});
