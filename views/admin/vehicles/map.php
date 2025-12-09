<?php
$pageTitle = "Live Fleet Map";
require __DIR__ . '/../../layout/header.php';
require __DIR__ . '/../geofences/_create_modal.php';
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
                <div class="col-12 col-xl-8 position-relative">
                    <div id="geofence-tools" class="position-absolute top-0 end-0 m-3 p-2 bg-white shadow-sm rounded border" style="z-index: 500;">
                        <div class="btn-group">
                            <button id="btn-draw-circle" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-circle"></i> Circle
                            </button>
                            <button id="btn-draw-polygon" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-vector-pen"></i> Polygon
                            </button>
                        </div>
                    </div>
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

<!-- Leaflet Draw -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

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
    window.GEOFENCES = <?= json_encode(
    $geofences,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) ?>;
    window.INITIAL_CENTER = [45.50, -73.57]; // optional
</script>
<script src="/assets/js/maps.js?v=1"></script>
