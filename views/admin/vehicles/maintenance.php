<?php

use App\Database\Database;

/** @var array $vehicle */
/** @var array $items */

$today = new DateTimeImmutable('today');

$statusFilter = $_GET['status'] ?? 'all';  // all|planned|completed|overdue
$searchTerm   = trim($_GET['q'] ?? '');

// Normalize filter to known values
$allowedStatus = ['all', 'planned', 'completed', 'overdue'];
if (!in_array($statusFilter, $allowedStatus, true)) {
    $statusFilter = 'all';
}

// Compute summary counts
$total = count($items);
$plannedCount = 0;
$completedCount = 0;
$overdueCount = 0;

foreach ($items as $item) {
    $status = $item['status'] ?? 'planned';
    $scheduledDate = !empty($item['scheduled_date'])
        ? new DateTimeImmutable($item['scheduled_date'])
        : null;

    $isCompleted = ($status === 'completed');
    $isOverdue = false;

    if (!$isCompleted && $scheduledDate instanceof DateTimeImmutable) {
        $isOverdue = $scheduledDate < $today;
    }

    if ($isCompleted) {
        $completedCount++;
    } elseif ($isOverdue) {
        $overdueCount++;
    } else {
        $plannedCount++;
    }
}

// Filter items for table based on status + search
$filteredItems = array_filter($items, function (array $item) use ($statusFilter, $searchTerm, $today) {
    $status = $item['status'] ?? 'planned';
    $scheduledDate = !empty($item['scheduled_date'])
        ? new DateTimeImmutable($item['scheduled_date'])
        : null;

    $isCompleted = ($status === 'completed');
    $isOverdue = false;

    if (!$isCompleted && $scheduledDate instanceof DateTimeImmutable) {
        $isOverdue = $scheduledDate < $today;
    }

    // Status filter
    if ($statusFilter === 'planned' && ($isCompleted || $isOverdue)) {
        return false;
    }
    if ($statusFilter === 'completed' && !$isCompleted) {
        return false;
    }
    if ($statusFilter === 'overdue' && !$isOverdue) {
        return false;
    }

    // Search filter (title + description)
    if ($searchTerm !== '') {
        $haystack = strtolower(($item['title'] ?? '') . ' ' . ($item['description'] ?? ''));
        if (strpos($haystack, strtolower($searchTerm)) === false) {
            return false;
        }
    }

    return true;
});

// Vehicle status badge reused from profile view
$vehicleStatus = $vehicle['status'] ?? '';
$vehicleStatusBadgeClass = 'bg-secondary';
$vehicleStatusLabel = ucfirst($vehicleStatus ?: 'Unknown');

switch ($vehicleStatus) {
    case 'available':
        $vehicleStatusBadgeClass = 'bg-success';
        $vehicleStatusLabel = 'Available';
        break;
    case 'maintenance':
        $vehicleStatusBadgeClass = 'bg-warning text-dark';
        $vehicleStatusLabel = 'In Maintenance';
        break;
    case 'in_service':
        $vehicleStatusBadgeClass = 'bg-primary';
        $vehicleStatusLabel = 'In Service';
        break;
}

// Year badge
$todayYear = (int)$today->format('Y');
$yearValue = isset($vehicle['year']) ? (int)$vehicle['year'] : 0;
$yearBadgeClass = 'bg-secondary';
if ($yearValue >= $todayYear - 3) {
    $yearBadgeClass = 'bg-success';
} elseif ($yearValue >= $todayYear - 7) {
    $yearBadgeClass = 'bg-warning text-dark';
}

$pageTitle = 'Maintenance — ' . e($vehicle['vehicle_number'] ?? 'Vehicle');

require __DIR__ . '/../../layout/header.php';
?>

