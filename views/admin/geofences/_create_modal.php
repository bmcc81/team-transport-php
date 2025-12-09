<div class="modal fade" id="geofenceCreateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">

            <form id="geofence-create-form" method="POST">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-geo-alt"></i> Create Geofence
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">Name *</label>
                        <input name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <input type="hidden" name="type" id="geo-type">

                    <!-- CIRCLE -->
                    <div id="geo-circle-fields" class="border rounded p-3 mb-3 bg-light">
                        <h6 class="fw-bold">Circle</h6>

                        <label class="form-label">Center Latitude</label>
                        <input name="center_lat" id="geo-center-lat" class="form-control">

                        <label class="form-label mt-2">Center Longitude</label>
                        <input name="center_lng" id="geo-center-lng" class="form-control">

                        <label class="form-label mt-2">Radius (m)</label>
                        <input name="radius_m" id="geo-radius" class="form-control">
                    </div>

                    <!-- POLYGON -->
                    <div id="geo-polygon-fields" class="border rounded p-3 mb-3 bg-light">
                        <h6 class="fw-bold">Polygon</h6>

                        <label class="form-label">Polygon Points JSON</label>
                        <textarea name="polygon_points"
                                  id="geo-poly"
                                  class="form-control"
                                  rows="3"></textarea>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" name="applies_to_all_vehicles"
                               class="form-check-input" checked>
                        <label class="form-check-label">Applies to all vehicles</label>
                    </div>

                    <div class="form-check mb-2">
                        <input type="checkbox" name="active" class="form-check-input" checked>
                        <label class="form-check-label">Active</label>
                    </div>

                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>
