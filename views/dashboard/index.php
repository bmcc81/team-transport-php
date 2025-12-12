<?php
$pageTitle = 'Dashboard';
require __DIR__ . '/../layout/header.php';
?>

<div class="row g-3">

    <!-- TOTAL LOADS -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 dashboard-kpi">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted text-uppercase small">Total Loads</div>
                        <div class="fw-bold fs-2">
                            <?= (int)($stats['loads_total'] ?? 0) ?>
                        </div>
                    </div>
                    <i class="bi bi-box-seam fs-3 text-primary"></i>
                </div>

                <div class="mt-auto pt-2 border-top">
                    <a href="/loads" class="small text-decoration-none fw-semibold">
                        View all loads <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- PENDING -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 dashboard-kpi">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted text-uppercase small">Pending</div>
                        <div class="fw-bold fs-2">
                            <?= (int)($stats['loads_pending'] ?? 0) ?>
                        </div>
                    </div>
                    <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                </div>

                <span class="badge bg-warning text-dark w-fit mb-2">Awaiting dispatch</span>

                <div class="mt-auto pt-2 border-top">
                    <a href="/loads?status=pending" class="small text-decoration-none fw-semibold">
                        View pending <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- IN TRANSIT -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 dashboard-kpi">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted text-uppercase small">In Transit</div>
                        <div class="fw-bold fs-2">
                            <?= (int)($stats['loads_transit'] ?? 0) ?>
                        </div>
                    </div>
                    <i class="bi bi-truck fs-3 text-info"></i>
                </div>

                <span class="badge bg-info text-dark w-fit mb-2">On the road</span>

                <div class="mt-auto pt-2 border-top">
                    <a href="/loads?status=in_transit" class="small text-decoration-none fw-semibold">
                        Track loads <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- DELIVERED -->
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100 dashboard-kpi">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted text-uppercase small">Delivered</div>
                        <div class="fw-bold fs-2">
                            <?= (int)($stats['loads_delivered'] ?? 0) ?>
                        </div>
                    </div>
                    <i class="bi bi-check-circle fs-3 text-success"></i>
                </div>

                <span class="badge bg-success w-fit mb-2">Completed</span>

                <div class="mt-auto pt-2 border-top">
                    <a href="/loads?status=delivered" class="small text-decoration-none fw-semibold">
                        View delivered <i class="bi bi-arrow-right-short"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
