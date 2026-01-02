<?php
/**
 * Shared form fields for create/edit.
 *
 * Expects:
 * - array $customers
 * - array $drivers
 * - array $vehicles
 * - array|null $load  (for edit) associative row
 */

$isEdit = isset($load) && is_array($load);
$old    = $load ?? [];


if (!function_exists('old_val')) {
    function old_val(array $src, string $key, $default = '')
    {
        return isset($src[$key]) ? $src[$key] : $default;
    }
}

if (!function_exists('dt_local')) {
    // Converts "YYYY-MM-DD HH:MM:SS" (MySQL) or "YYYY-MM-DDTHH:MM" into "YYYY-MM-DDTHH:MM" for <input datetime-local>
    function dt_local($value): string
    {
        $value = trim((string)$value);
        if ($value === '') return '';
        $ts = strtotime($value);
        if (!$ts) return '';
        return date('Y-m-d\TH:i', $ts);
    }
}

function old_val(array $src, string $key, $default = '')
{
    return isset($src[$key]) ? $src[$key] : $default;
}
?>

<div class="row g-3">

    <!-- LEFT COLUMN: Core details & schedule -->
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-semibold">
                Core Details
            </div>
            <div class="card-body">

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Load Number</label>
                        <input type="text"
                               name="load_number"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'load_number')) ?>"
                               placeholder="Optional (auto / manual)"/>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small text-muted">Customer</label>
                        <select name="customer_id" class="form-select form-select-sm" required>
                            <option value="">Select a customer...</option>
                            <?php foreach ($customers as $c) { ?>
                                <option value="<?= e((string)$c['id']) ?>"
                                    <?= (string)$c['id'] === (string)old_val($old, 'customer_id') ? 'selected' : '' ?>>
                                    <?= e($c['customer_company_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Assigned Driver</label>
                        <select name="assigned_driver_id" class="form-select form-select-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $d) { ?>
                                <option value="<?= e((string)$d['id']) ?>"
                                    <?= (string)$d['id'] === (string)old_val($old, 'assigned_driver_id') ? 'selected' : '' ?>>
                                    <?= e($d['full_name']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Vehicle</label>
                        <select name="vehicle_id" class="form-select form-select-sm">
                            <option value="">Unassigned</option>
                            <?php foreach ($vehicles as $v) { ?>
                                <option value="<?= e((string)$v['id']) ?>"
                                    <?= (string)$v['id'] === (string)old_val($old, 'vehicle_id') ? 'selected' : '' ?>>
                                    <?= e($v['vehicle_number']) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Scheduled Start</label>
                        <input type="datetime-local"
                               name="scheduled_start"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'scheduled_start')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small text-muted">Scheduled End</label>
                        <input type="datetime-local"
                               name="scheduled_end"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'scheduled_end')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label small text-muted">Reference</label>
                        <input type="text"
                               name="reference"
                               class="form-control form-control-sm mb-1"
                               value="<?= e((string)old_val($old, 'reference')) ?>"
                               placeholder="Customer PO, internal ref, etc.">
                        <input type="text"
                               name="reference_number"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'reference_number')) ?>"
                               placeholder="Reference number">
                    </div>

                    <div class="col-12">
                        <label class="form-label small text-muted">Description</label>
                        <textarea name="description"
                                  rows="3"
                                  class="form-control form-control-sm"
                                  placeholder="Optional load description, content, special handling notes..."><?= e((string)old_val($old, 'description')) ?></textarea>
                    </div>
                </div>

            </div>
        </div>

        <!-- Pickup / Delivery -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-semibold">
                Pickup &amp; Delivery
            </div>
            <div class="card-body">
                <div class="row g-3">

                    <div class="col-md-6 border-end">
                        <h6 class="text-uppercase small text-muted mb-2">Pickup</h6>

                        <div class="mb-2">
                            <label class="form-label small text-muted">Contact Name</label>
                            <input type="text"
                                   name="pickup_contact_name"
                                   class="form-control form-control-sm"
                                   value="<?= e((string)old_val($old, 'pickup_contact_name')) ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small text-muted">Address</label>
                            <input type="text"
                                   name="pickup_address"
                                   class="form-control form-control-sm"
                                   required
                                   value="<?= e((string)old_val($old, 'pickup_address')) ?>">
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">City</label>
                                <input type="text"
                                       name="pickup_city"
                                       class="form-control form-control-sm"
                                       required
                                       value="<?= e((string)old_val($old, 'pickup_city')) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Postal Code</label>
                                <input type="text"
                                       name="pickup_postal_code"
                                       class="form-control form-control-sm"
                                       value="<?= e((string)old_val($old, 'pickup_postal_code')) ?>">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small text-muted">Pickup Date &amp; Time</label>
                            <input type="datetime-local"
                                   name="pickup_date"
                                   class="form-control form-control-sm"
                                   required
                                   value="<?= e((string)old_val($old, 'pickup_date')) ?>">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <h6 class="text-uppercase small text-muted mb-2">Delivery</h6>

                        <div class="mb-2">
                            <label class="form-label small text-muted">Contact Name</label>
                            <input type="text"
                                   name="delivery_contact_name"
                                   class="form-control form-control-sm"
                                   value="<?= e((string)old_val($old, 'delivery_contact_name')) ?>">
                        </div>

                        <div class="mb-2">
                            <label class="form-label small text-muted">Address</label>
                            <input type="text"
                                   name="delivery_address"
                                   class="form-control form-control-sm"
                                   required
                                   value="<?= e((string)old_val($old, 'delivery_address')) ?>">
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small text-muted">City</label>
                                <input type="text"
                                       name="delivery_city"
                                       class="form-control form-control-sm"
                                       required
                                       value="<?= e((string)old_val($old, 'delivery_city')) ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-muted">Postal Code</label>
                                <input type="text"
                                       name="delivery_postal_code"
                                       class="form-control form-control-sm"
                                       value="<?= e((string)old_val($old, 'delivery_postal_code')) ?>">
                            </div>
                        </div>

                        <div class="mt-2">
                            <label class="form-label small text-muted">Delivery Date &amp; Time</label>
                            <input type="datetime-local"
                                   name="delivery_date"
                                   class="form-control form-control-sm"
                                   required
                                   value="<?= e((string)old_val($old, 'delivery_date')) ?>">
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Weight & Rate -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-semibold">
                Weight &amp; Rate
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Total Weight (kg)</label>
                        <input type="number"
                               step="0.01"
                               name="total_weight_kg"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'total_weight_kg')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Rate Amount</label>
                        <input type="number"
                               step="0.01"
                               name="rate_amount"
                               class="form-control form-control-sm"
                               value="<?= e((string)old_val($old, 'rate_amount')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-muted">Currency</label>
                        <?php $currency = old_val($old, 'rate_currency', 'CAD'); ?>
                        <select name="rate_currency" class="form-select form-select-sm">
                            <option value="CAD" <?= $currency === 'CAD' ? 'selected' : '' ?>>CAD</option>
                            <option value="USD" <?= $currency === 'USD' ? 'selected' : '' ?>>USD</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-semibold">
                Internal Notes
            </div>
            <div class="card-body">
                <textarea name="notes"
                          rows="4"
                          class="form-control form-control-sm"
                          placeholder="Internal notes, special handling, accessorial charges, etc."><?= e((string)old_val($old, 'notes')) ?></textarea>
            </div>
        </div>
    </div>

    <!-- RIGHT COLUMN: Status & Summary -->
    <div class="col-12 col-xl-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-light fw-semibold">
                Status & Workflow
            </div>
            <div class="card-body">

                <?php
                $currentStatus = old_val($old, 'load_status', 'pending');
                ?>

                <label class="form-label small text-muted">Load Status</label>
                <select name="load_status" class="form-select form-select-sm mb-3">
                    <option value="pending"   <?= $currentStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="assigned"  <?= $currentStatus === 'assigned' ? 'selected' : '' ?>>Assigned</option>
                    <option value="in_transit"<?= $currentStatus === 'in_transit' ? 'selected' : '' ?>>In Transit</option>
                    <option value="delivered" <?= $currentStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>

                <!-- Simple visual workflow -->
                <div class="small text-muted mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="<?= in_array($currentStatus, ['pending','assigned','in_transit','delivered','cancelled'], true) ? 'fw-semibold' : '' ?>">Pending</span>
                        <span class="<?= in_array($currentStatus, ['assigned','in_transit','delivered','cancelled'], true) ? 'fw-semibold' : '' ?>">Assigned</span>
                        <span class="<?= in_array($currentStatus, ['in_transit','delivered','cancelled'], true) ? 'fw-semibold' : '' ?>">In Transit</span>
                        <span class="<?= in_array($currentStatus, ['delivered'], true) ? 'fw-semibold' : '' ?>">Delivered</span>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mb-2">
                    <?= $isEdit ? 'Save Changes' : 'Create Load' ?>
                </button>

                <?php if ($isEdit && !empty($old['load_id'])) { ?>
                    <a href="/admin/loads/<?= e((string)$old['load_id']) ?>"
                       class="btn btn-outline-secondary w-100 btn-sm">
                        Back to Load Details
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>

</div>
