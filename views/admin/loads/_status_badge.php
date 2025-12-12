<?php
// Can be called with $load (show) or $l (index)
$source = $load ?? $l ?? null;

if (!$source) {
    return;
}

$status = $source['load_status'] ?? 'pending';

$map = [
    'pending'     => ['secondary', 'bi-hourglass-split', 'Pending'],
    'assigned'    => ['info',      'bi-person-check',    'Assigned'],
    'in_transit'  => ['primary',   'bi-truck',           'In Transit'],
    'delivered'   => ['success',   'bi-check-circle',    'Delivered'],
    'cancelled'   => ['danger',    'bi-x-circle',        'Cancelled'],
];

if (!isset($map[$status])) {
    $map[$status] = ['secondary', 'bi-question-circle', ucfirst($status)];
}

[$class, $icon, $label] = $map[$status];
?>
<span class="badge bg-<?= e($class) ?>">
    <i class="bi <?= e($icon) ?> me-1"></i>
    <?= e($label) ?>
</span>
