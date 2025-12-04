<?php
$pageTitle = "Vehicle Live Map & Playback";
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

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Vehicle Map
                    </li>
                </ol>
            </nav>

            <!-- Title + mode switch -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h2 class="h4 mb-0">
                    <i class="bi bi-map"></i> Live Vehicle Tracking
                </h2>

                <div class="d-flex align-items-center gap-2">
                    <button id="mode-live" class="btn btn-sm btn-outline-success">
                        Live Mode
                    </button>
                    <button id="mode-playback" class="btn btn-sm btn-outline-primary">
                        Playback Mode
                    </button>
                    <span id="live-label" class="badge bg-success">
                        LIVE
                    </span>
                </div>
            </div>

            <div class="row g-3">
                <!-- Controls column -->
                <div class="col-12 col-lg-4">

                    <!-- Vehicle selection + follow + trail & export -->
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light fw-semibold">
                            Vehicles & View Options
                        </div>
                        <div class="card-body">

                            <!-- Vehicle multi-select (populated by JS from /admin/api/vehicles/live) -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">Vehicle(s)</label>
                                <select id="vehicle-select"
                                        class="form-select"
                                        multiple
                                        size="5">
                                    <!-- JS will inject options here -->
                                </select>
                                <small class="form-text text-muted">
                                    Use Ctrl / Cmd + click to select multiple vehicles for comparison.
                                </small>
                            </div>

                            <!-- Follow toggle -->
                            <div class="form-check mb-3">
                                <input class="form-check-input"
                                       type="checkbox"
                                       id="follow-toggle">
                                <label class="form-check-label" for="follow-toggle">
                                    Auto-center / Follow selected vehicle
                                </label>
                            </div>

                            <!-- Trail mode buttons -->
                            <div class="mb-3">
                                <label class="form-label fw-bold d-block">Trail Mode</label>
                                <div class="btn-group w-100" role="group">
                                    <button id="trail-mode-normal"
                                            type="button"
                                            class="btn btn-outline-secondary btn-sm active">
                                        Basic Trail
                                    </button>
                                    <button id="trail-mode-heatmap"
                                            type="button"
                                            class="btn btn-outline-danger btn-sm">
                                        Speed Heatmap
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    Heatmap colors segments by speed (green → yellow → orange → red).
                                </small>
                            </div>

                            <!-- Export buttons -->
                            <div class="mb-2">
                                <label class="form-label fw-bold d-block">Export Trip</label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button id="export-csv" type="button" class="btn btn-outline-secondary">
                                        CSV
                                    </button>
                                    <button id="export-gpx" type="button" class="btn btn-outline-secondary">
                                        GPX
                                    </button>
                                    <button id="export-kml" type="button" class="btn btn-outline-secondary">
                                        KML
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    Export the currently loaded playback trip data.
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Playback controls -->
                    <div id="playback-controls" class="card shadow-sm mb-3" style="display:none;">
                        <div class="card-header bg-light fw-semibold">
                            Playback Controls
                        </div>
                        <div class="card-body">

                            <div class="row g-3">

                                <!-- Trip date -->
                                <div class="col-12">
                                    <label for="trip-date" class="form-label fw-bold">Trip Date</label>
                                    <input type="date" id="trip-date" class="form-control">
                                </div>

                                <!-- Load trip button -->
                                <div class="col-12">
                                    <button id="btn-load-trip"
                                            class="btn btn-primary w-100"
                                            disabled>
                                        <i class="bi bi-cloud-download"></i>
                                        Load Trip for Selected Vehicles
                                    </button>
                                    <small class="form-text text-muted">
                                        Choose at least one vehicle and a date, then click Load Trip.
                                    </small>
                                </div>

                                <!-- Slider + time label -->
                                <div class="col-12">
                                    <label class="form-label fw-bold">Timeline</label>
                                    <input type="range"
                                           id="trip-slider"
                                           class="form-range"
                                           min="0"
                                           value="0"
                                           disabled>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span>Start</span>
                                        <span id="trip-time-label">—</span>
                                        <span>End</span>
                                    </div>
                                </div>

                                <!-- Play / Pause -->
                                <div class="col-12 d-flex gap-2">
                                    <button id="btn-play"
                                            type="button"
                                            class="btn btn-success btn-sm flex-grow-1"
                                            disabled>
                                        <i class="bi bi-play-fill"></i> Play
                                    </button>
                                    <button id="btn-pause"
                                            type="button"
                                            class="btn btn-secondary btn-sm flex-grow-1"
                                            disabled>
                                        <i class="bi bi-pause-fill"></i> Pause
                                    </button>
                                </div>

                            </div>

                            <!-- Trip analytics summary -->
                            <hr class="my-3">

                            <div id="trip-analytics"
                                 class="row g-2 small text-muted"
                                 style="display:none;">

                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <div class="fw-semibold">Distance</div>
                                        <div><span id="trip-distance">0.0</span> km</div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <div class="fw-semibold">Duration</div>
                                        <div><span id="trip-duration">0</span> min</div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <div class="fw-semibold">Avg Speed</div>
                                        <div><span id="trip-avg-speed">0.0</span> km/h</div>
                                    </div>
                                </div>

                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100 bg-light">
                                        <div class="fw-semibold">Max Speed</div>
                                        <div><span id="trip-max-speed">0.0</span> km/h</div>
                                    </div>
                                </div>

                                <div class="col-12 mt-2">
                                    <span class="fw-semibold">GPS Points:</span>
                                    <span id="trip-points">0</span>
                                </div>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Map column -->
                <div class="col-12 col-lg-8">
                    <div id="vehicle-map"
                         class="rounded border shadow-sm"
                         style="height: 580px;">
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Leaflet CSS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>

<!-- Leaflet JS -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>

<!-- Map logic -->
<script src="/assets/js/map_script.js"></script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
