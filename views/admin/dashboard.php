<?php $pageTitle = 'Admin Panel'; require __DIR__ . '/../layout/header.php'; ?>

<div class="container mt-4">
    <h1 class="h3 mb-4">Admin Panel</h1>

    <div class="row g-3">
        <div class="col-md-3">
            <a href="/admin/users" class="text-decoration-none">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-1">Manage Users</h5>
                    <p class="text-muted small mb-0">View, create, update users</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="/customers" class="text-decoration-none">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-1">Customers</h5>
                    <p class="text-muted small mb-0">Customer management</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="/drivers" class="text-decoration-none">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-1">Drivers</h5>
                    <p class="text-muted small mb-0">Driver records & profiles</p>
                </div>
            </a>
        </div>

        <div class="col-md-3">
            <a href="/loads" class="text-decoration-none">
                <div class="card shadow-sm p-3">
                    <h5 class="mb-1">Loads</h5>
                    <p class="text-muted small mb-0">All shipments & loads</p>
                </div>
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
