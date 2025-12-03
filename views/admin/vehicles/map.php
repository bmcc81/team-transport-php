<?php
$pageTitle = "Vehicle Map";
require __DIR__ . '/../../layout/header.php';
?>
<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Live Vehicle Map</h2>

                <a href="/admin/vehicles" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-list-ul"></i> Vehicle List
                </a>
            </div>

            <div class="row g-3">

                <!-- Vehicle list -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Vehicles
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="vehicle-list" style="max-height: 70vh; overflow-y: auto;">
                                <?php if (!empty($vehicles ?? [])): ?>
                                    <?php foreach ($vehicles as $v): ?>
                                        <?php if (!in_array(strtolower($v['status']), ['in_service', 'in service'])) continue; ?>

                                        <button
                                            type="button"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start vehicle-row"
                                            data-vehicle-id="<?= $v['id'] ?>"
                                        >
                                            <div>
                                                <div class="fw-semibold">
                                                    <?= htmlspecialchars($v['vehicle_number']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($v['make'] . ' ' . $v['model']) ?>
                                                    &bullet;
                                                    <?= htmlspecialchars($v['license_plate']) ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-secondary vehicle-status-badge"
                                                  data-status-for="<?= $v['id'] ?>">
                                                <?= htmlspecialchars($v['status']) ?>
                                            </span>
                                        </button>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="p-3 text-muted">
                                        No vehicles found.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map -->
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                            <span>Map</span>
                            <small class="text-muted">Auto-updating every 5 seconds</small>
                        </div>
                        <div class="card-body p-0">
                            <div id="vehicle-map" style="height: 70vh; width: 100%;"></div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    crossorigin=""
/>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    crossorigin=""
></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    // Setup map
    const map = L.map('vehicle-map');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    // Store markers and trails
    const markers = {};
    const trails = {};

    // Pulsing icon for live vehicles
    const pulsingIcon = L.divIcon({
        className: 'pulsing-dot',
        iconSize: [18, 18]
    });

    // Distance function (Haversine)
    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371e3; // meters
        const toRad = deg => deg * Math.PI / 180;
        const dLat = toRad(lat2 - lat1);
        const dLon = toRad(lon2 - lon1);

        const a =
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) *
            Math.sin(dLon/2) * Math.sin(dLon/2);

        return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
    }

    // Determine segment color from speed
    function getSpeedColor(speed) {
        if (speed > 60) return '#ff0000';   // fast = red
        if (speed > 30) return '#ff8800';   // medium = orange
        return '#00cc44';                   // slow = green
    }

    // Build deluxe trail polyline (fading, dashed, speed-colored)
    function buildDeluxeTrail(points) {
        const segments = [];
        const n = points.length;

        for (let i = 1; i < n; i++) {
            const p1 = points[i - 1];
            const p2 = points[i];

            const lat1 = parseFloat(p1.latitude);
            const lon1 = parseFloat(p1.longitude);
            const lat2 = parseFloat(p2.latitude);
            const lon2 = parseFloat(p2.longitude);

            if (isNaN(lat1) || isNaN(lon1) || isNaN(lat2) || isNaN(lon2)) continue;

            // Approx speed
            const dt = (new Date(p2.created_at) - new Date(p1.created_at)) / 1000;
            const dist = haversine(lat1, lon1, lat2, lon2);
            const speed = dt > 0 ? (dist / dt) * 3.6 : 0; // km/h

            // Opacity fades older → newer
            const opacity = 0.2 + 0.8 * (i / n);

            // Create segment
            const segment = L.polyline([[lat1, lon1], [lat2, lon2]], {
                color: getSpeedColor(speed),
                weight: 4,
                opacity,
                dashArray: "6 8",
                lineCap: "round"
            });

            segments.push(segment);
        }

        return segments;
    }

    async function updateBreadcrumbs(vehicleId) {
        const res = await fetch(`/admin/vehicles/${vehicleId}/breadcrumbs`);
        const points = await res.json();

        if (!Array.isArray(points) || points.length < 2) return;

        const segments = buildDeluxeTrail(points);

        // Remove old segments
        if (trails[vehicleId]) {
            trails[vehicleId].forEach(seg => map.removeLayer(seg));
        }

        trails[vehicleId] = segments;

        // Add new segments
        segments.forEach(seg => seg.addTo(map));
    }

    // Smooth movement animation
    function smoothMoveMarker(marker, newLat, newLng) {
        const duration = 700;
        const frames = 20;
        const delay = duration / frames;

        const start = marker.getLatLng();
        const dLat = (newLat - start.lat) / frames;
        const dLng = (newLng - start.lng) / frames;

        let frame = 0;

        function move() {
            frame++;
            marker.setLatLng([start.lat + dLat * frame, start.lng + dLng * frame]);
            if (frame < frames) requestAnimationFrame(move);
        }
        move();
    }

    // Marker update logic
    function updateMarkers(vehicles) {
        vehicles.forEach(v => {

            if (!v.latitude || !v.longitude) return;

            const lat = parseFloat(v.latitude);
            const lng = parseFloat(v.longitude);

            if (!markers[v.id]) {
                // First creation
                const marker = L.marker([lat, lng], { icon: pulsingIcon }).addTo(map);
                markers[v.id] = marker;
            } else {
                // Smooth movement
                smoothMoveMarker(markers[v.id], lat, lng);
            }

            // Update deluxe trail
            updateBreadcrumbs(v.id);
        });
    }

    // Fetch in-service vehicles
    async function fetchLiveData() {
        const res = await fetch('/admin/vehicles/live');
        if (!res.ok) return;
        const vehicles = await res.json();

        updateMarkers(vehicles);

        // Fit map only on first load
        if (!window._fitDone && vehicles.length) {
            const bounds = vehicles
                .filter(v => v.latitude && v.longitude)
                .map(v => [parseFloat(v.latitude), parseFloat(v.longitude)]);
            if (bounds.length) {
                map.fitBounds(bounds, { padding: [40, 40] });
                window._fitDone = true;
            }
        }
    }

    // Initial load
    fetchLiveData();

    // Auto-poll every 5 seconds
    setInterval(fetchLiveData, 5000);
});

</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
