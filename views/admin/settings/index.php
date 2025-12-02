<?php $pageTitle = "Settings"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">System Settings</h2>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Basic system info. Later we can make this editable and back it by a <code>system_settings</code> table.
                    </p>

                    <dl class="row mb-0 small">
                        <dt class="col-sm-3">Application</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($settings['app_name']) ?></dd>

                        <dt class="col-sm-3">Environment</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($settings['environment']) ?></dd>

                        <dt class="col-sm-3">Timezone</dt>
                        <dd class="col-sm-9"><?= htmlspecialchars($settings['timezone']) ?></dd>
                    </dl>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
