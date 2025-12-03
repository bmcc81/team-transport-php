<?php
$pageTitle = "Vehicle Live Map & Playback";
require __DIR__ . '/../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main -->
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

            <h2 class="h4 mb-3">
                <i class="bi bi-map"></i> Live Vehicle Tracking
            </h2>

            <!-- =============================== -->
            <!-- MODE SWITCH -->
            <!-- =============================== -->
            <div class="d-flex align-items-center mb-3">

                <button id="mode-live" class="btn btn-sm btn-outline-success me-2">
                    Live Mode
                </button>

                <button id="mode-playback" class="btn btn-sm btn-outline-primary me-3">
                    Playback Mode
                </button>

                <span id="live-label" class="badge bg-success">LIVE</span>

            </div>

            <!-- =============================== -->
            <!-- PLAYBACK PANEL -->
            <!-- =============================== -->
            <div id="playback-controls" style="display:none;" class="card p-3 mb-4 shadow-sm">

                <div class="row g-3">

                    <!-- Date Input -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Trip Date</label>
                        <input type="date" id="trip-date" class="form-control">
                    </div>

                    <!-- Load Button -->
                    <div class="col-md-4 d-flex align-items-end">
                        <button id="btn-load-trip"
                                class="btn btn-primary w-100"
                                disabled>
                            <i class="bi bi-cloud-download"></i>
                            Load Trip
                        </button>
                    </div>

                    <!-- Current Time -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Time</label>
                        <div id="trip-time-label" class="form-control bg-light">
                            —
                        </div>
                    </div>

                </div>

                <!-- Slider -->
                <div class="mt-3">
                    <input type="range"
                           id="trip-slider"
                           class="form-range"
                           min="0"
                           value="0"
                           disabled>
                </div>

                <!-- Playback controls -->
                <div class="d-flex gap-2 mt-2">
                    <button id="btn-play" class="btn btn-success btn-sm" disabled>
                        <i class="bi bi-play-fill"></i> Play
                    </button>

                    <button id="btn-pause" class="btn btn-secondary btn-sm" disabled>
                        <i class="bi bi-pause-fill"></i> Pause
                    </button>
                </div>

                <!-- Trip Analytics -->
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

            <!-- =============================== -->
            <!-- MAP -->
            <!-- =============================== -->
            <div id="vehicle-map"
                 class="rounded border shadow-sm"
                 style="height: 580px;">
            </div>

        </main>
    </div>
</div>

<!-- LEAFLET CSS -->
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>

<!-- LEAFLET JS -->
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
></script>

<!-- MAP SCRIPT -->
<script src="/assets/js/map_script.js"></script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
