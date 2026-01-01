<div class="modal fade" id="geofenceCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-geo-alt"></i> Create Geofence
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <form id="geofence-create-form">
                    <!-- Preferred canonical payload -->
                    <input type="hidden" id="geojson-modal" name="geojson" value="">
                    <input type="hidden" id="rectangle-bounds-modal" name="rectangle_bounds" value="">

                    <div class="row g-3">

                        <div class="col-12 col-lg-5">

                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input id="geo-name" name="name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea id="geo-desc" name="description" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type *</label>
                                <select id="geo-type" name="type" class="form-select">
                                    <option value="circle">Circle</option>
                                    <option value="polygon" selected>Polygon</option>
                                    <option value="rectangle">Rectangle</option>
                                </select>
                            </div>

                            <div id="circle-section" class="border rounded bg-light p-2 mb-3" style="display:none;">
                                <h6 class="fw-semibold mb-2">Circle</h6>

                                <label class="form-label">Center Latitude</label>
                                <input id="geo-center-lat" name="center_lat" class="form-control form-control-sm mb-2">

                                <label class="form-label">Center Longitude</label>
                                <input id="geo-center-lng" name="center_lng" class="form-control form-control-sm mb-2">

                                <label class="form-label">Radius (m)</label>
                                <input id="geo-radius" name="radius_m" class="form-control form-control-sm">
                            </div>

                            <div id="polygon-section" class="border rounded bg-light p-2 mb-3">
                                <h6 class="fw-semibold mb-2">Polygon Points (JSON)</h6>
                                <textarea id="geo-polygon" name="polygon_points" class="form-control form-control-sm" rows="4"
                                          placeholder='[{"lat":45.50,"lng":-73.56}, {"lat":...,"lng":...}]'></textarea>
                            </div>

                            <div id="rectangle-section" class="border rounded bg-light p-2 mb-3" style="display:none;">
                                <h6 class="fw-semibold mb-2">Rectangle Bounds</h6>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label">North</label>
                                        <input id="geo-north" name="north_lat" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">South</label>
                                        <input id="geo-south" name="south_lat" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">East</label>
                                        <input id="geo-east" name="east_lng" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">West</label>
                                        <input id="geo-west" name="west_lng" class="form-control form-control-sm">
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-12 col-lg-7">
                            <div class="border rounded" style="height: 420px;">
                                <div id="modal-geofence-map" style="height: 100%;"></div>
                            </div>
                            <div class="small text-muted mt-2">
                                Your JS should populate <code>#geojson-modal</code> (preferred) or legacy fields.
                            </div>
                        </div>

                    </div>
                </form>

            </div>

            <div class="modal-footer">
                <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" type="submit" form="geofence-create-form">
                    <i class="bi bi-check2-circle"></i> Create
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const typeEl = document.getElementById('geo-type');
    const circle = document.querySelector('#geofenceCreateModal #circle-section');
    const poly   = document.querySelector('#geofenceCreateModal #polygon-section');
    const rect   = document.querySelector('#geofenceCreateModal #rectangle-section');

    function sync() {
        const t = typeEl ? typeEl.value : 'polygon';
        if (circle) circle.style.display = (t === 'circle') ? 'block' : 'none';
        if (poly)   poly.style.display   = (t === 'polygon') ? 'block' : 'none';
        if (rect)   rect.style.display   = (t === 'rectangle') ? 'block' : 'none';
    }

    if (typeEl) {
        typeEl.addEventListener('change', sync);
        sync();
    }
})();
</script>
