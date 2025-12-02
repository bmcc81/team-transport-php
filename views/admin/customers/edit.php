<?php $pageTitle = "Edit Customer"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-3">Edit Customer</h2>

            <form method="POST" action="/admin/customers/edit/<?= $customer['id'] ?>" class="card p-3 shadow-sm">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Company</label>
                        <input type="text" name="company" value="<?= htmlspecialchars($customer['customer_company_name']) ?>" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Contact Phone</label>
                        <input type="text" name="phone" value="<?= htmlspecialchars($customer['customer_contact_phone']) ?>" class="form-control">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($customer['customer_contact_first_name']) ?>" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($customer['customer_contact_last_name']) ?>" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($customer['customer_email']) ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" value="<?= htmlspecialchars($customer['customer_address']) ?>" class="form-control">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($customer['customer_contact_city']) ?>" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="postal" value="<?= htmlspecialchars($customer['customer_contact_postal_code']) ?>" class="form-control">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($customer['notes']) ?></textarea>
                </div>

                <button class="btn btn-primary">Save Changes</button>
                <a href="/admin/customers" class="btn btn-secondary">Cancel</a>

            </form>

        </main>
    </div>
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
