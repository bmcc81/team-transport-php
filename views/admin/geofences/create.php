<?php
$pageTitle = "Create Geofence";
require __DIR__ . "/../../layout/header.php";
?>

<div class="container-fluid">
    <div class="row g-3 py-2">

        <!-- LEFT COLUMN – FORM -->
        <div class="col-12 col-lg-4">
            <h4 class="mb-3"><i class="bi bi-geo-alt"></i> Create Geofence</h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="geofence-form" action="/admin/geofences/store" method="POST">

                        <input type="hidden" id="geofence-id" value="">

                        <!-- NAME -->
                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <!-- DESCRIPTION -->
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                        </div>

                        <!-- TYPE -->
                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select id="type" name="type" class="form-select">
                                <option value="circle">Circle</option>
                                <option value="polygon">Polygon</option>
                            </select>
                        </div>

                        <!-- CIRCLE SETTINGS -->
                        <div id="circle-section" class="border rounded bg-light p-3 mb-3">
                            <h6 class="fw-semibold mb-2">Circle Settings</h6>

                            <label class="form-label">Center Latitude</label>
                            <input type="text" class="form-control mb-2" id="center_lat" name="center_lat">

                            <label class="form-label">Center Longitude</label>
                            <input type="text" class="form-control mb-2" id="center_lng" name="center_lng">

                            <label class="form-label">Radius (meters)</label>
                            <input type="text" class="form-control" id="radius_m" name="radius_m">
                        </div>

                        <!-- POLYGON SETTINGS -->
                        <div id="polygon-section" class="border rounded bg-light p-3 mb-3" style="display:none;">
                            <h6 class="fw-semibold mb-2">Polygon Points (JSON)</h6>
                            <textarea id="polygon_points"
                                      name="polygon_points"
                                      class="form-control"
                                      rows="4"></textarea>
                        </div>

                        <!-- VEHICLE ASSIGNMENT -->
                        <div class="mb-3">
                            <label class="form-label">Applies to vehicles</label>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="applies_all"
                                       name="applies_to_all_vehicles" checked>
                                <label class="form-check-label" for="applies_all">
                                    Applies to all vehicles
                                </label>
                            </div>

                            <div id="vehicle-select-wrapper" class="mt-2" style="display:none;">
                                <label class="form-label">Specific vehicles (optional)</label>

                                <select id="vehicle_ids" name="vehicle_ids[]" class="form-select" multiple>
                                    <?php foreach ($vehicles as $v): ?>
                                        <option value="<?= $v['id'] ?>">
                                            <?= htmlspecialchars($v['vehicle_number'] . ' — ' . $v['make'] . ' ' . $v['model'] . ' (' . $v['license_plate'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <div class="form-text">
                                    Leave blank to apply to all vehicles, or uncheck the box above to enable selection.
                                </div>
                            </div>
                        </div>

                        <!-- ACTIVE -->
                        <div class="form-check mb-3">
                            <input type="checkbox" id="active" name="active" checked class="form-check-input">
                            <label class="form-check-label" for="active">Active</label>
                        </div>

                        <!-- BUTTONS -->
                        <div class="d-flex justify-content-between mt-3">
                            <a href="/admin/geofences" class="btn btn-outline-secondary">Cancel</a>
                            <button class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Geofence
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN – MAP -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">

                    <span><i class="bi bi-map"></i> Map Editor</span>

                    <div class="btn-group btn-group-sm">
                        <button id="btn-draw-circle" class="btn btn-outline-primary" type="button">
                            <i class="bi bi-circle"></i>
                        </button>
                        <button id="btn-draw-polygon" class="btn btn-outline-primary" type="button">
                            <i class="bi bi-vector-pen"></i>
                        </button>
                        <button id="btn-convert" class="btn btn-outline-secondary" type="button">
                            <i class="bi bi-arrow-left-right"></i>
                        </button>
                        <button id="btn-reset" class="btn btn-outline-danger" type="button">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">

                    <!-- EDITOR BUTTON BAR -->
                    <div class="p-2 d-flex flex-wrap gap-2">
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

                    <!-- MAP -->
                    <div id="edit-map" class="map-flex-fill border-top"></div>
                </div>

                <div class="card-footer small text-muted">
                    Draw, edit, convert, and manage your geofence using advanced editing tools.
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.getElementById("applies_all").addEventListener("change", function () {
    document.getElementById("vehicle-select-wrapper").style.display =
        this.checked ? "none" : "block";
});
</script>

<!-- Leaflet core -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet.Editable (WORKING VERSION) -->
<script src="https://unpkg.com/leaflet-editable@1.2.0/src/Leaflet.Editable.js"></script>

<!-- Your geofence editor engine -->
<script src="/assets/js/geofence-core.js?v=1"></script>
<script src="/assets/js/geofence-editor.js?v=1"></script>

<?php require __DIR__ . "/../../layout/footer.php"; ?>