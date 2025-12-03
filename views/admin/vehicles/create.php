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
            <h2 class="h4 mb-3">Add Vehicle</h2>

            <form method="POST" action="/admin/vehicles/create" class="card p-4 shadow-sm">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vehicle Number</label>
                        <input type="text" name="vehicle_number" class="form-control" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">License Plate</label>
                        <input type="text" name="license_plate" class="form-control" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Make</label>
                        <input type="text" name="make" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Model</label>
                        <input type="text" name="model" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">VIN (optional)</label>
                    <input type="text" name="vin" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Capacity (optional)</label>
                    <input type="number" name="capacity" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="available">Available</option>
                        <option value="in_service">In Service</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Maintenance Status</label>
                    <select name="maintenance_status" class="form-select">
                        <option value="ok">OK</option>
                        <option value="inspection_due">Inspection Due</option>
                        <option value="in_repair">In Repair</option>
                        <option value="out_of_service">Out of Service</option>
                    </select>
                </div>

                <button class="btn btn-primary">Save Vehicle</button>
                <a href="/admin/vehicles" class="btn btn-secondary ms-2">Cancel</a>
            </form>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
