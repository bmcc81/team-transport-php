<?php

use App\Models\VehicleMaintenance;
use App\Database\Database;

// Page title (safe)
$pageTitle = "Vehicle Details — " . e($vehicle['vehicle_number'] ?? '');

// DB
$pdo = Database::pdo();

// Count overdue maintenance
$overdue = VehicleMaintenance::countDueOrOverdueForVehicle((int)$vehicle['id']);

// Fetch maintenance summary (next 5 by date)
$maintenance = $pdo->prepare("
    SELECT id, title, description, scheduled_date, completed_date, status
    FROM vehicle_maintenance
    WHERE vehicle_id = ?
    ORDER BY scheduled_date ASC
    LIMIT 5
");
$maintenance->execute([(int)$vehicle['id']]);
$maintenanceItems = $maintenance->fetchAll(PDO::FETCH_ASSOC);

// Driver map
$driverMap = [];
$drivers = $pdo->query("SELECT id, full_name FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($drivers as $d) {
    $driverMap[$d['id']] = $d['full_name'];
}

require __DIR__ . '/../../layout/header.php';

// Helpers for UI badges / status
$today = new DateTimeImmutable('today');

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

// Year badge color (newer = greener)
$yearValue = isset($vehicle['year']) ? (int)$vehicle['year'] : 0;
$yearBadgeClass = 'bg-secondary';
$currentYear = (int)$today->format('Y');

if ($yearValue >= $currentYear - 3) {
    $yearBadgeClass = 'bg-success';
} elseif ($yearValue >= $currentYear - 7) {
    $yearBadgeClass = 'bg-warning text-dark';
}

?>
<div class="container-fluid mt-3">
    <div class="row">

        <!-- Sidebar -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <main class="col-md-9 col-lg-10">

            <!-- Title row -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <div>
                    <h2 class="h4 mb-1">
                        <?= e($vehicle['vehicle_number'] ?? 'Vehicle') ?>
                    </h2>

                    <div class="d-flex flex-wrap align-items-center gap-2 small text-muted">
                        <!-- Status badge -->
                        <span class="badge <?= $vehicleStatusBadgeClass ?>">
                            <i class="bi bi-truck-front me-1"></i>
                            <?= e($vehicleStatusLabel) ?>
                        </span>

                        <!-- Year badge (if set) -->
                        <?php if ($yearValue > 0): ?>
                            <span class="badge <?= $yearBadgeClass ?>">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= e((string)$yearValue) ?>
                            </span>
                        <?php endif; ?>

                        <!-- Plate quick badge -->
                        <?php if (!empty($vehicle['license_plate'])): ?>
                            <span class="badge text-bg-light border">
                                <i class="bi bi-card-text me-1"></i>
                                Plate: <?= e($vehicle['license_plate']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/edit"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>

                    <form action="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/delete"
                          method="POST"
                          onsubmit="return confirm('Delete this vehicle?');">
                        <button class="btn btn-outline-danger btn-sm">
                            <i class="bi bi-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>

            <!-- MAINTENANCE ALERT -->
            <?php if ($overdue > 0): ?>
                <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm mb-4">
                    <div>
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong><?= e((string)$overdue) ?></strong> maintenance item(s) are overdue.
                    </div>
                    <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance"
                       class="btn btn-outline-dark btn-sm">
                        View Maintenance
                    </a>
                </div>
            <?php endif; ?>

            <!-- Main content layout -->
            <div class="row g-3">

                <!-- Vehicle card -->
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-semibold">
                            Vehicle Information
                        </div>

                        <div class="card-body">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted d-block">Vehicle Number</label>
                                    <div><?= e($vehicle['vehicle_number'] ?? '') ?></div>
                                </div>

                                <div class="col-md-6">
                                    <label class="fw-bold small text-muted d-block">Make &amp; Model</label>
                                    <div>
                                        <?= e(($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? '')) ?>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="fw-bold small text-muted d-block">Year</label>
                                    <div>
                                        <?php if ($yearValue > 0): ?>
                                            <span class="badge <?= $yearBadgeClass ?>">
                                                <?= e((string)$yearValue) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Not set</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="fw-bold small text-muted d-block">License Plate</label>
                                    <div><?= e($vehicle['license_plate'] ?? '') ?></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="fw-bold small text-muted d-block">VIN</label>
                                    <div><?= e($vehicle['vin'] ?? '—') ?></div>
                                </div>

                                <div class="col-md-4">
                                    <label class="fw-bold small text-muted d-block">Status</label>
                                    <div>
                                        <span class="badge <?= $vehicleStatusBadgeClass ?>">
                                            <?= e($vehicleStatusLabel) ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="col-md-8">
                                    <label class="fw-bold small text-muted d-block">Assigned Driver</label>
                                    <div>
                                        <?php if (!empty($vehicle['assigned_driver_id'])): ?>
                                            <?php
                                            $driverId = $vehicle['assigned_driver_id'];
                                            $driverLabel = $driverMap[$driverId] ?? ('Driver #' . $driverId);
                                            ?>
                                            <i class="bi bi-person-badge me-1"></i>
                                            <?= e($driverLabel) ?>
                                        <?php else: ?>
                                            <span class="text-muted">None assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance card -->
                <div class="col-12 col-xl-6">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex justify-content-between align-items-center bg-light fw-semibold">
                            <span>
                                <i class="bi bi-wrench-adjustable-circle me-2"></i>
                                Maintenance Overview
                            </span>
                            <a href="/admin/vehicles/<?= e((string)$vehicle['id']) ?>/maintenance"
                               class="btn btn-sm btn-outline-primary">
                                View All
                            </a>
                        </div>

                        <div class="card-body">
                            <?php if (empty($maintenanceItems)): ?>
                                <p class="text-muted mb-0">
                                    No upcoming or recent maintenance items for this vehicle.
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
                                        <?php foreach ($maintenanceItems as $item): ?>
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
                                                // planned / upcoming
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
                                                <td>
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
                                                        <div class="small text-muted text-truncate" style="max-width: 260px;">
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
                </div>

            </div> <!-- /.row g-3 -->

        </main>
    </div> <!-- /.row -->
</div> <!-- /.container-fluid -->
