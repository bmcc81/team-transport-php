<?php
$isEdit = !empty($geofence['id']);
$pageTitle = $isEdit ? "Edit Geofence" : "New Geofence";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-0">
                        <i class="bi bi-bounding-box-circles me-2"></i>
                        <?= $isEdit ? 'Edit Geofence' : 'Create Geofence' ?>
                    </h2>
                    <small class="text-muted">
                        Click on the map to set the center and adjust radius.
                    </small>
                </div>
                <a href="/admin/geofences" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back to list
                </a>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="card shadow-sm mb-3">
                        <div class="card-body">
                            <form action="<?= $isEdit ? '/admin/geofences/update' : '/admin/geofences/store' ?>"
                                  method="post">
                                <?php if ($isEdit): ?>
                                    <input type="hidden" name="id" value="<?= (int)$geofence['id'] ?>">
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Name</label>
                                    <input type="text" name="name" class="form-control form-control-sm"
                                           value="<?= htmlspecialchars($geofence['name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" rows="3"
                                              class="form-control form-control-sm"><?= htmlspecialchars($geofence['description'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select form-select-sm">
                                        <option value="circle" <?= $geofence['type'] === 'circle' ? 'selected' : '' ?>>
                                            Circle (center + radius)
                                        </option>
                                        <!-- future: polygon -->
                                    </select>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Center Lat</label>
                                        <input type="text" id="center_lat" name="center_lat"
                                               class="form-control form-control-sm"
                                               value="<?= htmlspecialchars((string)($geofence['center_lat'] ?? '')) ?>">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Center Lng</label>
                                        <input type="text" id="center_lng" name="center_lng"
                                               class="form-control form-control-sm"
                                               value="<?= htmlspecialchars((string)($geofence['center_lng'] ?? '')) ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">
                                        Radius (meters)
                                    </label>
                                    <input type="number" id="radius_m" name="radius_m" min="50" max="50000" step="50"
                                           class="form-control form-control-sm"
                                           value="<?= htmlspecialchars((string)($geofence['radius_m'] ?? 500)) ?>">
                                </div>

                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="applies_to_all_vehicles"
                                           id="applies_to_all_vehicles"
                                           <?= !empty($geofence['applies_to_all_vehicles']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="applies_to_all_vehicles">
                                        Applies to all vehicles
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="active" id="active"
                                           <?= !empty($geofence['active']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="active">Active</label>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-save me-1"></i>
                                    <?= $isEdit ? 'Save Changes' : 'Create Geofence' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card shadow-sm">
                        <div class="card-header py-2">
                            <strong>Map</strong>
                        </div>
                        <div class="card-body p-0">
                            <div id="geofence-map" style="height: 420px;"></div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Leaflet (only if not already loaded globally) -->
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin="" />
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const latInput = document.getElementById('center_lat');
    const lngInput = document.getElementById('center_lng');
    const radiusInput = document.getElementById('radius_m');

    let lat = parseFloat(latInput.value) || 45.5019; // Montreal default
    let lng = parseFloat(lngInput.value) || -73.5674;
    let radius = parseInt(radiusInput.value, 10) || 500;

    const map = L.map('geofence-map').setView([lat, lng], 11);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19
    }).addTo(map);

    let circle = L.circle([lat, lng], {radius: radius}).addTo(map);

    function updateInputsFromCircle() {
        const center = circle.getLatLng();
        latInput.value = center.lat.toFixed(6);
        lngInput.value = center.lng.toFixed(6);
        radiusInput.value = Math.round(circle.getRadius());
    }

    map.on('click', function (e) {
        circle.setLatLng(e.latlng);
        updateInputsFromCircle();
    });

    radiusInput.addEventListener('input', function () {
        const val = parseInt(radiusInput.value, 10);
        if (!isNaN(val) && val > 0) {
            circle.setRadius(val);
        }
    });

    // Initialize inputs when loading existing
    updateInputsFromCircle();
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
