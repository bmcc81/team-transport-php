<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userName = $_SESSION['username'] ?? 'User';
$userRole = $_SESSION['role'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Team Transport</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/styles/css/bootstrap.min.css">

    <!-- Icons -->
    <link rel="stylesheet" href="/styles/css/bootstrap-icons/bootstrap-icons.css">

    <!--   THEME --->
    <link rel="stylesheet" href="/styles/theme.css">

    <script src="/styles/js/bootstrap.bundle.min.js"></script>

</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg tt-navbar">
    <div class="container-fluid">

        <!-- BRAND LOGO -->
        <a class="navbar-brand d-flex align-items-center" href="/dashboard.php">

            <img src="/images/logo.png" class="tt-logo" alt="Logo" onerror="this.style.display='none'">

            <span class="tt-brand-text">TeamTransport</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ttMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="ttMenu">

            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link tt-nav-link <?= $_SERVER['REQUEST_URI'] === '/dashboard.php' ? 'active' : '' ?>"
                       href="/dashboard.php">
                        <i class="bi bi-speedometer2"></i><span class="menu-title">Dashboard</span>
                    </a>
                </li>

                <!-- Loads Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/loads') ? 'active' : '' ?>"
                    href="#" id="loadsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-box-seam"></i><span class="menu-title">Loads</span>
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="loadsDropdown">

                        <li>
                            <a class="dropdown-item" href="/views/loads/loads_list.php">
                                <i class="bi bi-list-ul"></i><span class="menu-title">All Loads</span>
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="/views/loads/create_load_view.php">
                                <i class="bi bi-plus-circle"></i><span class="menu-title">Create Load</span> 
                            </a>
                        </li>

                    </ul>
                </li>

                <!-- Customers -->
                <li class="nav-item">
                    <a class="nav-link tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'customer') ? 'active' : '' ?>"
                       href="/views/create_customer_view.php">
                        <i class="bi bi-people"></i><span class="menu-title">Create Customer</span> 
                    </a>
                </li>

                <!-- Users -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'manage_users') ? 'active' : '' ?>"
                    href="#" id="manage_users_dropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-gear"></i><span class="menu-title">Users</span> 
                    </a>

                    <ul class="dropdown-menu" aria-labelledby="manage_users_dropdown">

                        <li>
                            <a class="dropdown-item" href="/views/manage_users.php">
                                <i class="bi bi-person-gear"></i><span class="menu-title">Users</span>
                            </a>
                        </li>

                        <li>
                            <a class="dropdown-item" href="/views/create_user_by_admin_view.php">
                                <i class="bi bi-person-add"></i><span class="menu-title">Create User</span>
                            </a>
                        </li>

                    </ul>
                </li>

            </ul>

            <!-- RIGHT SIDE USER INFO -->
            <div class="d-flex align-items-center gap-3">

                <span class="tt-user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($userName) ?> 
                    <small> (<?= $userRole ?>) </small>
                </span>

                <a href="/views/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>

            </div>

        </div>
    </div>
</nav>


<!-- PAGE WRAPPER -->
<div class="container-fluid mt-4 body_marg-btm">
