<?php
// views/layout/footer.php

// If header.php opened the admin wrapper (<section ...>), close it here.
$role       = $_SESSION['user']['role'] ?? null;
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';
$path       = parse_url($currentUri, PHP_URL_PATH) ?: '/';

$isAdminRoute = str_starts_with($path, '/admin');
$isAdminRole  = in_array((string)$role, ['admin', 'dispatcher'], true);
$showAdminUi  = !empty($_SESSION['user']['username']) && $isAdminRoute && $isAdminRole;

if ($showAdminUi) {
    echo "</section></div>";
}
?>

</main>

<footer class="mt-auto py-3 border-top bg-white">
    <div class="container-fluid text-center small text-muted">
        Team Transport &mdash; <?= date('Y') ?>
    </div>
</footer>

<!-- jQuery + Select2 loaded locally -->
<link rel="stylesheet" href="/assets/css/select2.min.css">
<script src="/assets/js/jquery-3.7.1.min.js"></script>
<script src="/assets/js/select2.min.js"></script>

<script src="/assets/js/geofence-vehicles.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/app.js"></script>

<?php if (!empty($pageScripts)) echo $pageScripts; ?>
</body>
</html>