<div class="container-fluid mt-3">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/admin">Admin</a></li>
                    <li class="breadcrumb-item"><a href="/admin/vehicles">Vehicles</a></li>
                    <li class="breadcrumb-item">
                        <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>">
                            <?= e($vehicle['vehicle_number'] ?? 'Vehicle') ?>
                        </a>
                    </li>
                    <li class="breadcrumb-item active">Maintenance</li>
                </ol>
            </nav>

            <!-- Header row -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h4 mb-1">
                        Maintenance — <?= e($vehicle['vehicle_number'] ?? '') ?>
                    </h2>
                    <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                        <span class="badge <?= $vehicleStatusBadgeClass ?>">
                            <i class="bi bi-truck-front me-1"></i>
                            <?= e($vehicleStatusLabel) ?>
                        </span>

                        <?php if ($yearValue > 0): ?>
                            <span class="badge <?= $yearBadgeClass ?>">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= e((string)$yearValue) ?>
                            </span>
                        <?php endif; ?>

                        <?php if (!empty($vehicle['license_plate'])): ?>
                            <span class="badge text-bg-light border">
                                <i class="bi bi-card-text me-1"></i>
                                Plate: <?= e($vehicle['license_plate']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-left"></i> Back to Vehicle
                    </a>

                    <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance/create"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add Maintenance
                    </a>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card shadow-sm h-100">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Total</div>
                                    <div class="h5 mb-0"><?= e((string)$total) ?></div>
                                </div>
                                <i class="bi bi-list-task fs-3 text-muted"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card shadow-sm h-100 border-info">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Planned</div>
                                    <div class="h5 mb-0"><?= e((string)$plannedCount) ?></div>
                                </div>
                                <i class="bi bi-calendar-event fs-3 text-info"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card shadow-sm h-100 border-success">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Completed</div>
                                    <div class="h5 mb-0"><?= e((string)$completedCount) ?></div>
                                </div>
                                <i class="bi bi-check-circle fs-3 text-success"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-xl-2">
                    <div class="card shadow-sm h-100 border-danger">
                        <div class="card-body py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="small text-muted">Overdue</div>
                                    <div class="h5 mb-0"><?= e((string)$overdueCount) ?></div>
                                </div>
                                <i class="bi bi-exclamation-octagon fs-3 text-danger"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters row: tabs + search -->
            <?php
            // Helper to build status tab URLs preserving q
            $baseUrl = '/admin/vehicles/' . urlencode((string)$vehicle['id']) . '/maintenance';
            $qParam = $searchTerm !== '' ? '&q=' . urlencode($searchTerm) : '';
            ?>
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 gap-2">
                <!-- Tabs -->
                <ul class="nav nav-pills">
                    <?php
                    $tabs = [
                        'all'       => 'All',
                        'planned'   => 'Planned',
                        'completed' => 'Completed',
                        'overdue'   => 'Overdue',
                    ];
                    foreach ($tabs as $key => $label):
                        $active = $statusFilter === $key ? 'active' : '';
                        $url = $baseUrl . '?status=' . $key . $qParam;
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active ?>" href="<?= e($url) ?>">
                                <?= e($label) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Search -->
                <form class="d-flex gap-2" method="GET" action="<?= e($baseUrl) ?>">
                    <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
                    <input
                        type="search"
                        name="q"
                        class="form-control form-control-sm"
                        placeholder="Search maintenance..."
                        value="<?= e($searchTerm) ?>"
                    >
                    <button class="btn btn-outline-secondary btn-sm" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>

            <!-- Maintenance table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <?php if (empty($filteredItems)): ?>
                        <p class="text-muted text-center py-4 mb-0">
                            No maintenance items match the current filters.
                        </p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Status</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Scheduled</th>
                                        <th scope="col">Completed</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($filteredItems as $item): ?>
                                    <?php
                                    $status = $item['status'] ?? 'planned';
                                    $scheduledDate = !empty($item['scheduled_date'])
                                        ? new DateTimeImmutable($item['scheduled_date'])
                                        : null;
                                    $completedDate = !empty($item['completed_date'])
                                        ? new DateTimeImmutable($item['completed_date'])
                                        : null;

                                    $statusBadgeClass = 'bg-secondary';
                                    $statusIcon = 'bi-clock-history';
                                    $statusLabel = ucfirst($status);

                                    $isCompleted = ($status === 'completed');
                                    $isOverdue = false;

                                    if (!$isCompleted && $scheduledDate instanceof DateTimeImmutable) {
                                        $isOverdue = $scheduledDate < $today;
                                    }

                                    if ($isCompleted) {
                                        $statusBadgeClass = 'bg-success';
                                        $statusIcon = 'bi-check-circle-fill';
                                        $statusLabel = 'Completed';
                                    } elseif ($isOverdue) {
                                        $statusBadgeClass = 'bg-danger';
                                        $statusIcon = 'bi-exclamation-octagon-fill';
                                        $statusLabel = 'Overdue';
                                    } else {
                                        $statusBadgeClass = 'bg-info text-dark';
                                        $statusIcon = 'bi-calendar-event';
                                        $statusLabel = 'Planned';
                                    }

                                    $scheduledLabel = $scheduledDate
                                        ? $scheduledDate->format('Y-m-d')
                                        : '—';
                                    $completedLabel = $completedDate
                                        ? $completedDate->format('Y-m-d')
                                        : '—';
                                    ?>
                                    <tr>
                                        <td style="white-space: nowrap;">
                                            <span class="badge <?= $statusBadgeClass ?>">
                                                <i class="bi <?= $statusIcon ?> me-1"></i>
                                                <?= e($statusLabel) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= e($item['title'] ?? '') ?>
                                            </div>
                                            <?php if (!empty($item['description'])): ?>
                                                <div class="small text-muted text-truncate" style="max-width: 320px;">
                                                    <?= e($item['description']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= e($scheduledLabel) ?></td>
                                        <td><?= e($completedLabel) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>
