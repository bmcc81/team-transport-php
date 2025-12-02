<?php 
$pageTitle = "Add Vehicle"; 
require __DIR__ . '/../../layout/header.php'; 
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">
                Edit Vehicle: <?= htmlspecialchars($vehicle['vehicle_number'] ?? '') ?>
            </h2>

            <form method="POST"
                  action="/admin/vehicles/edit/<?= htmlspecialchars($vehicle['id']) ?>"
                  class="card p-4 shadow-sm">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vehicle Number</label>
                        <input
                            type="text"
                            name="vehicle_number"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($vehicle['vehicle_number'] ?? '') ?>"
                        >
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">License Plate</label>
                        <input
                            type="text"
                            name="license_plate"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($vehicle['license_plate'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Make</label>
                        <input
                            type="text"
                            name="make"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($vehicle['make'] ?? '') ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Model</label>
                        <input
                            type="text"
                            name="model"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($vehicle['model'] ?? '') ?>"
                        >
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year</label>
                        <input
                            type="number"
                            name="year"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($vehicle['year'] ?? '') ?>"
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">VIN (optional)</label>
                    <input
                        type="text"
                        name="vin"
                        class="form-control"
                        value="<?= htmlspecialchars($vehicle['vin'] ?? '') ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Capacity (optional)</label>
                    <input
                        type="number"
                        name="capacity"
                        class="form-control"
                        value="<?= htmlspecialchars($vehicle['capacity'] ?? '') ?>"
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <?php $status = $vehicle['status'] ?? 'available'; ?>
                    <select name="status" class="form-select">
                        <option value="available"   <?= $status === 'available'   ? 'selected' : '' ?>>Available</option>
                        <option value="in_use"      <?= $status === 'in_use'      ? 'selected' : '' ?>>In Use</option>
                        <option value="maintenance" <?= $status === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Maintenance Status</label>
                    <?php $mstatus = $vehicle['maintenance_status'] ?? 'ok'; ?>
                    <select name="maintenance_status" class="form-select">
                        <option value="ok"              <?= $mstatus === 'ok'              ? 'selected' : '' ?>>OK</option>
                        <option value="inspection_due" <?= $mstatus === 'inspection_due' ? 'selected' : '' ?>>Inspection Due</option>
                        <option value="in_repair"      <?= $mstatus === 'in_repair'      ? 'selected' : '' ?>>In Repair</option>
                        <option value="out_of_service" <?= $mstatus === 'out_of_service' ? 'selected' : '' ?>>Out of Service</option>
                    </select>
                </div>

                <button class="btn btn-primary">Save Changes</button>
                <a href="/admin/vehicles/view/<?= htmlspecialchars($vehicle['id']) ?>"
                   class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
