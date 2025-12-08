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
                    <div><strong><?= $overdue ?></strong> maintenance item(s) are overdue.</div>
                    <a href="/admin/vehicles/<?= $vehicle['id'] ?>/maintenance"
                       class="btn btn-outline-dark btn-sm">View Maintenance</a>
                </div>
            <?php endif; ?>

            <!-- Vehicle card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold">Vehicle Information</div>

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

            <!-- Maintenance summary -->
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
                               class="btn btn-outline-primary btn-sm mt-2">Add First Maintenance</a>
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
                               class="btn btn-outline-primary btn-sm">View All Maintenance</a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

            <!-- GPS + MAP -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light fw-semibold d-flex justify-content-between align-items-center">
                    GPS Coordinates
                    <button id="btnUseMyLocation" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-geo"></i> Use My Location
                    </button>
                </div>

                <div class="card-body">

                    <form class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Latitude</label>
                            <input type="text"
                                name="latitude"
                                class="form-control"
                                value="<?= htmlspecialchars($vehicle['latitude'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Longitude</label>
                            <input type="text"
                                name="longitude"
                                class="form-control"
                                value="<?= htmlspecialchars($vehicle['longitude'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <button id="gps-save-btn" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save GPS
                            </button>
                        </div>

                    </form>

                    <!-- LIVE MAP -->
                    <div id="vehicleMap" style="height: 650px;" class="mt-3 rounded border"></div>

                </div>
            </div>

        </main>

    </div>
</div>


<!-- Leaflet -->
<link rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<!-- Unified Map + GPS Auto-Save -->
<script>
document.addEventListener("DOMContentLoaded", () => {

    const latInput = document.querySelector("input[name='latitude']");
    const lonInput = document.querySelector("input[name='longitude']");
    const saveButton = document.querySelector("#gps-save-btn");
    const useMyLocationBtn = document.getElementById("btnUseMyLocation");

    let lat = parseFloat(latInput.value || 45.5019);
    let lon = parseFloat(lonInput.value || -73.5674);
    let autoSaveTimer = null;

    // Map init
    const map = L.map("vehicleMap").setView([lat, lon], 12);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19
    }).addTo(map);

    // Draggable marker
    let marker = L.marker([lat, lon], { draggable: true }).addTo(map);

    marker.on("dragend", function () {
        const pos = marker.getLatLng();
        latInput.value = pos.lat.toFixed(6);
        lonInput.value = pos.lng.toFixed(6);
        startAutoSave();
    });

    // Manual save
    saveButton.addEventListener("click", (e) => {
        e.preventDefault();
        saveGPS();
    });

    // Auto-save
    function startAutoSave() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(saveGPS, 800);
    }

    function saveGPS() {
        const formData = new FormData();
        formData.append("latitude", latInput.value);
        formData.append("longitude", lonInput.value);

        fetch(window.location.pathname.replace('/view/', '/') + '/gps', {
            method: "POST",
            body: formData
        }).then(() => console.log("GPS auto-saved"));
    }

    // Use My Location
    useMyLocationBtn.addEventListener("click", () => {
        if (!navigator.geolocation) {
            alert("Geolocation not supported.");
            return;
        }

        useMyLocationBtn.disabled = true;
        useMyLocationBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Locating...`;

        navigator.geolocation.getCurrentPosition(pos => {
            const { latitude, longitude } = pos.coords;

            latInput.value = latitude.toFixed(6);
            lonInput.value = longitude.toFixed(6);

            marker.setLatLng([latitude, longitude]);
            map.setView([latitude, longitude], 14);

            saveGPS();

            useMyLocationBtn.innerHTML = "Use My Location";
            useMyLocationBtn.disabled = false;

        }, err => {
            alert("Unable to access location: " + err.message);
            useMyLocationBtn.innerHTML = "Use My Location";
            useMyLocationBtn.disabled = false;
        });
    });

});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
