<?php
$pageTitle = "Vehicle Details — " . htmlspecialchars($vehicle['vehicle_number']);
require __DIR__ . '/../../layout/header.php';

use App\Models\VehicleMaintenance;
use App\Database\Database;

$pdo = Database::pdo();

// Count overdue maintenance
$overdue = VehicleMaintenance::countDueOrOverdueForVehicle((int)$vehicle['id']);

// Fetch maintenance summary (next 5)
$maintenance = $pdo->prepare("
    SELECT *
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
foreach ($drivers as $d) $driverMap[$d['id']] = $d['full_name'];
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
                    <li class="breadcrumb-item active"><?= htmlspecialchars($vehicle['vehicle_number']) ?></li>
                </ol>
            </nav>

            <!-- Title row -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">
                    <?= htmlspecialchars($vehicle['vehicle_number']) ?>
                </h2>

                <div class="d-flex gap-2">
                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/edit"
                       class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-pencil"></i> Edit
                    </a>

                    <form action="/admin/vehicles/<?= $vehicle['id'] ?>/delete"
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
                <div class="alert alert-warning d-flex justify-content-between align-items-center shadow-sm">
                    <div>
                        <strong><?= $overdue ?></strong> maintenance item(s) are overdue.
                    </div>
                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                       class="btn btn-outline-dark btn-sm">
                        View Maintenance
                    </a>
                </div>
            <?php endif; ?>

            <!-- Vehicle card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold">
                    Vehicle Information
                </div>

                <div class="card-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="fw-bold">Vehicle Number</label>
                            <div><?= htmlspecialchars($vehicle['vehicle_number']) ?></div>
                        </div>

                        <div class="col-md-6">
                            <label class="fw-bold">Make & Model</label>
                            <div><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-bold">Year</label>
                            <div><?= htmlspecialchars($vehicle['year']) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-bold">License Plate</label>
                            <div><?= htmlspecialchars($vehicle['license_plate']) ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-bold">VIN</label>
                            <div><?= htmlspecialchars($vehicle['vin'] ?? '—') ?></div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-bold">Status</label>
                            <div>
                                <?php if ($vehicle['status'] === 'available'): ?>
                                    <span class="badge bg-success">Available</span>
                                <?php elseif ($vehicle['status'] === 'maintenance'): ?>
                                    <span class="badge bg-warning">Maintenance</span>
                                <?php elseif ($vehicle['status'] === 'in_service'): ?>
                                    <span class="badge bg-primary">In Service</span>
                                <?php else: ?>
                                    <?= htmlspecialchars($vehicle['status']) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="fw-bold">Assigned Driver</label>
                            <div>
                                <?php if ($vehicle['assigned_driver_id']): ?>
                                    <?= htmlspecialchars($driverMap[$vehicle['assigned_driver_id']] ?? 'Driver #' . $vehicle['assigned_driver_id']) ?>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Maintenance section -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Maintenance</span>
                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/create"
                       class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add Maintenance
                    </a>
                </div>

                <div class="card-body">

                    <?php if (empty($maintenanceItems)): ?>

                        <div class="text-muted text-center py-3">
                            No maintenance records.
                            <br>
                            <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance/create"
                               class="btn btn-outline-primary btn-sm mt-2">
                                Add First Maintenance
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Scheduled</th>
                                    <th>Status</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php foreach ($maintenanceItems as $m): ?>
                                    <?php $isOverdue = $m['status'] === 'planned' && $m['scheduled_date'] < date('Y-m-d'); ?>

                                    <tr class="<?= $isOverdue ? 'table-warning' : '' ?>">
                                        <td><?= htmlspecialchars($m['title']) ?></td>
                                        <td><?= htmlspecialchars($m['scheduled_date']) ?></td>
                                        <td>
                                            <?php if ($m['status'] === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($isOverdue): ?>
                                                <span class="badge bg-danger">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">Planned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                                               class="btn btn-outline-secondary btn-sm">
                                                <i class="bi bi-arrow-right"></i>
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                               class="btn btn-outline-primary btn-sm">
                                View All Maintenance
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <!-- GPS COORDINATES -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold">
                    GPS Coordinates
                </div>

                <div class="card-body">

                    <form method="POST" action="/admin/vehicles/<?= $vehicle['id'] ?>/gps" class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Latitude</label>
                            <input type="text"
                                name="latitude"
                                class="form-control"
                                value="<?= htmlspecialchars($vehicle['latitude'] ?? '') ?>"
                                placeholder="e.g., 45.5019">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Longitude</label>
                            <input type="text"
                                name="longitude"
                                class="form-control"
                                value="<?= htmlspecialchars($vehicle['longitude'] ?? '') ?>"
                                placeholder="e.g., -73.5674">
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <button class="btn btn-primary">
                                    <i class="bi bi-geo-alt"></i> Save GPS
                                </button>

                                <button type="button" id="use-my-location" class="btn btn-outline-secondary">
                                    <i class="bi bi-crosshair"></i> Use My Location
                                </button>
                            </div>

                            <div id="gps-loading" class="mt-2 text-muted small" style="display:none;">
                                <i class="bi bi-hourglass-split"></i> Detecting location...
                            </div>

                            <div id="gps-error" class="mt-2 text-danger small" style="display:none;"></div>
                        </div>

                    </form>

                    <?php if (!empty($vehicle['latitude']) && !empty($vehicle['longitude'])): ?>
                        <div class="mt-3 small text-muted">
                            Current location: <?= $vehicle['latitude'] ?>, <?= $vehicle['longitude'] ?>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- VEHICLE MAP -->
            <?php if (!empty($vehicle['latitude']) && !empty($vehicle['longitude'])): ?>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold">
                    Vehicle Location
                </div>
                <div class="card-body p-0">
                    <div id="vehicle-map" style="height: 350px; width: 100%;"></div>
                </div>
            </div>

            <!-- Leaflet CSS -->
            <link
                rel="stylesheet"
                href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
                integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
                crossorigin=""
            />

            <!-- Leaflet JS -->
            <script
                src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
                integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
                crossorigin=""
            ></script>

            <script>
            document.addEventListener("DOMContentLoaded", function() {

                // Pull PHP values into JS
                const lat = parseFloat("<?= $vehicle['latitude'] ?>");
                const lng = parseFloat("<?= $vehicle['longitude'] ?>");

                // Init map
                const map = L.map('vehicle-map').setView([lat, lng], 14);

                // Tile layer
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                // Marker with popup
                L.marker([lat, lng])
                    .addTo(map)
                    .bindPopup(
                        `<strong><?= htmlspecialchars($vehicle['vehicle_number']) ?></strong><br>` +
                        "<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?><br>" +
                        "Plate: <?= htmlspecialchars($vehicle['license_plate']) ?>"
                    )
                    .openPopup();
            });
            </script>

            <?php endif; ?>


        </main>

    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", () => {

    const btn = document.getElementById("use-my-location");
    const loading = document.getElementById("gps-loading");
    const errorBox = document.getElementById("gps-error");

    const latInput = document.querySelector("input[name='latitude']");
    const lngInput = document.querySelector("input[name='longitude']");

    btn.addEventListener("click", () => {
        errorBox.style.display = "none";
        loading.style.display = "block";

        if (!navigator.geolocation) {
            loading.style.display = "none";
            errorBox.innerText = "Geolocation is not supported on this device.";
            errorBox.style.display = "block";
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude.toFixed(7);
                const lng = pos.coords.longitude.toFixed(7);

                latInput.value = lat;
                lngInput.value = lng;

                loading.style.display = "none";
            },
            (err) => {
                loading.style.display = "none";
                errorBox.innerText = "Unable to get location: " + err.message;
                errorBox.style.display = "block";
            },
            {
                enableHighAccuracy: true,
                timeout: 8000,
                maximumAge: 0
            }
        );
    });

});
</script>


<?php require __DIR__ . '/../../layout/footer.php'; ?>
