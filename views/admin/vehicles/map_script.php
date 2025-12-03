<script>
// ---------------------------------------------
// MAP INITIALIZATION
// ---------------------------------------------
let map = L.map('vehicle-map').setView([45.5019, -73.5674], 11);

L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19
}).addTo(map);

// Pulsing icon
const pulsingIcon = L.divIcon({
    className: "pulsing-dot",
    iconSize: [20, 20]
});

// ---------------------------------------------
// STATE
// ---------------------------------------------
let markers = {};                // live markers
let breadcrumbLines = {};        // trail segments
let vehiclePositions = {};       // last known location per vehicle
let playbackData = [];           // full trip points
let playbackIndex = 0;
let playbackTimer = null;

let selectedVehicleId = null;
let currentMode = "live";        // live | playback

// ---------------------------------------------
// HELPERS
// ---------------------------------------------

// Great-circle distance (for speed)
function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371e3;
    const toRad = v => v * Math.PI / 180;
    const dLat = toRad(lat2 - lat1);
    const dLon = toRad(lon2 - lon1);

    const a =
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
        Math.sin(dLon/2) * Math.sin(dLon/2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
}

// Speed color mapping
function getSpeedColor(speed) {
    if (speed > 80) return "#ff0000";     // red
    if (speed > 40) return "#ff7f00";     // orange
    return "#00cc44";                     // green
}

// ---------------------------------------------
// DELUXE BREADCRUMB TRAIL
// ---------------------------------------------
function drawBreadcrumb(vehicleId, latlngs) {
    // Remove old
    if (breadcrumbLines[vehicleId]) {
        breadcrumbLines[vehicleId].forEach(seg => map.removeLayer(seg));
    }

    const segments = [];
    for (let i = 1; i < latlngs.length; i++) {
        const start = latlngs[i - 1];
        const end = latlngs[i];

        // Speed estimate (m/s -> km/h)
        const dist = haversine(start[0], start[1], end[0], end[1]);
        const speed = dist * 3.6; // assume ~1 second interval

        const opacity = 0.2 + 0.8 * (i / latlngs.length);
        const color = getSpeedColor(speed);

        const seg = L.polyline([start, end], {
            color,
            weight: 4,
            opacity,
            dashArray: "6 6",
            lineCap: "round"
        });

        segments.push(seg);
    }

    breadcrumbLines[vehicleId] = segments;
    segments.forEach(seg => seg.addTo(map));
}

// ---------------------------------------------
// LIVE MARKER MOVEMENT WITH SMOOTH MOTION
// ---------------------------------------------
function animateMarker(marker, from, to, duration = 800) {
    let startTime = performance.now();

    function step() {
        let now = performance.now();
        let progress = Math.min((now - startTime) / duration, 1);

        const lat = from.lat + (to.lat - from.lat) * progress;
        const lng = from.lng + (to.lng - from.lng) * progress;

        marker.setLatLng([lat, lng]);

        if (progress < 1) requestAnimationFrame(step);
    }

    requestAnimationFrame(step);
}

// ---------------------------------------------
// LIVE MODE — FETCH AND UPDATE
// ---------------------------------------------
function fetchLivePositions() {
    if (currentMode !== "live") return;

    fetch("/admin/api/vehicles/live")
        .then(r => r.json())
        .then(data => {

            data.forEach(v => {
                if (v.status !== "in_service") return; // only show active

                const lat = parseFloat(v.latitude);
                const lng = parseFloat(v.longitude);
                if (!lat || !lng) return;

                const pos = L.latLng(lat, lng);

                // Update marker
                if (!markers[v.id]) {
                    markers[v.id] = L.marker(pos, { icon: pulsingIcon }).addTo(map);
                    vehiclePositions[v.id] = [[lat, lng]];
                } else {
                    animateMarker(
                        markers[v.id],
                        markers[v.id].getLatLng(),
                        pos,
                        650
                    );
                }

                // Add trail point
                vehiclePositions[v.id].push([lat, lng]);
                if (vehiclePositions[v.id].length > 50) {
                    vehiclePositions[v.id].shift();
                }

                drawBreadcrumb(v.id, vehiclePositions[v.id]);
            });

        });
}

