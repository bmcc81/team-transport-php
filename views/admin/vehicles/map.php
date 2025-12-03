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
                            <div class="list-group list-group-flush" style="max-height: 70vh; overflow-y: auto;">
                                <?php if (!empty($vehicles)): ?>
                                    <?php foreach ($vehicles as $v): ?>
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
                                            <span>
                                                <?php if ($v['status'] === 'available'): ?>
                                                    <span class="badge bg-success">Avail</span>
                                                <?php elseif ($v['status'] === 'in_service'): ?>
                                                    <span class="badge bg-primary">In Service</span>
                                                <?php elseif ($v['status'] === 'maintenance'): ?>
                                                    <span class="badge bg-warning text-dark">Maint</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?= htmlspecialchars($v['status']) ?>
                                                    </span>
                                                <?php endif; ?>
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
                            <small class="text-muted">Zoom & click markers to see vehicles</small>
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

<?php require __DIR__ . '/../../layout/footer.php'; ?>

<!-- Leaflet CSS & JS (CDN) -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Init Leaflet map
    const map = L.map('vehicle-map');
    const tiles = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    // PHP → JS data
    const vehicles = <?=
        json_encode(array_map(function ($v) {
            return [
                'id'        => (int)$v['id'],
                'number'    => $v['vehicle_number'],
                'make'      => $v['make'],
                'model'     => $v['model'],
                'plate'     => $v['license_plate'],
                'status'    => $v['status'],
                'lat'       => isset($v['latitude']) ? (float)$v['latitude'] : null,
                'lng'       => isset($v['longitude']) ? (float)$v['longitude'] : null,
            ];
        }, $vehicles), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    ?>;

    const markers = {};
    const bounds = [];

    vehicles.forEach(v => {
        if (v.lat === null || v.lng === null) {
            return; // skip vehicles without coordinates
        }

        const marker = L.marker([v.lat, v.lng])
            .addTo(map)
            .bindPopup(
                `<strong>${v.number}</strong><br>` +
                `${v.make} ${v.model}<br>` +
                `Plate: ${v.plate}<br>` +
                `Status: ${v.status}`
            );

        markers[v.id] = marker;
        bounds.push([v.lat, v.lng]);
    });

    if (bounds.length > 0) {
        map.fitBounds(bounds, {padding: [30, 30]});
    } else {
        // Default center (e.g., Montreal area)
        map.setView([45.5019, -73.5674], 10);
    }

    // Highlight marker when clicking vehicle row
    document.querySelectorAll('.vehicle-row').forEach(row => {
        row.addEventListener('click', () => {
            const id = parseInt(row.getAttribute('data-vehicle-id'), 10);
            const marker = markers[id];

            document.querySelectorAll('.vehicle-row').forEach(r => r.classList.remove('active'));
            row.classList.add('active');

            if (marker) {
                map.setView(marker.getLatLng(), 13);
                marker.openPopup();
            }
        });
    });
});
</script>
