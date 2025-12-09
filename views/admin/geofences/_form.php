<?php
$isEdit = isset($geofence);
$type   = $isEdit ? $geofence['type'] : 'circle';
?>

<div class="row">
    <div class="col-12 col-xl-6">
        <!-- LEFT SIDE: FORM FIELDS -->

        <div class="mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" required class="form-control"
                   value="<?= $isEdit ? htmlspecialchars($geofence['name']) : '' ?>">
        </div>

        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control"
                      rows="2"><?= $isEdit ? htmlspecialchars($geofence['description']) : '' ?></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Type *</label>
            <select name="type" id="geo-type" class="form-select" required>
                <option value="circle"  <?= $type === 'circle' ? 'selected' : '' ?>>Circle</option>
                <option value="polygon" <?= $type === 'polygon' ? 'selected' : '' ?>>Polygon</option>
            </select>
        </div>

        <!-- Circle Fields -->
        <div id="circle-fields" class="bg-light border rounded p-3 mb-3">
            <h6 class="fw-bold">Circle Settings</h6>

            <label class="form-label mt-2">Center Latitude</label>
            <input type="number" step="0.000001" name="center_lat"
                   value="<?= $geofence['center_lat'] ?? '' ?>" class="form-control">

            <label class="form-label mt-2">Center Longitude</label>
            <input type="number" step="0.000001" name="center_lng"
                   value="<?= $geofence['center_lng'] ?? '' ?>" class="form-control">

            <label class="form-label mt-2">Radius (meters)</label>
            <input type="number" name="radius_m"
                   value="<?= $geofence['radius_m'] ?? '' ?>" class="form-control">
        </div>

        <!-- Polygon Fields -->
        <div id="polygon-fields" class="bg-light border rounded p-3 mb-3">
            <h6 class="fw-bold">Polygon Settings</h6>

            <label class="form-label">Polygon Points (JSON)</label>
            <textarea name="polygon_points" id="polygon-json" class="form-control" rows="3"
                      placeholder='[[45.50,-73.56],[45.51,-73.57]]'><?= $geofence['polygon_points'] ?? '' ?></textarea>

            <div class="text-muted small mt-1">
                Points will update automatically when drawing on the map.
            </div>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="applies_to_all_vehicles"
                   class="form-check-input"
                   <?= isset($geofence) && !$geofence['applies_to_all_vehicles'] ? '' : 'checked' ?>>
            <label class="form-check-label">Applies to all vehicles</label>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" name="active"
                   class="form-check-input"
                   <?= isset($geofence) && !$geofence['active'] ? '' : 'checked' ?>>
            <label class="form-check-label">Active</label>
        </div>

    </div>

    <!-- RIGHT SIDE: MAP PREVIEW -->
    <div class="col-12 col-xl-6">
        <h6 class="fw-bold">Map Preview</h6>
        <div id="map-preview" class="border rounded shadow-sm" style="height: 420px;"></div>

        <div class="small text-muted mt-1">
            Drag circle or draw polygon. Changes will automatically update the form.
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js"></script>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const typeSelect = document.getElementById("geo-type");
    const circleFields = document.getElementById("circle-fields");
    const polygonFields = document.getElementById("polygon-fields");

    function refreshFields() {
        const t = typeSelect.value;
        circleFields.style.display = (t === "circle") ? "block" : "none";
        polygonFields.style.display = (t === "polygon") ? "block" : "none";
    }

    typeSelect.addEventListener("change", refreshFields);
    refreshFields();

    // -------------------------
    // MAP SETUP
    // -------------------------
    const map = L.map("map-preview").setView([45.50, -73.57], 13);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 19 })
        .addTo(map);

    let drawnLayer = null;

    const drawControl = new L.Control.Draw({
        edit: { featureGroup: new L.FeatureGroup().addTo(map) },
        draw: {
            polygon: true,
            circle: true,
            marker: false,
            rectangle: false,
            polyline: false
        }
    });

    map.addControl(drawControl);

    map.on(L.Draw.Event.CREATED, (e) => {
        if (drawnLayer) map.removeLayer(drawnLayer);
        drawnLayer = e.layer;
        map.addLayer(drawnLayer);

        if (e.layerType === "circle") {
            document.querySelector("input[name='center_lat']").value = drawnLayer.getLatLng().lat;
            document.querySelector("input[name='center_lng']").value = drawnLayer.getLatLng().lng;
            document.querySelector("input[name='radius_m']").value = Math.round(drawnLayer.getRadius());
        }

        if (e.layerType === "polygon") {
            let pts = drawnLayer.getLatLngs()[0].map(p => [p.lat, p.lng]);
            document.getElementById("polygon-json").value = JSON.stringify(pts);
        }
    });
});
</script>
