<?php
$pickup = $load['pickup_address'] ?? '';
$delivery = $load['delivery_address'] ?? '';
?>

<?php if (empty($pickup) && empty($delivery)): ?>
    <p class="text-muted mb-0">
        No pickup/delivery addresses set — map not available.
    </p>
<?php else: ?>
    <div
        id="load-map"
        class="border rounded"
        style="width: 100%; height: 320px;"
        data-pickup="<?= e($pickup) ?>"
        data-delivery="<?= e($delivery) ?>"
    ></div>
    <small class="text-muted d-block mt-2">
        The map uses pickup and delivery addresses to visualize the route.
    </small>
<?php endif; ?>
