<div class="card shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
        Stops (Pickup, Delivery & Intermediate)
        <button type="button" class="btn btn-sm btn-success" id="add-stop-btn">
            <i class="bi bi-plus-circle"></i> Add Stop
        </button>
    </div>

    <div class="card-body" id="stops-container">

        <?php foreach ($stops as $i => $s): ?>
            <?php require __DIR__ . '/_stop_row.php'; ?>
        <?php endforeach; ?>

        <?php if (empty($stops)): ?>
            <?php require __DIR__ . '/_stop_row.php'; ?>
        <?php endif; ?>

    </div>
</div>

<?php require __DIR__ . '/_address_map_modal.php'; ?>
