<?php $pageTitle = "Add User"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container-fluid mt-4">

    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- Main content -->
        <div class="col-12 col-md-9 col-lg-10">

            <div class="card shadow-sm p-3">
                <form method="POST" action="/admin/users/store">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input required name="full_name" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input required name="username" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input required type="email" name="email" class="form-control">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="admin">Admin</option>
                                <option value="dispatcher">Dispatcher</option>
                                <option value="driver">Driver</option>
                                <option value="client">Client</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input required type="password" name="password" class="form-control">
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="must_change_password" id="mustChange">
                                <label class="form-check-label" for="mustChange">
                                    Must change password
                                </label>
                            </div>
                        </div>

                    </div>

                    <div class="mt-4">
                        <button class="btn btn-primary">Create User</button>
                        <a href="/admin/users" class="btn btn-secondary">Cancel</a>
                    </div>

                </form>
            </div>

        </div>
    </div>
    <h2 class="h4 mb-3">Add User</h2>

   
</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
