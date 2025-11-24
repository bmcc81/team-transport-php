<?php
// Detect edit vs create
$isEdit = isset($load);
?>

<div class="row g-3">

    <!-- CUSTOMER -->
    <div class="col-md-6">
        <label class="form-label">Customer</label>
        <select name="customer_id" class="form-select" required>
            <option value="">Select customer...</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>"
                    <?= ($isEdit && $load['customer_id'] == $c['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c['customer_company_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- REFERENCE NUMBER -->
    <div class="col-md-6">
        <label class="form-label">Reference Number</label>
        <input type="text" name="reference_number" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['reference_number']) : '' ?>" required>
    </div>

    <!-- DESCRIPTION -->
    <div class="col-12">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="2"><?= $isEdit ? htmlspecialchars($load['description']) : '' ?></textarea>
    </div>

    <!-- PICKUP INFO -->
    <h5 class="mt-4">Pickup</h5>

    <div class="col-md-6">
        <label class="form-label">Contact Name</label>
        <input type="text" name="pickup_contact_name" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['pickup_contact_name']) : '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Pickup Address</label>
        <input type="text" name="pickup_address" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['pickup_address']) : '' ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Pickup City</label>
        <input type="text" name="pickup_city" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['pickup_city']) : '' ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="pickup_postal_code" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['pickup_postal_code']) : '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Pickup Date</label>
        <input type="datetime-local" name="pickup_date" class="form-control"
               value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($load['pickup_date'])) : '' ?>" required>
    </div>

    <!-- DELIVERY INFO -->
    <h5 class="mt-4">Delivery</h5>

    <div class="col-md-6">
        <label class="form-label">Contact Name</label>
        <input type="text" name="delivery_contact_name" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['delivery_contact_name']) : '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Delivery Address</label>
        <input type="text" name="delivery_address" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['delivery_address']) : '' ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Delivery City</label>
        <input type="text" name="delivery_city" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['delivery_city']) : '' ?>" required>
    </div>

    <div class="col-md-4">
        <label class="form-label">Postal Code</label>
        <input type="text" name="delivery_postal_code" class="form-control"
               value="<?= $isEdit ? htmlspecialchars($load['delivery_postal_code']) : '' ?>">
    </div>

    <div class="col-md-6">
        <label class="form-label">Delivery Date</label>
        <input type="datetime-local" name="delivery_date" class="form-control"
               value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($load['delivery_date'])) : '' ?>" required>
    </div>

    <!-- WEIGHT -->
    <div class="col-md-4">
        <label class="form-label">Total Weight (KG)</label>
        <input type="number" step="0.01" name="total_weight_kg" class="form-control"
               value="<?= $isEdit ? $load['total_weight_kg'] : '' ?>">
    </div>

    <!-- RATE -->
    <div class="col-md-4">
        <label class="form-label">Rate Amount</label>
        <input type="number" step="0.01" name="rate_amount" class="form-control"
               value="<?= $isEdit ? $load['rate_amount'] : '' ?>">
    </div>

    <div class="col-md-4">
        <label class="form-label">Currency</label>
        <select name="rate_currency" class="form-select">
            <option value="CAD" <?= ($isEdit && $load['rate_currency']=='CAD') ? 'selected' : '' ?>>CAD</option>
            <option value="USD" <?= ($isEdit && $load['rate_currency']=='USD') ? 'selected' : '' ?>>USD</option>
        </select>
    </div>

    <!-- DRIVER -->
    <div class="col-md-6">
        <label class="form-label">Assign Driver</label>
        <select name="assigned_driver_id" class="form-select">
            <option value="">Unassigned</option>
            <?php foreach ($drivers as $d): ?>
                <option value="<?= $d['id'] ?>"
                    <?= ($isEdit && $load['assigned_driver_id'] == $d['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['username']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- STATUS -->
    <div class="col-md-6">
        <label class="form-label">Load Status</label>
        <select name="load_status" class="form-select" required>
            <?php foreach (['pending','assigned','in_transit','delivered','cancelled'] as $status): ?>
                <option value="<?= $status ?>"
                    <?= ($isEdit && $load['load_status'] == $status) ? 'selected' : '' ?>>
                    <?= ucfirst(str_replace('_',' ',$status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- NOTES -->
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="3"><?= $isEdit ? htmlspecialchars($load['notes']) : '' ?></textarea>
    </div>

    <!-- FILE UPLOAD -->
    <div class="col-12 mt-3">
        <label class="form-label">Upload More Documents</label>
        <input type="file" name="documents[]" class="form-control" multiple>
    </div>

</div>
