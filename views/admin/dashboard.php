<?php
$pageTitle = "Admin Dashboard";
require __DIR__ . '/../layout/header.php';

use App\Database\Database;

$pdo = Database::pdo();

// Fetch stats
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalDrivers  = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn();
$totalLoads    = $pdo->query("SELECT COUNT(*) FROM loads")->fetchColumn();
$totalCustomers= $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();

// Maintenance alerts
$maintenanceDue = $pdo->query("
    SELECT COUNT(*) 
    FROM vehicle_maintenance
    WHERE status = 'planned'
      AND scheduled_date <= CURDATE()
")->fetchColumn();

$maintenanceWeek = $pdo->query("
    SELECT COUNT(*)
    FROM vehicle_maintenance
    WHERE status = 'planned'
      AND scheduled_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
")->fetchColumn();
?>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <h2 class="h4 mb-4">Dashboard</h2>

            <!-- Maintenance Alerts -->
            <div class="row mb-4">

                <!-- Overdue -->
                <div class="col-md-3 mb-3">
                    <div class="card border-danger shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Overdue Maintenance</h5>
                            <p class="display-6 text-danger fw-bold"><?= $maintenanceDue ?></p>
                            <a href="/admin/vehicles" class="small text-decoration-none">View Vehicles →</a>
                        </div>
                    </div>
                </div>

                <!-- Due This Week -->
                <div class="col-md-3 mb-3">
                    <div class="card border-warning shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Due This Week</h5>
                            <p class="display-6 text-warning fw-bold"><?= $maintenanceWeek ?></p>
                            <a href="/admin/vehicles" class="small text-decoration-none">View Vehicles →</a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Main Stats -->
            <div class="row mb-4">

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Vehicles</h6>
                            <div class="display-6 fw-bold"><?= $totalVehicles ?></div>
                            <a href="/admin/vehicles" class="small text-decoration-none">Manage Vehicles</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Drivers</h6>
                            <div class="display-6 fw-bold"><?= $totalDrivers ?></div>
                            <a href="/admin/drivers" class="small text-decoration-none">Manage Drivers</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Loads</h6>
                            <div class="display-6 fw-bold"><?= $totalLoads ?></div>
                            <a href="/admin/loads" class="small text-decoration-none">View Loads</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="text-muted">Customers</h6>
                            <div class="display-6 fw-bold"><?= $totalCustomers ?></div>
                            <a href="/admin/customers" class="small text-decoration-none">Manage Customers</a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Quick Actions Row -->
            <div class="card shadow-sm mb-5">
                <div class="card-header bg-light fw-semibold">
                    Quick Actions
                </div>
                <div class="card-body">

                    <div class="row g-3">

                        <div class="col-md-3">
                            <a href="/loads/create" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i> New Load
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="/admin/vehicles/create" class="btn btn-outline-primary w-100">
                                <i class="bi bi-truck"></i> Add Vehicle
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="/admin/drivers" class="btn btn-outline-primary w-100">
                                <i class="bi bi-person-badge"></i> View Drivers
                            </a>
                        </div>

                        <div class="col-md-3">
                            <a href="/admin/customers/create" class="btn btn-outline-primary w-100">
                                <i class="bi bi-building"></i> Add Customer
                            </a>
                        </div>

                    </div>

                </div>
            </div>

        </main>

    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
