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
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['customer_company_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars(
                                        ($c['customer_contact_first_name'] ?? '') . ' ' . ($c['customer_contact_last_name'] ?? '')
                                    ) ?></td>
                                <td><?= htmlspecialchars($c['customer_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['customer_contact_city'] ?? '') ?></td>
                                <td>
                                    <a href="/admin/customers/edit/<?= $c['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="/admin/customers/delete/<?= $c['id'] ?>"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Delete this customer?');">
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
