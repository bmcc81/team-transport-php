<?php
/** @var array $customers */
/** @var array $drivers */
/** @var array $vehicles */
/** @var array $errors */

$pageTitle = "Create Load";
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
                    <h2 class="h4 mb-0">Create Load</h2>
                    <small class="text-muted">Plan a new freight movement</small>
                </div>
            </div>

            <?php if (!empty($errors)) { ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e) { ?>
                            <li><?= e($e) ?></li>
                        <?php } ?>
                    </ul>
                </div>
            <?php } ?>

            <form method="POST" action="/admin/loads/create">
                <?php require __DIR__ . '/_form_fields.php'; ?>
            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>

<script src="/assets/js/load-form.js"></script>
<script src="/assets/js/load-stops.js"></script>
