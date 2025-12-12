<?php
$pageTitle = 'Edit Load ' . htmlspecialchars($load['reference_number'] ?? '');
require __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-12 col-lg-9">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h1 class="h5 mb-3"><i class="bi bi-pencil me-2"></i>Edit Load</h1>

                <form method="post" action="/loads/update" class="row g-3">
                    <input type="hidden" name="id" value="<?= (int)$load['load_id'] ?>">

                    <div class="col-12 col-md-6">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= (int)$customer['id'] ?>"
                                    <?= (int)$customer['id'] === (int)$load['customer_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['customer_company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Reference #</label>
                        <input type="text" name="reference_number" class="form-control"
                               value="<?= htmlspecialchars($load['reference_number']) ?>" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Assigned Driver</label>
                        <select name="driver_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= (int)$driver['id'] ?>"
                                    <?= (int)$driver['id'] === (int)($load['driver_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($driver['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($load['description']) ?></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <h2 class="h6 mt-2">Pickup</h2>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Contact Name</label>
                                <input type="text" name="pickup_contact_name" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['pickup_contact_name']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <input type="text" name="pickup_address" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['pickup_address']) ?>" required>
                            </div>
                            <div class="col-8">
                                <label class="form-label small">City</label>
                                <input type="text" name="pickup_city" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['pickup_city']) ?>" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Postal Code</label>
                                <input type="text" name="pickup_postal_code" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['pickup_postal_code']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Pickup Date/Time</label>
                                <input type="datetime-local" name="pickup_date" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars(str_replace(' ', 'T', $load['pickup_date'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <h2 class="h6 mt-2">Delivery</h2>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Contact Name</label>
                                <input type="text" name="delivery_contact_name" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['delivery_contact_name']) ?>" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <input type="text" name="delivery_address" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['delivery_address']) ?>" required>
                            </div>
                            <div class="col-8">
                                <label class="form-label small">City</label>
                                <input type="text" name="delivery_city" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['delivery_city']) ?>" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Postal Code</label>
                                <input type="text" name="delivery_postal_code" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars($load['delivery_postal_code']) ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Delivery Date/Time</label>
                                <input type="datetime-local" name="delivery_date" class="form-control form-control-sm"
                                       value="<?= htmlspecialchars(str_replace(' ', 'T', $load['delivery_date'])) ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Total Weight (KG)</label>
                        <input type="number" step="0.01" name="total_weight_kg" class="form-control"
                               value="<?= htmlspecialchars($load['total_weight_kg']) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Rate Amount</label>
                        <input type="number" step="0.01" name="rate_amount" class="form-control"
                               value="<?= htmlspecialchars($load['rate_amount']) ?>">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Currency</label>
                        <select name="rate_currency" class="form-select">
                            <option value="CAD" <?= ($load['rate_currency'] ?? '') === 'CAD' ? 'selected' : '' ?>>CAD</option>
                            <option value="USD" <?= ($load['rate_currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <?php $status = $load['load_status'] ?? 'pending'; ?>
                        <select name="load_status" class="form-select">
                            <option value="pending"   <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="in_transit"<?= $status === 'in_transit' ? 'selected' : '' ?>>In transit</option>
                            <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($load['notes']) ?></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="/loads/view?id=<?= (int)$load['load_id'] ?>" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
