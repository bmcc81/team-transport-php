<?php
$pageTitle = "Create Load";
require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">Create Load</h2>

            <div class="alert alert-info">
                Creating load starting on: <strong><?= htmlspecialchars($date) ?></strong>
            </div>

            <form method="POST" action="/admin/loads/create">

                <input type="hidden" name="preselected_date" value="<?= htmlspecialchars($date) ?>">

                <!-- You can reuse your existing create form fields here -->

                <button class="btn btn-primary mt-3">
                    Save Load
                </button>
            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
