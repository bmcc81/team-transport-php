<?php
/** @var array $geofence */
$pageTitle = "Edit Geofence";
require __DIR__ . "/../../layout/header.php";

// Ensure keys exist
$g = $geofence ?? [];
?>

<div class="container-fluid mt-3">
    <div class="row g-3">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/geofences">Geofences</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit</li>
                </ol>
            </nav>
        </div>

        <!-- FORM -->
        <div class="col-12 col-lg-5 col-xl-4">
            <h4 class="mb-3">
                <i class="bi bi-pencil-square"></i> Edit Geofence
            </h4>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/admin/geofences/update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars((string)$g['id']) ?>">

                        <div class="mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($g['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($g['description'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Type *</label>
                            <select name="type" id="geo-type" class="form-select" required>
                                <option value="circle"  <?= ($g['type'] ?? '') === 'circle' ? 'selected' : '' ?>>Circle</option>
                                <option value="polygon" <?= ($g['type'] ?? '') === 'polygon' ? 'selected' : '' ?>>Polygon</option>
                            </select>
                        </div>

                        <!-- Circle -->
                        <div id="circle-section" class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-semibold mb-2">Circle Settings</h6>

                            <div class="mb-2">
                                <label class="form-label">Center Latitude</label>
                                <input type="text" name="center_lat" id="center_lat" class="form-control"
                                       value="<?= htmlspecialchars((string)($g['center_lat'] ?? '')) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Center Longitude</label>
                                <input type="text" name="center_lng" id="center_lng" class="form-control"
                                       value="<?= htmlspecialchars((string)($g['center_lng'] ?? '')) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label">Radius (meters)</label>
                                <input type="text" name="radius_m" id="radius_m" class="form-control"
                                       value="<?= htmlspecialchars((string)($g['radius_m'] ?? '')) ?>">
                            </div>
                            <div class="form-text">
                                Drag or resize the circle on the map to update these fields.
                            </div>
                        </div>

                        <!-- Polygon -->
                        <div id="polygon-section" class="border rounded p-3 mb-3 bg-light">
                            <h6 class="fw-semibold mb-2">Polygon Settings</h6>
                            <div class="mb-2">
                                <label class="form-label">Polygon Points (JSON)</label>
                                <textarea name="polygon_points" id="polygon_points" class="form-control" rows="4"><?= htmlspecialchars((string)($g['polygon_points'] ?? '')) ?></textarea>
                            </div>
                            <div class="form-text">
                                Draw or edit the polygon on the map; coordinates will fill here automatically.
                            </div>
                        </div>

                        <div class="mb-2 form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="applies_to_all_vehicles" id="applies_all"
                                   <?= !empty($g['applies_to_all_vehicles']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="applies_all">
                                Applies to all vehicles
                            </label>
                        </div>

                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="active" id="active"
                                   <?= !empty($g['active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="active">
                                Active
                            </label>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="/admin/geofences" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                            <button class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MAP -->
        <div class="col-12 col-lg-7 col-xl-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-map"></i> Map Preview</span>
                    <div class="btn-group btn-group-sm">
                        <button id="btn-draw-circle" class="btn btn-outline-primary">
                            <i class="bi bi-circle"></i> Circle
                        </button>
                        <button id="btn-draw-polygon" class="btn btn-outline-primary">
                            <i class="bi bi-vector-pen"></i> Polygon
                        </button>
                        <button id="btn-reset-shape" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Reset
                        </button>
                    </div>
                </div>
                <div class="card-body p-0" style="height: 520px;">
                    <div id="geofence-map" style="height: 100%; width: 100%;"></div>
                </div>
                <div class="card-footer small text-muted">
                    Drag or resize the shape. Changes sync automatically with the form.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet + Draw -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script src="/assets/js/geofence-editor.js?v=1"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const initialData = {
        type: "<?= htmlspecialchars($g['type'] ?? 'circle') ?>",
        center_lat: "<?= htmlspecialchars((string)($g['center_lat'] ?? '')) ?>",
        center_lng: "<?= htmlspecialchars((string)($g['center_lng'] ?? '')) ?>",
        radius_m: "<?= htmlspecialchars((string)($g['radius_m'] ?? '')) ?>",
        polygon_points: <?= json_encode($g['polygon_points'] ?? '') ?>
    };

    GeofenceEditor.init({
        mapId: "geofence-map",
        defaultCenter: [45.50, -73.57],
        defaultZoom: 13,
        typeSelect: document.getElementById("geo-type"),
        circle: {
            section: document.getElementById("circle-section"),
            latInput: document.getElementById("center_lat"),
            lngInput: document.getElementById("center_lng"),
            radiusInput: document.getElementById("radius_m")
        },
        polygon: {
            section: document.getElementById("polygon-section"),
            pointsInput: document.getElementById("polygon_points")
        },
        buttons: {
            circle: document.getElementById("btn-draw-circle"),
            polygon: document.getElementById("btn-draw-polygon"),
            reset: document.getElementById("btn-reset-shape")
        },
        initialData: initialData
    });
});
</script>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
