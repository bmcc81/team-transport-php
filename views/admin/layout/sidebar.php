<?php
// views/admin/layout/sidebar.php

$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

function is_active_admin(string $prefix, string $uri): string {
    return str_starts_with($uri, $prefix) ? 'active' : '';
}
?>

<div class="admin-sidebar border-end bg-light h-100 py-3">
    <div class="px-3 mb-3">
        <h6 class="text-uppercase text-muted small mb-1">Admin</h6>
        <div class="fw-semibold">Team Transport</div>
    </div>

    <ul class="nav nav-pills flex-column px-2 small">
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin', $currentUri) && $currentUri === '/admin' ? 'active' : '' ?>"
               href="/admin">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin/users', $currentUri) ?>"
               href="/admin/users">
                <i class="bi bi-people me-2"></i> Users
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin/customers', $currentUri) ?>"
               href="/admin/customers">
                <i class="bi bi-building me-2"></i> Customers
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin/drivers', $currentUri) ?>"
               href="/admin/drivers">
                <i class="bi bi-truck-front me-2"></i> Drivers
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center 
                <?= (
                    // highlight on /admin/vehicles or /admin/vehicles/*
                    str_starts_with($currentUri, '/admin/vehicles')
                    // but NOT on /admin/vehicles/map
                    && !str_starts_with($currentUri, '/admin/vehicles/map')
                ) ? 'active' : '' ?>"
                href="/admin/vehicles">
                <i class="bi bi-truck me-2"></i> Vehicles
            </a>
        </li>
        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= str_starts_with($currentUri, '/admin/vehicles/map') ? 'active' : '' ?>"
                href="/admin/vehicles/map">
                <i class="bi bi-map me-2"></i> Live Map
            </a>
        </li>


        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin/loads', $currentUri) ?>"
               href="/admin/loads">
                <i class="bi bi-box-seam me-2"></i> Loads
            </a>
        </li>

        <li><hr class="dropdown-divider my-2"></li>

        <li class="nav-item mb-1">
            <a class="nav-link d-flex align-items-center <?= is_active_admin('/admin/settings', $currentUri) ?>"
               href="/admin/settings">
                <i class="bi bi-gear me-2"></i> Settings
            </a>
        </li>
    </ul>
</div>
