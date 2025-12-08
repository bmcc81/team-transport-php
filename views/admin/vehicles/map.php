<?php
$pageTitle = "Live Vehicle Map";
require __DIR__ . '/../../layout/header.php';

$focusId = isset($_GET['focus']) ? (int)$_GET['focus'] : null;
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<style>
#map {
    height: calc(100vh - 120px);
    border-radius: 8px;
    border: 1px solid #ddd;
}

.vehicle-list {
    max-height: calc(100vh - 140px);
    overflow-y: auto;
}

.vehicle-item {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.vehicle-item:hover {
    background: #f5f5f5;
}

.vehicle-item.active {
    background: #e9f5ff;
    border-left: 4px solid #0d6efd;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 6px;
}

.status-online { background: #28a745; }
.status-offline { background: #dc3545; }
</style>

<div class="container-fluid mt-3">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- MAIN CONTENT -->
        <main class="col-md-9 col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4">Live Vehicle Map</h2>
            </div>

            <div class="row">
                <!-- VEHICLE LIST -->
                <div class="col-12 col-md-4 col-lg-3 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-header">
                            <strong>Vehicles</strong>
                        </div>

                        <div class="vehicle-list" id="vehicle-list">
                            <!-- This list will now be FILLED by AJAX -->
                            <div class="text-muted text-center py-3">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- MAP -->
                <div class="col-12 col-md-8 col-lg-9">
                    <div id="map"></div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
// Expose focus ID to JS
window.__FOCUS_ID__ = <?= $focusId ? (int)$focusId : 'null' ?>;
</script>

<script src="/assets/js/live-map.js"></script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
