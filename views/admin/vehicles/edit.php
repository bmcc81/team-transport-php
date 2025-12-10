<?php

/** @var array $vehicle */
/** @var array $drivers (each: ['id' => ..., 'full_name' => ...]) */
/** @var array|null $errors */

$pageTitle = 'Edit Vehicle — ' . e($vehicle['vehicle_number'] ?? '');

require __DIR__ . '/../../layout/header.php';

$statuses = [
    'available'   => 'Available',
    'in_service'  => 'In Service',
    'maintenance' => 'Maintenance',
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
                    <li class="breadcrumb-item active">Edit Vehicle</li>
                </ol>
            </nav>

            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h4 mb-1">Edit Vehicle</h2>
                    <div class="small text-muted">
                        <?= e($vehicle['vehicle_number'] ?? '') ?> —
                        <?= e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Vehicle
                    </a>
                </div>
            </div>

            <!-- Validation errors -->
            <?php if (!empty($errors) && is_array($errors)): ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Please correct the following errors:</div>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?= e($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="/admin/vehicles/edit/<?= e((string)$vehicle['id']) ?>">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label for="vehicle_number" class="form-label">
                                    Vehicle Number <span class="text-danger">*</span>
                                </label>
                                <input
                                    type="text"
                                    id="vehicle_number"
                                    name="vehicle_number"
                                    class="form-control"
                                    required
                                    value="<?= e($vehicle['vehicle_number'] ?? '') ?>"
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="make" class="form-label">Make</label>
                                <input
                                    type="text"
                                    id="make"
                                    name="make"
                                    class="form-control"
                                    value="<?= e($vehicle['make'] ?? '') ?>"
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="model" class="form-label">Model</label>
                                <input
                                    type="text"
                                    id="model"
                                    name="model"
                                    class="form-control"
                                    value="<?= e($vehicle['model'] ?? '') ?>"
                                >
                            </div>

                            <div class="col-md-3">
                                <label for="year" class="form-label">Year</label>
                                <input
                                    type="number"
                                    id="year"
                                    name="year"
                                    min="1980"
                                    max="2100"
                                    class="form-control"
                                    value="<?= e((string)($vehicle['year'] ?? '')) ?>"
                                >
                            </div>

                            <div class="col-md-3">
                                <label for="license_plate" class="form-label">License Plate</label>
                                <input
                                    type="text"
                                    id="license_plate"
                                    name="license_plate"
                                    class="form-control"
                                    value="<?= e($vehicle['license_plate'] ?? '') ?>"
                                >
                            </div>

                            <div class="col-md-6">
                                <label for="vin" class="form-label">VIN</label>
                                <input
                                    type="text"
                                    id="vin"
                                    name="vin"
                                    class="form-control"
                                    value="<?= e($vehicle['vin'] ?? '') ?>"
                                >
                            </div>

                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="">Select status...</option>
                                    <?php foreach ($statuses as $value => $label): ?>
                                        <option
                                            value="<?= e($value) ?>"
                                            <?= (isset($vehicle['status']) && $vehicle['status'] === $value) ? 'selected' : '' ?>
                                        >
                                            <?= e($label) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label for="assigned_driver_id" class="form-label">Assigned Driver</label>
                                <select id="assigned_driver_id" name="assigned_driver_id" class="form-select">
                                    <option value="">None</option>
                                    <?php foreach ($drivers as $driver): ?>
                                        <option
                                            value="<?= e((string)$driver['id']) ?>"
                                            <?= (!empty($vehicle['assigned_driver_id']) && (int)$vehicle['assigned_driver_id'] === (int)$driver['id']) ? 'selected' : '' ?>
                                        >
                                            <?= e($driver['full_name'] ?? ('Driver #' . $driver['id'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- If you track lat/long, you can add them here as readonly or hidden -->
                            <?php if (isset($vehicle['latitude'], $vehicle['longitude'])): ?>
                                <div class="col-md-3">
                                    <label class="form-label">Latitude</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        value="<?= e((string)$vehicle['latitude']) ?>"
                                        readonly
                                    >
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Longitude</label>
                                    <input
                                        type="text"
                                        class="form-control form-control-sm"
                                        value="<?= e((string)$vehicle['longitude']) ?>"
                                        readonly
                                    >
                                </div>
                            <?php endif; ?>

                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4">
                            <div class="small text-muted">
                                Fields marked with <span class="text-danger">*</span> are required.
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>"
                                   class="btn btn-outline-secondary">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-lg"></i> Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>
</div>
