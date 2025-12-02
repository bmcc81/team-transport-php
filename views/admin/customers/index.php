<?php $pageTitle = "Customers"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Customers</h2>
                <!-- later: add "Add Customer" button -->
            </div>

            <div class="card shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>City</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['customer_company_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($c['customer_contact_first_name'] ?? '') . ' ' . ($c['customer_contact_last_name'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($c['customer_contact_email'] ?? '') ?></td>
                                <td><?= htmlspecialchars($c['customer_contact_city'] ?? '') ?></td>
                                <td class="text-end">
                                    <!-- placeholders for future edit/view -->
                                    <a href="#" class="btn btn-sm btn-outline-secondary disabled">View</a>
                                </td>
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
