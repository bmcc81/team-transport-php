<?php
/** @var array $driver */
/** @var array $vehicles */

$pageTitle = "Assign Vehicle";
require __DIR__ . '/../../layout/header.php';

foreach ($vehicles as &$v) {
    $v['status'] ??= 'unknown';
    $v['maintenance_status'] ??= 'unknown';
}
unset($v);
?>



<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">
                Assign Vehicle — <?= htmlspecialchars($driver['full_name']) ?>
            </h2>

            <form method="post" class="card shadow-sm p-3">

                <div class="mb-3">
                    <label class="form-label">Vehicle</label>
                    <select name="vehicle_id" class="form-select" required>
                        <option value="">— Select vehicle —</option>
                        <?php foreach ($vehicles as $v): ?>
                            <option value="<?= $v['id'] ?>"
                                <?= (
                                    $v['status'] === 'maintenance' ||
                                    $v['status'] === 'retired' ||
                                    $v['maintenance_status'] !== 'ok'
                                ) ? 'disabled' : '' ?>
                            >
                                <?= htmlspecialchars($v['vehicle_number']) ?>

                                <?php if ($v['maintenance_status'] !== 'ok'): ?>
                                    — maintenance
                                <?php elseif ($v['assigned_driver_id']): ?>
                                    — assigned to <?= htmlspecialchars($v['assigned_driver_name']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="/admin/drivers/edit/<?= $driver['id'] ?>"
                       class="btn btn-outline-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Assign
                    </button>
                </div>

            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
