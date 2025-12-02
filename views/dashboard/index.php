<?php $pageTitle = 'Dashboard'; require __DIR__ . '/../layout/header.php'; ?>

<div class="row g-3">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase">Total Loads</span>
                    <i class="bi bi-box-seam fs-4 text-primary"></i>
                </div>
                <div class="h3 mb-1"><?= (int)($stats['loads_total'] ?? 0) ?></div>
                <a href="/loads" class="small mt-auto text-decoration-none">
                    View loads <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase">Pending</span>
                    <span class="badge bg-warning text-dark">Pending</span>
                </div>
                <div class="h3 mb-1"><?= (int)($stats['loads_pending'] ?? 0) ?></div>
                <a href="/loads?status=pending" class="small mt-auto text-decoration-none">
                    Filter pending <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase">In Transit</span>
                    <span class="badge bg-info text-dark">In transit</span>
                </div>
                <div class="h3 mb-1"><?= (int)($stats['loads_transit'] ?? 0) ?></div>
                <a href="/loads?status=in_transit" class="small mt-auto text-decoration-none">
                    Filter in transit <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6 col-xl-3">
        <div class="card shadow-sm h-100">
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted small text-uppercase">Delivered</span>
                    <span class="badge bg-success">Delivered</span>
                </div>
                <div class="h3 mb-1"><?= (int)($stats['loads_delivered'] ?? 0) ?></div>
                <a href="/loads?status=delivered" class="small mt-auto text-decoration-none">
                    Filter delivered <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
