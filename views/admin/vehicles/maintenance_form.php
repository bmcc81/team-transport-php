<?php
$pageTitle = "Add Maintenance for " . htmlspecialchars($vehicle['vehicle_number']);
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

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
                        <a href="/admin/vehicles/<?= $vehicle['id'] ?>">
                            <?= htmlspecialchars($vehicle['vehicle_number']) ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Add Maintenance</li>
                </ol>
            </nav>

            <h2 class="h4 mb-3">Add Maintenance for <?= htmlspecialchars($vehicle['vehicle_number']) ?></h2>

            <form method="POST"
                  action="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/create"
                  class="card p-4 shadow-sm"
                  style="max-width: 650px;">

                <!-- Title -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Title</label>
                    <input type="text"
                           name="title"
                           class="form-control"
                           placeholder="Oil change, safety inspection, etc."
                           required>
                </div>

                <!-- Date -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Scheduled Date</label>
                    <input type="date"
                           name="scheduled_date"
                           class="form-control"
                           required>
                </div>

                <!-- Description -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Description (optional)</label>
                    <textarea name="description"
                              class="form-control"
                              rows="3"
                              placeholder="Notes about this maintenance task…"></textarea>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary flex-grow-1">
                        Save Maintenance
                    </button>

                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                       class="btn btn-secondary flex-grow-1">
                        Cancel
                    </a>
                </div>

            </form>

        </main>

    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
