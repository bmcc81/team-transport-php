<?php
$pageTitle = "Add Maintenance - " . ($vehicle['vehicle_number'] ?? '');
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">
                Add Maintenance for <?= htmlspecialchars($vehicle['vehicle_number']) ?>
            </h2>

            <form method="POST"
                  action="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/create"
                  class="card p-4 shadow-sm"
                  style="max-width: 600px;">

                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required
                           placeholder="Oil change, safety inspection, etc.">
                </div>

                <div class="mb-3">
                    <label class="form-label">Scheduled Date</label>
                    <input type="date" name="scheduled_date" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description (optional)</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Notes about this maintenance task"></textarea>
                </div>

                <button class="btn btn-primary">Save Maintenance</button>
                <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                   class="btn btn-secondary">
                    Cancel
                </a>

            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
