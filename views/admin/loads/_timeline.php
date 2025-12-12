<?php

use App\Database\Database;

$loadId = $load['load_id'] ?? null;

if (!$loadId) {
    echo '<p class="text-muted mb-0">No load ID specified.</p>';
    return;
}

try {
    $pdo = Database::pdo();

    $stmt = $pdo->prepare("
        SELECT *
        FROM load_stops
        WHERE load_id = ?
        ORDER BY sequence ASC
    ");
    $stmt->execute([$loadId]);
    $stops = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {
    echo '<p class="text-muted mb-0">Stops timeline is not configured.</p>';
    return;
}

if (empty($stops)) {
    echo '<p class="text-muted mb-0">No stops defined for this load.</p>';
    return;
}
?>

<ul class="list-unstyled mb-0">
    <?php foreach ($stops as $stop): ?>
        <?php
        $type = $stop['type'] ?? 'stop'; // e.g. pickup / delivery / stop
        $label = ucfirst($type);
        $ts = $stop['scheduled_at'] ?? null;

        $badgeClass = 'bg-secondary';
        $icon = 'bi-geo-alt';

        if ($type === 'pickup') {
            $badgeClass = 'bg-info text-dark';
            $icon = 'bi-box-arrow-in-down';
            $label = 'Pickup';
        } elseif ($type === 'delivery') {
            $badgeClass = 'bg-success';
            $icon = 'bi-box-arrow-up';
            $label = 'Delivery';
        }

        $address = $stop['address'] ?? '';
        ?>
        <li class="d-flex mb-3">
            <div class="me-3">
                <span class="badge <?= $badgeClass ?>">
                    <i class="bi <?= $icon ?>"></i>
                </span>
            </div>
            <div>
                <div class="fw-semibold">
                    <?= e($label) ?>
                    <?php if (!empty($stop['name'])): ?>
                        — <?= e($stop['name']) ?>
                    <?php endif; ?>
                </div>
                <?php if (!empty($address)): ?>
                    <div class="small text-muted">
                        <?= e($address) ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($ts)): ?>
                    <div class="small text-muted">
                        Scheduled: <?= e($ts) ?>
                    </div>
                <?php endif; ?>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
