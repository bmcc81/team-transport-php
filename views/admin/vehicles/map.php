<?php
$pageTitle = "Live Fleet Map";
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
                <h2 class="h4 mb-0">
                    <i class="bi bi-map me-2"></i> Live Fleet Map
                </h2>

                <div class="d-flex align-items-center gap-2">
                    <span id="ws-status-pill" class="badge text-bg-secondary connection-pill">
                        WS: Connecting…
                    </span>
                    <button id="btn-fit-all" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrows-fullscreen"></i> Fit All
                    </button>
                </div>
            </div>

            <div class="row">
                <!-- Map -->
                <div class="col-lg-8 mb-3">
                    <div id="live-map" class="rounded border" style="height: 550px;"></div>
                </div>

                <!-- Sidebar table -->
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span class="fw-semibold">
                                <i class="bi bi-table me-1"></i> Active Vehicles
                            </span>
                            <small class="text-muted" id="last-update-label">
                                Last update: —
                            </small>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Position</th>
                                            <th>Speed</th>
                                            <th>Time (UTC)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="active-vehicles-body">
                                        <!-- rows added by JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Leaflet CSS/JS -->
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

<!-- Live Telemetry JS -->
<script src="/assets/js/telemetry-live-map.js"></script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
