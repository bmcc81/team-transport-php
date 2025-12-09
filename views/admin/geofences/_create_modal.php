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

                    <div class="row g-3">

                        <!-- LEFT SIDE FORM -->
                        <div class="col-12 col-md-5">

                            <div class="mb-3">
                                <label class="form-label">Name *</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Type *</label>
                                <select name="type" id="geo-type" class="form-select">
                                    <option value="circle">Circle</option>
                                    <option value="polygon">Polygon</option>
                                </select>
                            </div>

                            <!-- Circle -->
                            <div id="circle-section" class="border rounded p-2 mb-3">
                                <h6>Circle Settings</h6>
                                <input type="text" class="form-control mb-2" id="geo-center-lat" name="center_lat" placeholder="Latitude">
                                <input type="text" class="form-control mb-2" id="geo-center-lng" name="center_lng" placeholder="Longitude">
                                <input type="text" class="form-control mb-2" id="geo-radius" name="radius_m" placeholder="Radius meters">
                            </div>

                            <!-- Polygon -->
                            <div id="polygon-section" class="border rounded p-2 mb-3" style="display:none;">
                                <h6>Polygon Settings</h6>
                                <textarea class="form-control" id="geo-poly" name="polygon_points" rows="4"></textarea>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="applies_to_all_vehicles" checked>
                                <label class="form-check-label">Applies to all vehicles</label>
                            </div>

                        </div>

                        <!-- RIGHT: INLINE MAP -->
                        <div class="col-12 col-md-7">
                            <div class="border rounded" style="height:420px;">
                                <div id="geofence-map-inline" style="height:100%; width:100%;"></div>
                            </div>

                            <div class="mt-2 btn-group btn-group-sm">
                                <button id="btn-inline-circle" class="btn btn-outline-primary">
                                    <i class="bi bi-circle"></i> Circle
                                </button>
                                <button id="btn-inline-polygon" class="btn btn-outline-primary">
                                    <i class="bi bi-vector-pen"></i> Polygon
                                </button>
                                <button id="btn-inline-reset" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Reset
                                </button>
                            </div>

                        </div>
                    </div>

                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="btn-save-geofence">
                    <i class="bi bi-check-circle"></i> Save Geofence
                </button>
            </div>

        </div>
    </div>
</div>
