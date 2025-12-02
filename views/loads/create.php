<?php
$pageTitle = 'Create Load';
require __DIR__ . '/../layout/header.php';
?>

<div class="row">
    <div class="col-12 col-lg-9">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h1 class="h5 mb-3"><i class="bi bi-plus-lg me-2"></i>Create Load</h1>

                <form method="post" action="/loads" enctype="multipart/form-data" class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <option value="">Select customer...</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= (int)$customer['id'] ?>">
                                    <?= htmlspecialchars($customer['customer_company_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Reference #</label>
                        <input type="text" name="reference_number" class="form-control" required>
                    </div>

                    <div class="col-12 col-md-3">
                        <label class="form-label">Assigned Driver</label>
                        <select name="assigned_driver_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= (int)$driver['id'] ?>"><?= htmlspecialchars($driver['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-12 col-md-6">
                        <h2 class="h6 mt-2">Pickup</h2>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Contact Name</label>
                                <input type="text" name="pickup_contact_name" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <input type="text" name="pickup_address" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-8">
                                <label class="form-label small">City</label>
                                <input type="text" name="pickup_city" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Postal Code</label>
                                <input type="text" name="pickup_postal_code" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Pickup Date/Time</label>
                                <input type="datetime-local" name="pickup_date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6">
                        <h2 class="h6 mt-2">Delivery</h2>
                        <div class="row g-2">
                            <div class="col-12">
                                <label class="form-label small">Contact Name</label>
                                <input type="text" name="delivery_contact_name" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Address</label>
                                <input type="text" name="delivery_address" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-8">
                                <label class="form-label small">City</label>
                                <input type="text" name="delivery_city" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label small">Postal Code</label>
                                <input type="text" name="delivery_postal_code" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Delivery Date/Time</label>
                                <input type="datetime-local" name="delivery_date" class="form-control form-control-sm" required>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <label class="form-label">Total Weight (KG)</label>
                        <input type="number" step="0.01" name="total_weight_kg" class="form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Rate Amount</label>
                        <input type="number" step="0.01" name="rate_amount" class="form-control">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Currency</label>
                        <select name="rate_currency" class="form-select">
                            <option value="CAD">CAD</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="load_status" class="form-select">
                            <option value="pending">Pending</option>
                            <option value="in_transit">In transit</option>
                            <option value="delivered">Delivered</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Upload Documents</label>
                        <input type="file" name="documents[]" class="form-control" multiple>
                        <div class="form-text">Optional: PDFs, images, etc.</div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="/loads" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2-circle me-1"></i>Create Load
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
