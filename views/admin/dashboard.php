<?php $pageTitle = 'Admin Panel'; require __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/layout/sidebar.php'; ?>
        </div>

        <!-- Main admin content -->
        <div class="col-12 col-md-9 col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Admin Dashboard</h1>
            </div>

            <!-- Stats row -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Users</div>
                            <div class="fs-4 fw-semibold"><?= htmlspecialchars((string)($stats['users'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Customers</div>
                            <div class="fs-4 fw-semibold"><?= htmlspecialchars((string)($stats['customers'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Drivers</div>
                            <div class="fs-4 fw-semibold"><?= htmlspecialchars((string)($stats['drivers'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-0">
                        <div class="card-body py-3">
                            <div class="text-muted small">Active Loads</div>
                            <div class="fs-4 fw-semibold"><?= htmlspecialchars((string)($stats['loads'] ?? 0)) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick links -->
            <div class="row g-3">
                <div class="col-md-3 col-6">
                    <a href="/admin/users" class="text-decoration-none">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body py-3">
                                <div class="mb-1"><i class="bi bi-people me-1"></i>Users</div>
                                <div class="small text-muted">Manage all system users</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="/admin/customers" class="text-decoration-none">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body py-3">
                                <div class="mb-1"><i class="bi bi-building me-1"></i>Customers</div>
                                <div class="small text-muted">Companies & contacts</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="/admin/drivers" class="text-decoration-none">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body py-3">
                                <div class="mb-1"><i class="bi bi-truck-front me-1"></i>Drivers</div>
                                <div class="small text-muted">Fleet drivers overview</div>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-3 col-6">
                    <a href="/admin/settings" class="text-decoration-none">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-body py-3">
                                <div class="mb-1"><i class="bi bi-gear me-1"></i>Settings</div>
                                <div class="small text-muted">System configuration</div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
