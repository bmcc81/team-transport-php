<?php
$pageTitle = "Geofences";
require __DIR__ . "/../../layout/header.php";
?>

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
                               value="<?= htmlspecialchars($search) ?>">
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type">
                            <option value="">All Types</option>
                            <option value="circle"  <?= $type === 'circle' ? 'selected' : '' ?>>Circle</option>
                            <option value="polygon" <?= $type === 'polygon' ? 'selected' : '' ?>>Polygon</option>
                        </select>
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="active">
                            <option value="">All</option>
                            <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $active === '0' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="col-sm-2">
                        <label class="form-label">Per Page</label>
                        <select class="form-select" name="per_page">
                            <?php foreach ([10, 25, 50, 100] as $n): ?>
                                <option value="<?= $n ?>" <?= ($pageSize == $n ? 'selected' : '') ?>>
                                    <?= $n ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-sm-2 text-end">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Apply
                        </button>
                    </div>

                </div>
            </form>

            <?php
            // SORT HELPER
            function sortLink($label, $field, $sort, $order)
            {
                $newOrder = ($sort === $field && strtoupper($order) === 'ASC') ? 'DESC' : 'ASC';
                $icon = '';

                if ($sort === $field) {
                    $icon = $order === 'ASC'
                        ? ' <i class="bi bi-caret-up-fill"></i>'
                        : ' <i class="bi bi-caret-down-fill"></i>';
                }

                return "<a href=\"?sort=$field&order=$newOrder\" class=\"text-decoration-none\">$label$icon</a>";
            }
            ?>

            <!-- TABLE -->
            <div class="card shadow-sm">
                <?php if (empty($geofences)): ?>

                    <div class="p-4 text-center text-muted">
                        <i class="bi bi-signpost fs-2 d-block mb-2"></i>
                        No geofences found.
                    </div>

                <?php else: ?>
                    <table class="table table-hover table-sm mb-0 align-middle w-100">
                        <thead class="table-light">
                        <tr>
                            <th><?= sortLink("Name", "name", $sort, $order) ?></th>
                            <th><?= sortLink("Type", "type", $sort, $order) ?></th>
                            <th>Vehicles</th>
                            <th><?= sortLink("Status", "active", $sort, $order) ?></th>
                            <th><?= sortLink("Created", "created_at", $sort, $order) ?></th>
                            <th class="text-end">Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($geofences as $g): ?>
                            <tr>

                                <!-- NAME -->
                                <td class="fw-semibold">
                                    <i class="bi bi-geo-alt text-primary me-1"></i>
                                    <?= htmlspecialchars($g['name']) ?>
                                </td>

                                <!-- TYPE -->
                                <td>
                                    <?php if ($g['type'] === 'circle'): ?>
                                        <span class="badge rounded-pill text-bg-info">
                                            <i class="bi bi-circle"></i> Circle
                                        </span>
                                    <?php else: ?>
                                        <span class="badge rounded-pill text-bg-primary">
                                            <i class="bi bi-vector-pen"></i> Polygon
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <!-- VEHICLES -->
                                <td>
                                    <?php $appliesAll = $g['applies_to_all_vehicles'] ?? 1; ?>
                                    <?php if ($appliesAll): ?>

                                        <span class="badge rounded-pill text-bg-success">All Vehicles</span>

                                    <?php else: ?>

                                        <span class="badge rounded-pill text-bg-warning">Specific</span>
                                        <?php if (!empty($g['vehicle_count'])): ?>
                                            <span class="text-muted small ms-1">
                                                (<?= $g['vehicle_count'] ?>)
                                            </span>
                                        <?php endif; ?>

                                    <?php endif; ?>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <?php if ($g['active']): ?>
                                        <span class="badge text-bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>

                                <!-- CREATED -->
                                <td class="text-muted">
                                    <?= date("Y-m-d H:i", strtotime($g['created_at'])) ?>
                                </td>

                                <!-- ACTIONS -->
                                <td class="text-end">
                                    <a href="/admin/geofences/edit/<?= $g['id'] ?>"
                                       class="btn btn-outline-primary btn-sm me-1">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="/admin/geofences/delete/<?= $g['id'] ?>"
                                       class="btn btn-outline-danger btn-sm"
                                       onclick="return confirm('Delete this geofence?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>

                <?php endif; ?>
            </div>

            <!-- PAGINATION -->
            <?php if ($totalPages > 1): ?>
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">

                        <!-- Previous -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                        </li>

                        <!-- Smart page window -->
                        <?php
                        $start = max(1, $page - 2);
                        $end   = min($totalPages, $page + 2);

                        if ($start > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                            if ($start > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                        }

                        for ($i = $start; $i <= $end; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;

                        if ($end < $totalPages) {
                            if ($end < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                        }
                        ?>

                        <!-- Next -->
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                        </li>

                    </ul>
                </nav>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require __DIR__ . "/../../layout/footer.php"; ?>