// Poll live updates
setInterval(fetchLivePositions, 5000);
fetchLivePositions();

// ---------------------------------------------
// VEHICLE LIST CLICK HANDLER
// ---------------------------------------------
document.querySelectorAll(".vehicle-row").forEach(btn => {
    btn.addEventListener("click", () => {
        selectedVehicleId = btn.dataset.vehicleId;

        if (markers[selectedVehicleId]) {
            map.setView(markers[selectedVehicleId].getLatLng(), 15);
        }

        document.getElementById("btn-load-trip").disabled = false;
    });
});

// ---------------------------------------------
// PLAYBACK MODE ACTIVATION
// ---------------------------------------------
document.getElementById("mode-live").addEventListener("click", () => {
    currentMode = "live";
    clearInterval(playbackTimer);
    document.getElementById("playback-controls").style.display = "none";
    document.getElementById("live-label").style.display = "inline";
});

document.getElementById("mode-playback").addEventListener("click", () => {
    currentMode = "playback";
    clearInterval(playbackTimer);
    document.getElementById("playback-controls").style.display = "block";
    document.getElementById("live-label").style.display = "none";
});

// ---------------------------------------------
// LOAD TRIP DATA
// ---------------------------------------------
document.getElementById("btn-load-trip").addEventListener("click", () => {
    if (!selectedVehicleId) return;

    const date = document.getElementById("trip-date").value;
    if (!date) return alert("Pick a date");

    fetch(`/admin/api/vehicles/${selectedVehicleId}/history?date=${date}`)
        .then(r => r.json())
        .then(points => {
            if (!points.length) {
                alert("No GPS data for this date.");
                return;
            }

            playbackData = points.map(p => ({
                lat: parseFloat(p.latitude),
                lng: parseFloat(p.longitude),
                time: p.created_at
            }));

            playbackIndex = 0;

            document.getElementById("trip-slider").max = playbackData.length - 1;
            document.getElementById("trip-slider").disabled = false;
            document.getElementById("btn-play").disabled = false;
            document.getElementById("btn-pause").disabled = false;

            startPlayback();
        });
});

// ---------------------------------------------
// PLAYBACK CONTROLS
// ---------------------------------------------
function startPlayback() {
    clearInterval(playbackTimer);

    playbackTimer = setInterval(() => {
        updatePlaybackFrame(playbackIndex);
        playbackIndex++;

        if (playbackIndex >= playbackData.length) {
            clearInterval(playbackTimer);
        }

        document.getElementById("trip-slider").value = playbackIndex;
    }, 800);
}

function updatePlaybackFrame(i) {
    if (!markers[selectedVehicleId]) {
        markers[selectedVehicleId] = L.marker([0, 0], { icon: pulsingIcon }).addTo(map);
    }

    const p = playbackData[i];
    animateMarker(
        markers[selectedVehicleId],
        markers[selectedVehicleId].getLatLng(),
        L.latLng(p.lat, p.lng),
        500
    );

    // Update label
    document.getElementById("trip-time-label").innerText =
        new Date(p.time).toLocaleTimeString();

    // Build playback trail
    const pts = playbackData.slice(0, i + 1).map(p => [p.lat, p.lng]);
    drawBreadcrumb(selectedVehicleId, pts);
}

// Pause
document.getElementById("btn-pause").addEventListener("click", () => {
    clearInterval(playbackTimer);
});

// Slider scrubbing
document.getElementById("trip-slider").addEventListener("input", (e) => {
    playbackIndex = parseInt(e.target.value);
    updatePlaybackFrame(playbackIndex);
});
</script>
