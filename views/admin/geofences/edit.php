<?php
$pageTitle = "Edit Geofence";
require __DIR__ . "/../../layout/header.php";
?>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- FORM COLUMN -->
        <div class="col-12 col-lg-4">

            <h4 class="mb-3"><i class="bi bi-geo-alt"></i> Edit Geofence</h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="geofence-form" method="POST" action="/admin/geofences/update">

                        <input type="hidden" id="geofence-id" name="id" value="<?= $geofence['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input id="name" name="name" class="form-control" value="<?= $geofence['name'] ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control"><?= $geofence['description'] ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select id="type" name="type" class="form-select">
                                <option value="circle" <?= $geofence['type']==='circle'?'selected':'' ?>>Circle</option>
                                <option value="polygon" <?= $geofence['type']==='polygon'?'selected':'' ?>>Polygon</option>
                            </select>
                        </div>

                        <div id="circle-section" class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-semibold mb-2">Circle Settings</h6>

                            <div class="mb-2">
                                <label class="form-label">Center Latitude</label>
                                <input id="center_lat" name="center_lat" class="form-control" value="<?= $geofence['center_lat'] ?>">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Center Longitude</label>
                                <input id="center_lng" name="center_lng" class="form-control" value="<?= $geofence['center_lng'] ?>">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Radius (meters)</label>
                                <input id="radius_m" name="radius_m" class="form-control" value="<?= $geofence['radius_m'] ?>">
                            </div>
                        </div>

                        <div id="polygon-section" class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-semibold mb-2">Polygon Points (JSON)</h6>
                            <textarea id="polygon_points" name="polygon_points" class="form-control" rows="4"><?= $geofence['polygon_points'] ?></textarea>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" id="applies_all" name="applies_to_all_vehicles" class="form-check-input"
                                   <?= $geofence['applies_to_all_vehicles']?'checked':'' ?>>
                            <label class="form-check-label">Applies to all vehicles</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" id="active" name="active" class="form-check-input"
                                   <?= $geofence['active']?'checked':'' ?>>
                            <label class="form-check-label">Active</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/admin/geofences" class="btn btn-outline-secondary">Cancel</a>
                            <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Changes</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- MAP COLUMN -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-map"></i> Edit Shape</span>
                    <div class="btn-group btn-group-sm">
                        <button id="btn-draw-circle" class="btn btn-outline-primary"><i class="bi bi-circle"></i></button>
                        <button id="btn-draw-polygon" class="btn btn-outline-primary"><i class="bi bi-vector-pen"></i></button>
                        <button id="btn-convert" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right"></i></button>
                        <button id="btn-reset" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="p-2 d-flex flex-wrap gap-2">
                        <button id="btn-undo" class="btn btn-outline-secondary btn-sm">Undo</button>
                        <button id="btn-redo" class="btn btn-outline-secondary btn-sm">Redo</button>
                        <button id="btn-toggle-snap" class="btn btn-outline-secondary btn-sm">Snap: ON</button>
                        <button id="btn-toggle-grid" class="btn btn-outline-secondary btn-sm">Grid: ON</button>
                    </div>

                    <div id="edit-map" class="map-flex-fill border-top" class="border-top"></div>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Leaflet Dependencies -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script src="https://cdn.jsdelivr.net/npm/leaflet-editable@1.2.0/dist/Leaflet.Editable.min.js"></script>

<script src="/assets/js/geofence-editor.js?v=2"></script>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
