<?php
/** @var array $load */
/** @var array $customers */
/** @var array $drivers */

$pageTitle = 'Edit Load ' . h($load['reference_number'] ?? '');
require __DIR__ . '/../layout/header.php';

// datetime-local expects: YYYY-MM-DDTHH:MM
$pickupSrc   = $load['pickup_datetime'] ?? ($load['pickup_date'] ?? '');
$deliverySrc = $load['delivery_datetime'] ?? ($load['delivery_date'] ?? '');

$pickupVal = '';
if (!empty($pickupSrc)) {
    $ts = strtotime((string)$pickupSrc);
    if ($ts) $pickupVal = date('Y-m-d\TH:i', $ts);
}

$deliveryVal = '';
if (!empty($deliverySrc)) {
    $ts = strtotime((string)$deliverySrc);
    if ($ts) $deliveryVal = date('Y-m-d\TH:i', $ts);
}
?>

<div class="row">
    <div class="col-12 col-lg-9">
        <div class="card shadow-sm mb-3">
            <div class="card-body">

                <h1 class="h5 mb-3">
                    <i class="bi bi-pencil me-2"></i>Edit Load
                </h1>

                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger small">
                        <?= h($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="post"
                      action="/loads/update"
                      enctype="multipart/form-data"
                      class="row g-3 needs-validation"
                      novalidate>

                    <input type="hidden" name="id" value="<?= (int)($load['load_id'] ?? 0) ?>">

                    <!-- CUSTOMER -->
                    <div class="col-12 col-md-6">
                        <label class="form-label" for="customer_id">Customer</label>
                        <select id="customer_id" name="customer_id" class="form-select" required>
                            <?php foreach ($customers as $customer): ?>
                                <?php
                                    $cid = (int)($customer['id'] ?? 0);
                                    $cname = $customer['name'] ?? ($customer['customer_company_name'] ?? '');
                                ?>
                                <option value="<?= $cid ?>"
                                    <?= $cid === (int)($load['customer_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= h($cname) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a customer.</div>
                    </div>

                    <!-- REFERENCE -->
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="reference_number">Reference #</label>
                        <input type="text"
                               id="reference_number"
                               name="reference_number"
                               class="form-control"
                               value="<?= h($load['reference_number'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">Reference # is required.</div>
                    </div>

                    <!-- DRIVER -->
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="assigned_driver_id">Assigned Driver</label>
                        <select id="assigned_driver_id" name="assigned_driver_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <?php $did = (int)($driver['id'] ?? 0); ?>
                                <option value="<?= $did ?>"
                                    <?= $did === (int)($load['assigned_driver_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= h($driver['full_name'] ?? '') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- PICKUP -->
                    <div class="col-12">
                        <h2 class="h6 mt-2 mb-0">Pickup</h2>
                        <div class="text-muted small">Where and when the load will be picked up.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="pickup_address">Pickup address</label>
                        <input type="text"
                               id="pickup_address"
                               name="pickup_address"
                               class="form-control"
                               value="<?= h($load['pickup_address'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">Pickup address is required.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="pickup_city">Pickup city</label>
                        <input type="text"
                               id="pickup_city"
                               name="pickup_city"
                               class="form-control"
                               value="<?= h($load['pickup_city'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">Pickup city is required.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="pickup_datetime">Pickup date/time</label>
                        <input type="datetime-local"
                               id="pickup_datetime"
                               name="pickup_datetime"
                               class="form-control"
                               value="<?= h($pickupVal) ?>"
                               required>
                        <div class="invalid-feedback">Pickup date/time is required.</div>
                    </div>

                    <!-- DELIVERY -->
                    <div class="col-12">
                        <h2 class="h6 mt-3 mb-0">Delivery</h2>
                        <div class="text-muted small">Where and when the load will be delivered.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="delivery_address">Delivery address</label>
                        <input type="text"
                               id="delivery_address"
                               name="delivery_address"
                               class="form-control"
                               value="<?= h($load['delivery_address'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">Delivery address is required.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="delivery_city">Delivery city</label>
                        <input type="text"
                               id="delivery_city"
                               name="delivery_city"
                               class="form-control"
                               value="<?= h($load['delivery_city'] ?? '') ?>"
                               required>
                        <div class="invalid-feedback">Delivery city is required.</div>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label" for="delivery_datetime">Delivery date/time</label>
                        <input type="datetime-local"
                               id="delivery_datetime"
                               name="delivery_datetime"
                               class="form-control"
                               value="<?= h($deliveryVal) ?>"
                               required>
                        <div class="invalid-feedback">Delivery date/time is required.</div>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="col-12">
                        <label class="form-label" for="description">Description</label>
                        <textarea id="description"
                                  name="description"
                                  class="form-control"
                                  rows="2"><?= h($load['description'] ?? '') ?></textarea>
                    </div>

                    <!-- STATUS WARNINGS -->
                    <?php if (empty($load['has_vehicle'])): ?>
                        <div class="col-12">
                            <div class="alert alert-warning small mb-0">
                                <i class="bi bi-truck me-1"></i>
                                A vehicle must be assigned before this load can be dispatched.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($load['has_pod']) && ($load['load_status'] ?? '') !== 'delivered'): ?>
                        <div class="col-12">
                            <div class="alert alert-warning small mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Proof of Delivery (POD) is required before marking this load as delivered.
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- STATUS -->
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="load_status">Status</label>
                        <select id="load_status" name="load_status" class="form-select">
                            <option value="pending"    <?= ($load['load_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_transit" <?= ($load['load_status'] ?? '') === 'in_transit' ? 'selected' : '' ?> <?= empty($load['has_vehicle']) ? 'disabled' : '' ?>>In Transit</option>
                            <option value="delivered"  <?= ($load['load_status'] ?? '') === 'delivered' ? 'selected' : '' ?> <?= empty($load['has_pod']) ? 'disabled' : '' ?>>Delivered</option>
                        </select>
                    </div>

                    <!-- ADD DOCUMENT -->
                    <div class="col-12">
                        <h2 class="h6 mt-3">Add Document</h2>
                        <div class="row g-2 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label" for="document_type">Document Type</label>
                                <select id="document_type" name="document_type" class="form-select">
                                    <option value="">— Select —</option>
                                    <option value="bol">Bill of Lading (BOL)</option>
                                    <option value="pod">Proof of Delivery (POD)</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-8">
                                <label class="form-label" for="document_file">Upload PDF</label>
                                <input type="file"
                                       id="document_file"
                                       name="document_file"
                                       class="form-control"
                                       accept="application/pdf">
                            </div>
                        </div>
                    </div>

                    <!-- ACTIONS -->
                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                        <a href="/loads/view?id=<?= (int)($load['load_id'] ?? 0) ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>
                            Save Changes
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
