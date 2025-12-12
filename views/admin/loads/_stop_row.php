<div class="stop-row border rounded p-3 mb-3" data-index="<?= $i ?>">
    <div class="row g-3">

        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="stops[<?= $i ?>][type]" class="form-select stop-type">
                <option value="pickup"   <?= ($s['type'] ?? '') === 'pickup' ? 'selected' : '' ?>>Pickup</option>
                <option value="dropoff"  <?= ($s['type'] ?? '') === 'dropoff' ? 'selected' : '' ?>>Delivery</option>
                <option value="intermediate" <?= ($s['type'] ?? '') === 'intermediate' ? 'selected' : '' ?>>Intermediate</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label">Address</label>
            <div class="input-group">
                <input type="text"
                       class="form-control address-field"
                       name="stops[<?= $i ?>][address]"
                       value="<?= h($s['address'] ?? '') ?>"
                       placeholder="Enter address...">

                <button type="button"
                        class="btn btn-outline-secondary show-map-btn"
                        data-index="<?= $i ?>">
                    <i class="bi bi-map"></i>
                </button>
            </div>
        </div>

        <input type="hidden" name="stops[<?= $i ?>][lat]" class="stop-lat" value="<?= h($s['lat'] ?? '') ?>">
        <input type="hidden" name="stops[<?= $i ?>][lng]" class="stop-lng" value="<?= h($s['lng'] ?? '') ?>">

        <div class="col-md-2">
            <label class="form-label">Time Window</label>
            <input type="datetime-local"
                   class="form-control"
                   name="stops[<?= $i ?>][window]"
                   value="<?= h($s['window'] ?? '') ?>">
        </div>

        <div class="col-md-1 d-flex align-items-end">
            <button type="button" class="btn btn-outline-danger remove-stop-btn">
                <i class="bi bi-trash"></i>
            </button>
        </div>

    </div>
</div>
