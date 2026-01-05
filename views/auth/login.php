<?php $pageTitle = 'Login'; require __DIR__ . '/../layout/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm mt-4">
            <div class="card-body">
                <h1 class="h4 mb-3 text-center">
                    <i class="bi bi-truck-front me-2"></i>Sign in
                </h1>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger py-2 small">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/login" novalidate>
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" name="username" class="form-control"
                               required autofocus
                               value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            <span>Password</span>
                        </label>
                        <input type="password" name="password" class="form-control" required>
                    </div>

                    <button class="btn btn-primary w-100 mt-2" type="submit">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </button>
                </form>
            </div>
        </div>

        <p class="text-center mt-3 small text-muted">
            Demo: admin / admin123, bmcc81 / bmcc81123, driver1 / driver1123, driver2 / driver2123, driver3 / driver3123, driver4 / driver4123, driver5 / driver5123, driver6 / driver6123, driver7 / driver7123, driver8 / driver8123, driver9 / driver9123, driver10 / driver101123, driver11 / driver11123, driver12 / driver12123, driver13 / driver13123, driver14 / driver14123, driver15 / driver15123, driver16 / driver16123, dispatcher1 / dispatcher1123, dispatcher2 / dispatcher2123
        </p>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
