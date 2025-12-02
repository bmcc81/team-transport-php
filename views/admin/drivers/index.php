<?php $pageTitle = "Drivers"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Drivers</h2>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($drivers as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['full_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['username'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($d['created_at'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
