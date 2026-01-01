<?php
/** @var array $vehicles */
$pageTitle = "Create Geofence";
require __DIR__ . "/../../layout/header.php";
?>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main -->
        <main class="col-md-9 col-lg-10">

            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/geofences">Geofences</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>

            <div class="row g-3">

                <!-- FORM -->
                <div class="col-12 col-lg-4 col-xl-4">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h2 class="h5 mb-0"><i class="bi bi-geo-alt"></i> Create Geofence</h2>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form id="geofence-form" action="/admin/geofences/store" method="POST">

                                <!-- Canonical payload -->
                                <input type="hidden" id="geojson" name="geojson" value="">
                                <input type="hidden" id="rectangle_bounds" name="rectangle_bounds" value="">

                                <!-- NAME -->
                                <div class="mb-3">
                                    <label class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>

                                <!-- DESCRIPTION -->
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                                </div>

                                <!-- TYPE -->
                                <div class="mb-3">
                                    <label class="form-label">Type <span class="text-danger">*</span></label>
                                    <select id="type" name="type" class="form-select">
                                        <option value="circle">Circle</option>
                                        <option value="polygon" selected>Polygon</option>
                                        <option value="rectangle">Rectangle</option>
                                    </select>
                                    <div class="form-text">Your map editor should write GeoJSON into the hidden field.</div>
                                </div>

                                <!-- ACTIVE -->
                                <div class="form-check form-switch mb-3">
                                    <!-- IMPORTANT: name="active" (controller reads isset($_POST['active'])) -->
                                    <input class="form-check-input" type="checkbox" role="switch" id="active" name="active" checked>
                                    <label class="form-check-label" for="active">Active</label>
                                </div>

                                <!-- CIRCLE SETTINGS (legacy) -->
                                <div id="circle-section" class="border rounded bg-light p-3 mb-3" style="display:none;">
                                    <h6 class="fw-semibold mb-2">Circle Settings (legacy)</h6>

                                    <label class="form-label">Center Latitude</label>
                                    <input type="text" class="form-control mb-2" id="center_lat" name="center_lat" placeholder="45.50">

                                    <label class="form-label">Center Longitude</label>
                                    <input type="text" class="form-control mb-2" id="center_lng" name="center_lng" placeholder="-73.56">

                                    <label class="form-label">Radius (meters)</label>
                                    <input type="text" class="form-control" id="radius_m" name="radius_m" placeholder="250">
                                </div>

                                <!-- POLYGON SETTINGS (legacy) -->
                                <div id="polygon-section" class="border rounded bg-light p-3 mb-3">
                                    <h6 class="fw-semibold mb-2">Polygon Points (JSON) (legacy)</h6>
                                    <textarea id="polygon_points"
                                              name="polygon_points"
                                              class="form-control"
                                              rows="4"
                                              placeholder='[{"lat":45.50,"lng":-73.56},{"lat":45.51,"lng":-73.55},{"lat":45.49,"lng":-73.54}]'></textarea>
                                    <div class="form-text">
                                        Leave empty if your JS posts GeoJSON into <code>#geojson</code>.
                                    </div>
                                </div>

                                <!-- RECTANGLE SETTINGS (legacy) -->
                                <div id="rectangle-section" class="border rounded bg-light p-3 mb-3" style="display:none;">
                                    <h6 class="fw-semibold mb-2">Rectangle Bounds (legacy)</h6>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">North (max lat)</label>
                                            <input type="text" class="form-control" id="north_lat" name="north_lat" placeholder="45.52">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">South (min lat)</label>
                                            <input type="text" class="form-control" id="south_lat" name="south_lat" placeholder="45.48">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">East (max lng)</label>
                                            <input type="text" class="form-control" id="east_lng" name="east_lng" placeholder="-73.50">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label">West (min lng)</label>
                                            <input type="text" class="form-control" id="west_lng" name="west_lng" placeholder="-73.60">
                                        </div>
                                    </div>

                                    <div class="form-text mt-2">
                                        Leave empty if your JS posts GeoJSON into <code>#geojson</code>.
                                    </div>
                                </div>

                                <!-- VEHICLE ASSIGNMENT -->
                                <div class="mb-3">
                                    <label class="form-label">Applies to vehicles</label>

                                    <!-- IMPORTANT: do NOT include hidden input here, because controller uses isset() -->
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="applies_all"
                                               name="applies_to_all_vehicles" checked>
                                        <label class="form-check-label" for="applies_all">
                                            Applies to all vehicles
                                        </label>
                                    </div>

                                    <div id="vehicle-select-wrapper" class="mt-2" style="display:none;">
                                        <label class="form-label">Specific vehicles</label>
                                        <select id="vehicle_ids" name="vehicle_ids[]" class="form-select" multiple>
                                            <?php foreach (($vehicles ?? []) as $v): ?>
                                                <?php
                                                $vn = $v['vehicle_number'] ?? ('#' . ($v['id'] ?? ''));
                                                $lp = $v['license_plate'] ?? '';
                                                $st = $v['status'] ?? '';
                                                $label = trim($vn . ($lp ? " ({$lp})" : '') . ($st ? " — {$st}" : ''));
                                                ?>
                                                <option value="<?= (int)$v['id'] ?>">
                                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>

                                        <div class="form-text">
                                            Uncheck “Applies to all vehicles” to enable selection.
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary">
                                        <i class="bi bi-check2-circle"></i> Create
                                    </button>
                                    <a href="/admin/geofences" class="btn btn-outline-secondary">Cancel</a>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>

                <!-- MAP -->
                <div class="col-12 col-lg-8 col-xl-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-map"></i> Map Editor</span>

                            <div class="btn-group btn-group-sm">
                                <button id="btn-draw-circle" class="btn btn-outline-primary" type="button" title="Draw circle">
                                    <i class="bi bi-circle"></i>
                                </button>
                                <button id="btn-draw-polygon" class="btn btn-outline-primary" type="button" title="Draw polygon">
                                    <i class="bi bi-vector-pen"></i>
                                </button>
                                <button id="btn-convert" class="btn btn-outline-secondary" type="button" title="Convert">
                                    <i class="bi bi-arrow-left-right"></i>
                                </button>

                                <!-- Compatibility: some JS expects btn-reset, some expects btn-clear -->
                                <button id="btn-reset" class="btn btn-outline-danger" type="button" title="Reset/Clear">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <button id="btn-clear" class="btn btn-outline-danger d-none" type="button" aria-hidden="true"></button>
                            </div>
                        </div>

                        <!-- IMPORTANT: give Leaflet a real height via flex + min-height -->
                        <div class="card-body p-0 d-flex flex-column" style="min-height: 560px;">
                            <div class="p-2 d-flex flex-wrap gap-2 border-bottom">
                                <button id="btn-undo" class="btn btn-outline-secondary btn-sm" type="button">
                                    <i class="bi bi-arrow-counterclockwise"></i> Undo
                                </button>
                                <button id="btn-redo" class="btn btn-outline-secondary btn-sm" type="button">
                                    <i class="bi bi-arrow-clockwise"></i> Redo
                                </button>
                                <button id="btn-toggle-snap" class="btn btn-outline-secondary btn-sm" type="button">
                                    Snap: ON
                                </button>
                                <button id="btn-toggle-grid" class="btn btn-outline-secondary btn-sm" type="button">
                                    Grid: ON
                                </button>
                            </div>

                            <div id="edit-map" class="flex-grow-1 border-top"></div>
                        </div>

                        <div class="card-footer small text-muted">
                            Draw a shape on the map. The editor should update <code>#geojson</code>.
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>

