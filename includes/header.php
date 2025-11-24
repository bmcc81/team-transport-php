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
    <title>TeamTransport</title>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="/styles/css/bootstrap.min.css">

    <!-- Icons -->
    <link rel="stylesheet" href="/styles/css/bootstrap-icons/bootstrap-icons.css">

    <!-- Custom Branding -->
    <style>

        /* BRAND COLORS */
        :root {
            --brand-blue: #0066cc;
            --brand-navy: #0b1e39;
            --brand-green: #1dbf73;
            --brand-gray: #f5f7fa;
        }

        body {
            background: var(--brand-gray);
        }

        /* NAVBAR */
        .tt-navbar {
            background: var(--brand-navy);
            padding: 0.75rem 1rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.35);
        }

        .tt-brand-text {
            font-weight: 700;
            letter-spacing: 0.5px;
            font-size: 1.4rem;
            color: #fff;
        }

        .tt-nav-link {
            color: #d4e1f7 !important;
            font-weight: 500;
            padding: 0.75rem 1rem !important;
            font-size: 0.95rem;
        }

        .tt-nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
        }

        .tt-nav-link.active {
            background: var(--brand-blue) !important;
            color: #fff !important;
            border-radius: 8px;
        }

        /* USER INFO */
        .tt-user-badge {
            color: #fff;
            font-size: 0.9rem;
        }

        /* LOGO */
        .tt-logo {
            height: 42px;
            margin-right: 12px;
        }

        .navbar-toggler {
            border-color: #ffffff80;
        }
        .navbar-toggler-icon {
            filter: invert(1);
        }

        .body_marg-btm {
            margin-bottom: 0.8rem;
        }

    </style>

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
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>

                <!-- Loads -->
                <li class="nav-item">
                    <a class="nav-link tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], '/loads') ? 'active' : '' ?>"
                       href="/views/loads/loads_list.php">
                        <i class="bi bi-box-seam"></i> Loads
                    </a>
                </li>

                <!-- Customers -->
                <li class="nav-item">
                    <a class="nav-link tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'customer') ? 'active' : '' ?>"
                       href="/includes/create_customer_view.php">
                        <i class="bi bi-people"></i> Create Customer
                    </a>
                </li>

                <!-- Users -->
                <?php if ($userRole === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link tt-nav-link <?= str_contains($_SERVER['REQUEST_URI'], 'manage_users') ? 'active' : '' ?>"
                           href="/views/manage_users.php">
                            <i class="bi bi-person-gear"></i> Users
                        </a>
                    </li>
                <?php endif; ?>

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
