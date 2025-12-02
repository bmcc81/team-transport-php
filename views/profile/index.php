<?php $pageTitle = 'My Profile'; require __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm mt-3">
            <div class="card-body">
                <h1 class="h5 mb-3">
                    <i class="bi bi-person-badge me-2"></i>My Profile
                </h1>
                <dl class="row small mb-0">
                    <dt class="col-4 text-muted">Full name</dt>
                    <dd class="col-8"><?= htmlspecialchars($user['full_name']) ?></dd>

                    <dt class="col-4 text-muted">Username</dt>
                    <dd class="col-8"><?= htmlspecialchars($user['username']) ?></dd>

                    <dt class="col-4 text-muted">Role</dt>
                    <dd class="col-8 text-capitalize"><?= htmlspecialchars($user['role']) ?></dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
