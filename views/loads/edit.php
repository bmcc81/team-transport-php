<?php
$pageTitle = 'Edit Load ' . htmlspecialchars($load['reference_number'] ?? '');
require __DIR__ . '/../layout/header.php';
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
                        <?= htmlspecialchars($_SESSION['error']) ?>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <form method="post"
                      action="/loads/update"
                      enctype="multipart/form-data"
                      class="row g-3">

                    <input type="hidden" name="id" value="<?= (int)$load['load_id'] ?>">

                    <!-- CUSTOMER -->
                    <div class="col-12 col-md-6">
                        <label class="form-label">Customer</label>
                        <select name="customer_id" class="form-select" required>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= (int)$customer['id'] ?>"
                                    <?= (int)$customer['id'] === (int)$load['customer_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- REFERENCE -->
                    <div class="col-12 col-md-3">
                        <label class="form-label">Reference #</label>
                        <input type="text"
                               name="reference_number"
                               class="form-control"
                               value="<?= htmlspecialchars($load['reference_number']) ?>"
                               required>
                    </div>

                    <!-- DRIVER -->
                    <!-- DRIVER -->
                    <div class="col-12 col-md-3">
                        <label class="form-label">Assigned Driver</label>
                        <select name="assigned_driver_id" class="form-select">
                            <option value="">Unassigned</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= (int)$driver['id'] ?>"
                                    <?= (int)$driver['id'] === (int)($load['assigned_driver_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($driver['full_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- DESCRIPTION -->
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description"
                                  class="form-control"
                                  rows="2"><?= htmlspecialchars($load['description'] ?? '') ?></textarea>
                    </div>

                    <!-- STATUS WARNINGS -->
                    <?php if (empty($load['has_vehicle'])): ?>
                        <div class="col-12">
                            <div class="alert alert-warning small">
                                <i class="bi bi-truck me-1"></i>
                                A vehicle must be assigned before this load can be dispatched.
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($load['has_pod']) && $load['load_status'] !== 'delivered'): ?>
                        <div class="col-12">
                            <div class="alert alert-warning small">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Proof of Delivery (POD) is required before marking this load as delivered.
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- STATUS -->
                    <div class="col-6 col-md-3">
                        <label class="form-label">Status</label>
                        <select name="load_status" class="form-select">

                            <option value="pending"
                                <?= $load['load_status'] === 'pending' ? 'selected' : '' ?>>
                                Pending
                            </option>

                            <option value="in_transit"
                                <?= $load['load_status'] === 'in_transit' ? 'selected' : '' ?>
                                <?= empty($load['has_vehicle']) ? 'disabled' : '' ?>>
                                In Transit
                            </option>

                            <option value="delivered"
                                <?= $load['load_status'] === 'delivered' ? 'selected' : '' ?>
                                <?= empty($load['has_pod']) ? 'disabled' : '' ?>>
                                Delivered
                            </option>

                        </select>
                    </div>

                    <!-- ADD DOCUMENT -->
                    <div class="col-12">
                        <h2 class="h6 mt-3">Add Document</h2>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Document Type</label>
                                <select name="document_type" class="form-select">
                                    <option value="">— Select —</option>
                                    <option value="bol">Bill of Lading (BOL)</option>
                                    <option value="pod">Proof of Delivery (POD)</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Upload PDF</label>
                                <input type="file"
                                       name="document_file"
                                       class="form-control"
                                       accept="application/pdf">
                            </div>
                        </div>
                    </div>

                    <!-- ACTIONS -->
                    <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                        <a href="/loads/view?id=<?= (int)$load['load_id'] ?>"
                           class="btn btn-outline-secondary">
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
