<?php
$pageTitle = "Add Maintenance — " . e($vehicle['vehicle_number']);
require __DIR__ . '/../../layout/header.php';

$statuses = [
    'planned'   => 'Planned',
    'completed' => 'Completed',
];
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/vehicles">Vehicles</a></li>
                    <li class="breadcrumb-item">
                        <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>">
                            <?= e($vehicle['vehicle_number']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance">
                            Maintenance
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Add Maintenance</li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Add Maintenance Item</h2>

                <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance"
                   class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
            </div>

            <!-- Validation errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold">Please fix the following issues:</div>
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= e($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance/create">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Title <span class="text-danger">*</span></label>
                                <input
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    required
                                    value="<?= e($item['title']) ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Scheduled Date <span class="text-danger">*</span></label>
                                <input
                                    type="date"
                                    name="scheduled_date"
                                    class="form-control"
                                    required
                                    value="<?= e($item['scheduled_date']) ?>"
                                >
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea
                                    name="description"
                                    class="form-control"
                                    rows="4"
                                ><?= e($item['description']) ?></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option value="<?= e($value) ?>"
                                            <?= $item['status'] === $value ? 'selected' : '' ?>>
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="small text-muted">
                                Fields with <span class="text-danger">*</span> are required.
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg"></i> Save Maintenance Item
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>
