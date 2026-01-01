<?php $pageTitle = "Customers"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4">Customers</h2>
                <a href="/admin/customers/create" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Add Customer
                </a>
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 1%;"></th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tbody>
                        <?php foreach ($customers as $c): ?>
                            <?php $rowId = 'cust-notes-' . (int)($c['id'] ?? 0); ?>
                            <tr>
                                <td class="align-middle">
                                    <button
                                        class="btn btn-sm btn-outline-secondary"
                                        type="button"
                                        data-bs-toggle="collapse"
                                        data-bs-target="#<?= $rowId ?>"
                                        aria-expanded="false"
                                        aria-controls="<?= $rowId ?>"
                                        title="Show notes"
                                    >
                                        <i class="bi bi-chevron-down js-cust-notes-chevron"></i>
                                    </button>
                                </td>

                                <td class="align-middle"><?= htmlspecialchars($c['company'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($c['contact'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($c['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="align-middle"><?= htmlspecialchars($c['city'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                                <td class="align-middle">
                                    <a href="/admin/customers/edit/<?= (int)($c['id'] ?? 0) ?>"
                                    class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <form action="/admin/customers/delete/<?= (int)($c['id'] ?? 0) ?>"
                                        method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this customer?');">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Collapsible notes row -->
                            <tr class="collapse" id="<?= $rowId ?>">
                                <td colspan="6" class="bg-light">
                                    <div class="py-2">
                                        <div class="small text-muted mb-1">Notes:</div>
                                        <div>
                                            <?= nl2br(htmlspecialchars($c['notes'] ?? '', ENT_QUOTES, 'UTF-8')) ?: '<span class="text-muted small">No notes.</span>' ?>
                                        </div>
                                    </div>
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
