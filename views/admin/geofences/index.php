<?php
$pageTitle = "Geofences";
require __DIR__ . "/../../layout/header.php";

/**
 * Build URLs that preserve current querystring (filters/pagination),
 * while overriding specific keys.
 */
function qs(array $overrides = []): string
{
    $q = $_GET;

    foreach ($overrides as $k => $v) {
        if ($v === null) {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }

    $query = http_build_query($q);
    return $query ? ('?' . $query) : '';
}

/**
 * Sort link helper that preserves filters.
 */
function sortLink(string $label, string $field, string $sort, string $order): string
{
    $currentOrder = strtoupper($order ?: 'ASC');
    $newOrder = ($sort === $field && $currentOrder === 'ASC') ? 'DESC' : 'ASC';

    $icon = '';
    if ($sort === $field) {
        $icon = ($currentOrder === 'ASC')
            ? ' <i class="bi bi-caret-up-fill"></i>'
            : ' <i class="bi bi-caret-down-fill"></i>';
    }

    $href = qs(['sort' => $field, 'order' => $newOrder, 'page' => 1]);

    return "<a href=\"{$href}\" class=\"text-decoration-none\">{$label}{$icon}</a>";
}
?>

<link rel="stylesheet" href="/assets/css/geofences.css">

<div class="container-fluid mt-3">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-12 col-md-3 col-lg-2 mb-3 mb-md-0">
            <?php require __DIR__ . '/../layout/sidebar.php'; ?>
        </div>

        <!-- MAIN -->
        <main class="col-md-9 col-lg-10">

            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">
                    <i class="bi bi-map"></i> Geofences
                </h2>

                <a href="/admin/geofences/create" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Add Geofence
                </a>
            </div>

            <!-- FILTERS -->
            <form class="card shadow-sm mb-3" method="GET">
                <div class="card-body row g-3 align-items-end">

                    <div class="col-sm-4">
                        <label class="form-label">Search</label>
                        <input type="text"
                               class="form-control"
                               name="search"
                               placeholder="Search by name or description"
                               value="<?= htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="circle"    <?= ($type ?? '') === 'circle' ? 'selected' : '' ?>>Circle</option>
                            <option value="polygon"   <?= ($type ?? '') === 'polygon' ? 'selected' : '' ?>>Polygon</option>
                            <option value="rectangle" <?= ($type ?? '') === 'rectangle' ? 'selected' : '' ?>>Rectangle</option>
                        </select>
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="active">
                            <option value="">All</option>
                            <option value="1" <?= ($active ?? '') === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ($active ?? '') === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Per Page</label>
                        <select class="form-select" name="per_page">
                            <?php foreach ([10, 25, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= ((int)($pageSize ?? 10) === (int)$n) ? 'selected' : '' ?>>
                                    <?= $n ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-sm-2 text-end">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Apply
                        </button>
                        <a class="btn btn-link w-100 mt-1 p-0" href="/admin/geofences">Reset</a>
                    </div>

                </div>
            </form>

            <!-- TABLE -->
            <div class="card shadow-sm">
                <div class="card-body p-0">

                    <?php if (empty($geofences)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-signpost fs-2 d-block mb-2"></i>
                            No geofences found.
                        </div>

                    <?php else: ?>
                        <table class="table table-hover table-sm mb-0 align-middle w-100">
                            <thead class="table-light">
                            <tr>
                                <th><?= sortLink("Name", "name", $sort ?? '', $order ?? '') ?></th>
                                <th><?= sortLink("Type", "type", $sort ?? '', $order ?? '') ?></th>
                                <th>Vehicles</th>
                                <th><?= sortLink("Status", "active", $sort ?? '', $order ?? '') ?></th>
                                <th><?= sortLink("Created", "created_at", $sort ?? '', $order ?? '') ?></th>
                                <th class="text-end">Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php foreach ($geofences as $g): ?>
                                <tr>

                                    <!-- NAME -->
                                    <td class="fw-semibold">
                                        <i class="bi bi-geo-alt text-primary me-1"></i>
                                        <?= htmlspecialchars($g['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>

                                        <?php
                                        $desc = trim((string)($g['geo_description'] ?? ''));
                                        if ($desc !== '') {
                                            $short = (strlen($desc) > 90) ? (substr($desc, 0, 90) . '…') : $desc;
                                            echo '<div class="text-muted small">' . htmlspecialchars($short, ENT_QUOTES, 'UTF-8') . '</div>';
                                        }
                                        ?>
                                    </td>

                                    <!-- TYPE -->
                                    <td>
                                        <?php $t = (string)($g['type'] ?? ''); ?>
                                        <?php if ($t === 'circle'): ?>
                                            <span class="badge rounded-pill text-bg-info">
                                                <i class="bi bi-circle"></i> Circle
                                            </span>
                                        <?php elseif ($t === 'polygon'): ?>
                                            <span class="badge rounded-pill text-bg-primary">
                                                <i class="bi bi-vector-pen"></i> Polygon
                                            </span>
                                        <?php elseif ($t === 'rectangle'): ?>
                                            <span class="badge rounded-pill text-bg-dark">
                                                <i class="bi bi-bounding-box"></i> Rectangle
                                            </span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill text-bg-secondary">Unknown</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- VEHICLES -->
                                    <td>
                                        <?php $appliesAll = (int)($g['applies_to_all_vehicles'] ?? 1); ?>
                                        <?php if ($appliesAll): ?>
                                            <span class="badge rounded-pill text-bg-success">All Vehicles</span>
                                        <?php else: ?>
                                            <span class="badge rounded-pill text-bg-warning">Specific</span>
                                            <?php if (!empty($g['vehicle_count'])): ?>
                                                <span class="text-muted small ms-1">
                                                    (<?= (int)$g['vehicle_count'] ?>)
                                                </span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>

                                    <!-- STATUS -->
                                    <td>
                                        <?php if (!empty($g['active'])): ?>
                                            <span class="badge text-bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- CREATED -->
                                    <td class="text-muted">
                                        <?php
                                        $createdAt = $g['created_at'] ?? null;
                                        echo $createdAt ? date("Y-m-d H:i", strtotime($createdAt)) : '—';
                                        ?>
                                    </td>

                                    <!-- ACTIONS -->
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group" aria-label="Geofence actions">
                                            <a href="/admin/geofences/edit/<?= (int)$g['id'] ?>"
                                               class="btn btn-outline-primary"
                                               title="Edit geofence">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>

                                            <a href="/admin/geofences/delete/<?= (int)$g['id'] ?>"
                                               class="btn btn-outline-danger"
                                               title="Delete geofence"
                                               onclick="return confirm('Delete this geofence?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>

                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>

                </div>
            </div>

            <!-- PAGINATION -->
            <?php if (($totalPages ?? 1) > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination pagination-sm mb-0">

                        <!-- Prev -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= qs(['page' => max(1, $page - 1)]) ?>">Prev</a>
                        </li>

                        <?php
                        // Windowed pagination (show first, last, and a window around current page)
                        $p = (int)$page;
                        $tp = (int)$totalPages;
                        $window = 2;

                        $pages = [];
                        for ($i = 1; $i <= $tp; $i++) {
                            if ($i === 1 || $i === $tp || ($i >= $p - $window && $i <= $p + $window)) {
                                $pages[] = $i;
                            }
                        }
                        $pages = array_values(array_unique($pages));
                        sort($pages);

                        $prevShown = 0;
                        foreach ($pages as $i) {
                            if ($prevShown && $i > $prevShown + 1) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                            $activeClass = ($i === $p) ? 'active' : '';
                            echo '<li class="page-item ' . $activeClass . '"><a class="page-link" href="' . qs(['page' => $i]) . '">' . $i . '</a></li>';
                            $prevShown = $i;
                        }
                        ?>

                        <!-- Next -->
                        <li class="page-item <?= $page >= (int)$totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= qs(['page' => min((int)$totalPages, $page + 1)]) ?>">Next</a>
                        </li>

                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
