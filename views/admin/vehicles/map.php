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
    const mapEl = document.getElementById('vehicle-map');
    if (!mapEl) return;

    const map = L.map('vehicle-map');
    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const markers = {};
    let boundsInitialized = false;

    function updateMarkers(vehicles) {
        const bounds = [];

        vehicles.forEach(v => {
            if (!v.latitude || !v.longitude) return;

            const lat = parseFloat(v.latitude);
            const lng = parseFloat(v.longitude);
            if (isNaN(lat) || isNaN(lng)) return;

            // Update / create marker
            if (markers[v.id]) {
                markers[v.id].setLatLng([lat, lng]);
            } else {
                const marker = L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup(
                        `<strong>${v.vehicle_number}</strong><br>` +
                        `${v.make} ${v.model}<br>` +
                        `Plate: ${v.license_plate}<br>` +
                        `Status: ${v.status}`
                    );
                markers[v.id] = marker;
            }

            bounds.push([lat, lng]);

            // Update status badge in list
            const badge = document.querySelector(`.vehicle-status-badge[data-status-for="${v.id}"]`);
            if (badge) {
                badge.textContent = v.status;
                badge.className = 'badge vehicle-status-badge ' + statusToBadgeClass(v.status);
            }
        });

        if (bounds.length && !boundsInitialized) {
            map.fitBounds(bounds, { padding: [30, 30] });
            boundsInitialized = true;
        }
    }

    function statusToBadgeClass(status) {
        switch ((status || '').toLowerCase()) {
            case 'available':   return 'bg-success';
            case 'in_service':
            case 'in service':  return 'bg-primary';
            case 'maintenance': return 'bg-warning text-dark';
            default:            return 'bg-secondary';
        }
    }

    // Click list row -> focus marker
    document.querySelectorAll('.vehicle-row').forEach(row => {
        row.addEventListener('click', () => {
            const id = row.getAttribute('data-vehicle-id');
            const marker = markers[id];

            document.querySelectorAll('.vehicle-row').forEach(r => r.classList.remove('active'));
            row.classList.add('active');

            if (marker) {
                const pos = marker.getLatLng();
                map.setView(pos, 14);
                marker.openPopup();
            }
        });
    });

    async function fetchLiveData() {
        try {
            const res = await fetch('/admin/vehicles/live', { cache: 'no-store' });
            if (!res.ok) return;
            const vehicles = await res.json();
            updateMarkers(vehicles);
        } catch (e) {
            console.error('Live tracking error:', e);
        }
    }

    // Initial load
    fetchLiveData();

    // Poll every 5 seconds
    setInterval(fetchLiveData, 5000);
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
