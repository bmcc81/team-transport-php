<?php
/** @var array $driver */
$pageTitle = "Edit Driver";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">Edit Driver</h2>

            <form method="post" class="card shadow-sm p-3" autocomplete="off">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Full Name</label>
                        <input type="text"
                               name="full_name"
                               class="form-control"
                               value="<?= htmlspecialchars($driver['full_name']) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text"
                               name="username"
                               class="form-control"
                               value="<?= htmlspecialchars($driver['username']) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email"
                               name="email"
                               class="form-control"
                               value="<?= htmlspecialchars($driver['email']) ?>"
                               required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="active"   <?= $driver['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $driver['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Assigned Vehicle:</strong>
                        <?php if ($driver['vehicle_id']): ?>
                            <?= htmlspecialchars($driver['vehicle_number']) ?>
                        <?php else: ?>
                            <span class="text-muted">None</span>
                        <?php endif; ?>
                    </div>

                    <a href="/admin/drivers/assign-vehicle/<?= $driver['id'] ?>"
                    class="btn btn-outline-primary btn-sm">
                        Assign Vehicle
                    </a>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    <a href="/admin/drivers" class="btn btn-outline-secondary">
                        Cancel
                    </a>

                    <button type="submit" class="btn btn-primary">
                        Save Changes
                    </button>
                </div>

            </form>
            <?php if (!empty($driver['updated_at'])): ?>
                <div class="mt-3 text-muted small">
                    Last updated on <?= htmlspecialchars($driver['updated_at']) ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
