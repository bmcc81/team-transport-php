<?php
$pageTitle = "Create Geofence";
require __DIR__ . "/../../layout/header.php";
?>

<div class="container-fluid mt-3">
    <div class="row g-3">

        <!-- Breadcrumb -->
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/geofences">Geofences</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Create</li>
                </ol>
            </nav>
        </div>

        <!-- FORM COLUMN -->
        <div class="col-12 col-lg-4">

            <h4 class="mb-3"><i class="bi bi-geo-alt"></i> Create Geofence</h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="geofence-form" method="POST" action="/admin/geofences/store">

                        <!-- Hidden ID for autosave -->
                        <input type="hidden" id="geofence-id" value="">

                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" id="name" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea id="description" name="description" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select id="type" name="type" class="form-select">
                                <option value="circle" selected>Circle</option>
                                <option value="polygon">Polygon</option>
                            </select>
                        </div>

                        <!-- Circle Form -->
                        <div id="circle-section" class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-semibold mb-2">Circle Settings</h6>

                            <div class="mb-2">
                                <label class="form-label">Center Latitude</label>
                                <input id="center_lat" name="center_lat" class="form-control">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Center Longitude</label>
                                <input id="center_lng" name="center_lng" class="form-control">
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Radius (meters)</label>
                                <input id="radius_m" name="radius_m" class="form-control">
                            </div>
                        </div>

                        <!-- Polygon Form -->
                        <div id="polygon-section" class="border rounded p-3 mb-3 bg-light" style="display:none;">
                            <h6 class="fw-semibold mb-2">Polygon Points (JSON)</h6>
                            <textarea id="polygon_points" name="polygon_points" class="form-control" rows="4"></textarea>
                        </div>

                        <div class="form-check mb-2">
                            <input type="checkbox" id="applies_all" name="applies_to_all_vehicles" checked class="form-check-input">
                            <label class="form-check-label" for="applies_all">Applies to all vehicles</label>
                        </div>

                        <div class="form-check mb-3">
                            <input type="checkbox" id="active" name="active" checked class="form-check-input">
                            <label class="form-check-label" for="active">Active</label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/admin/geofences" class="btn btn-outline-secondary">Cancel</a>
                            <button class="btn btn-primary"><i class="bi bi-check-circle"></i> Save Geofence</button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <!-- MAP COLUMN -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm h-100">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-map"></i> Map Editor</span>

                    <div class="btn-group btn-group-sm">
                        <button id="btn-draw-circle" class="btn btn-outline-primary"><i class="bi bi-circle"></i></button>
                        <button id="btn-draw-polygon" class="btn btn-outline-primary"><i class="bi bi-vector-pen"></i></button>
                        <button id="btn-convert" class="btn btn-outline-primary"><i class="bi bi-arrow-left-right"></i></button>
                        <button id="btn-reset" class="btn btn-outline-danger"><i class="bi bi-x-circle"></i></button>
                    </div>
                </div>

                <div class="card-body p-0">

                    <div class="p-2 d-flex flex-wrap gap-2">
                        <button id="btn-undo" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-counterclockwise"></i> Undo</button>
                        <button id="btn-redo" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise"></i> Redo</button>

                        <button id="btn-toggle-snap" class="btn btn-outline-secondary btn-sm">Snap: ON</button>
                        <button id="btn-toggle-grid" class="btn btn-outline-secondary btn-sm">Grid: ON</button>
                    </div>

                    <div id="edit-map" class="map-flex-fill border-top"></div>
                </div>

                <div class="card-footer small text-muted">
                    Draw, edit, convert, and manage your geofence using advanced editing tools.
                </div>

            </div>
        </div>

    </div>
</div>

<!-- Required Libraries -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script src="https://cdn.jsdelivr.net/npm/leaflet-editable@1.2.0/dist/Leaflet.Editable.min.js"></script>

<script src="/assets/js/geofence-editor.js?v=2"></script>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
