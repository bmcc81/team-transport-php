<?php
/** @var array $load */
/** @var array $customers */
/** @var array $drivers */
/** @var array $vehicles */
/** @var array $activeVehicles */
/** @var array $selectedVehicleIds */
/** @var array $errors */

$loadId = (int)($load['load_id'] ?? 0);

$pageTitle = "Edit Load — " . e($load['load_number'] ?? ('L-' . $loadId));
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div>
                    <h2 class="h4 mb-0">Edit Load <?= e($load['load_number'] ?? ('L-' . $loadId)) ?></h2>
                    <small class="text-muted">Update load details, assignment and schedule</small>
                </div>

                <div class="d-flex gap-2">
                    <a href="/admin/loads/view?id=<?= $loadId ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Load
                    </a>
                    <a href="/admin/loads" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-list-ul"></i> Loads
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)) { ?>
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Please correct the following:</div>
                    <ul class="mb-0">
                        <?php foreach ($errors as $msg) { ?>
                            <li><?= e($msg) ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST"
                          action="/admin/loads/update?id=<?= $loadId ?>"
                          enctype="multipart/form-data"
                          class="needs-validation"
                          novalidate>

                        <input type="hidden" name="id" value="<?= $loadId ?>">

                        <?php require __DIR__ . '/_form_fields.php'; ?>

                    </form>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>

<script src="/assets/js/load-form.js"></script>
<script src="/assets/js/load-stops.js"></script>

