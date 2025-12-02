<?php
use App\Helpers\Breadcrumbs;
use App\Helpers\UserAvatar;

if (!isset($pageTitle)) {
    $pageTitle = 'Team Transport';
}

$authUser   = $_SESSION['username'] ?? null;
$role       = $_SESSION['role'] ?? null;
$currentUri = $_SERVER['REQUEST_URI'] ?? '/';

// Generate dynamic breadcrumbs
$breadcrumbs = Breadcrumbs::generate($currentUri);
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($pageTitle) ?> | Team Transport</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-fluid">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center" href="/dashboard">
            <i class="bi bi-truck-front me-2"></i>
            <strong>Team Transport</strong>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#mainNav" aria-controls="mainNav"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">

            <?php if ($authUser): ?>

                <!-- LEFT SIDE NAV LINKS -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link<?= ($currentUri === '/dashboard' || $currentUri === '/' ? ' active' : '') ?>"
                           href="/dashboard">
                            <i class="bi bi-speedometer2 me-1"></i>Dashboard
                        </a>
                    </li>

                    <?php switch ($role):

                        case 'admin': ?>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/loads') ? ' active' : '' ?>"
                                   href="/loads">
                                    <i class="bi bi-box-seam me-1"></i>Loads
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/admin') ? ' active' : '' ?>"
                                   href="/admin">
                                    <i class="bi bi-shield-lock me-1"></i>Admin
                                </a>
                            </li>
                            <?php break;

                        case 'dispatcher': ?>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/loads') ? ' active' : '' ?>"
                                   href="/loads">
                                    <i class="bi bi-box-seam me-1"></i>Loads
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/customers') ? ' active' : '' ?>"
                                   href="/customers">
                                    <i class="bi bi-building me-1"></i>Customers
                                </a>
                            </li>
                            <?php break;

                        case 'driver': ?>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/loads') ? ' active' : '' ?>"
                                   href="/loads?mine=1">
                                    <i class="bi bi-truck-front me-1"></i>My Loads
                                </a>
                            </li>
                            <?php break;

                        case 'client': ?>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/loads') ? ' active' : '' ?>"
                                   href="/loads?customer=me">
                                    <i class="bi bi-boxes me-1"></i>My Shipments
                                </a>
                            </li>
                            <?php break;

                        default: ?>
                            <li class="nav-item">
                                <a class="nav-link<?= str_starts_with($currentUri, '/loads') ? ' active' : '' ?>"
                                   href="/loads">
                                    <i class="bi bi-box-seam me-1"></i>Loads
                                </a>
                            </li>
                            <?php break;

                    endswitch; ?>
                </ul>

                <!-- RIGHT SIDE USER DROPDOWN -->
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">

                        <?php $initials = UserAvatar::initials($authUser); ?>

                        <a class="nav-link dropdown-toggle d-flex align-items-center"
                           href="#" id="userMenu" role="button"
                           data-bs-toggle="dropdown" aria-expanded="false">

                            <span class="avatar-circle me-2">
                                <?= htmlspecialchars($initials) ?>
                            </span>

                            <?= htmlspecialchars($authUser) ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li class="dropdown-item-text small text-muted">
                                Role: <?= htmlspecialchars((string)$role) ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="/profile">
                                    <i class="bi bi-person-badge me-1"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="/logout">
                                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                                </a>
                            </li>
                        </ul>

                    </li>
                </ul>

            <?php endif; ?>

        </div>
    </div>
</nav>

<!-- BREADCRUMBS -->
<?php if (!empty($breadcrumbs)): ?>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-white shadow-sm px-3 py-2 rounded-3 my-2 mx-3">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index === array_key_last($breadcrumbs)): ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($crumb['label']) ?>
                    </li>
                <?php else: ?>
                    <li class="breadcrumb-item">
                        <a href="<?= htmlspecialchars($crumb['url']) ?>">
                            <?= htmlspecialchars($crumb['label']) ?>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ol>
    </nav>
<?php endif; ?>

<main class="container-fluid py-3 py-md-4 flex-grow-1">