<script>
(function () {
    // Type section toggle
    const typeEl = document.getElementById('type');
    const circle = document.getElementById('circle-section');
    const poly   = document.getElementById('polygon-section');
    const rect   = document.getElementById('rectangle-section');

    function syncTypeSections() {
        const t = (typeEl?.value || 'polygon');
        if (circle) circle.style.display = (t === 'circle') ? 'block' : 'none';
        if (poly)   poly.style.display   = (t === 'polygon') ? 'block' : 'none';
        if (rect)   rect.style.display   = (t === 'rectangle') ? 'block' : 'none';
    }
    typeEl?.addEventListener('change', syncTypeSections);
    syncTypeSections();

    // Vehicle select toggle
    const applies = document.getElementById("applies_all");
    const wrapper = document.getElementById("vehicle-select-wrapper");

    function syncVehicleSelect() {
        if (!applies || !wrapper) return;
        wrapper.style.display = applies.checked ? "none" : "block";
    }
    applies?.addEventListener("change", syncVehicleSelect);
    syncVehicleSelect();

    // Button ID compatibility: if JS binds to btn-clear, forward to btn-reset
    const btnClear = document.getElementById('btn-clear');
    const btnReset = document.getElementById('btn-reset');
    if (btnClear && btnReset) {
        btnClear.addEventListener('click', () => btnReset.click());
    }

    // Ensure Leaflet doesn’t render into 0 height if the page is slow
    window.setTimeout(() => {
        const mapEl = document.getElementById('edit-map');
        if (mapEl && mapEl.offsetHeight < 200) {
            mapEl.style.minHeight = '560px';
        }
    }, 0);
})();
</script>

<!-- Leaflet CSS can be in <head> -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<!-- Scripts: MUST be in this order -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-editable@1.2.0/src/Leaflet.Editable.js"></script>

<script src="/assets/js/geofence-core.js?v=1"></script>
<script src="/assets/js/geofence-editor.js?v=1"></script>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
