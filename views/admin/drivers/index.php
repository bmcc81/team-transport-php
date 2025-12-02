<?php $pageTitle = "Drivers"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">

    <div class="row">

        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">Drivers</h2>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($drivers as $d): ?>
                            <tr>
                                <td><?= htmlspecialchars($d['full_name']) ?></td>
                                <td><?= htmlspecialchars($d['username']) ?></td>
                                <td><?= htmlspecialchars($d['email']) ?></td>
                                <td>
                                    <a href="/admin/drivers/view/<?= $d['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                       <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
        
    </div>

</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
