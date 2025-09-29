<?php
// File: admin/admin_header.php (Responsive Version - Corrected)
require_once 'auth_check.php';
?>
<!DOCTYPE html>
<html lang="lo">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { 
            font-family: 'Phetsarath OT', sans-serif; 
            background-color: #f8f9fa;
        }

        /* === START CSS CORRECTION === */
        .sidebar {
            width: 280px;
            min-height: 100vh;
        }
        
        .main-layout {
            display: flex;
            min-height: 100vh;
        }

        /* Desktop view: Sidebar is always visible */
        @media (min-width: 992px) {
            .sidebar-desktop {
                display: flex !important;
            }
            .main-content {
                width: calc(100% - 280px);
                flex-grow: 1;
            }
            .mobile-navbar {
                display: none;
            }
        }
        
        /* Mobile view: Sidebar becomes an offcanvas */
        @media (max-width: 991.98px) {
            .sidebar-desktop {
                display: none !important;
            }
            .main-content {
                width: 100%;
            }
        }
        /* === END CSS CORRECTION === */

        .card-icon {
            font-size: 3rem;
            opacity: 0.3;
        }
        .game-image {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 0.375rem;
        }
    </style>
</head>
<body>

<div class="main-layout">
    <div class="d-flex flex-column flex-shrink-0 p-3 text-white bg-dark sidebar sidebar-desktop">
        <a href="dashboard.php" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
            <i class="fas fa-gamepad fa-2x me-2"></i>
            <span class="fs-4">Admin Panel</span>
        </a>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
            <li><a href="manage_orders.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar me-2"></i> ຈັດການອໍເດີ້</a></li>
            <li><a href="manage_games.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_games.php' ? 'active' : ''; ?>"><i class="fas fa-cogs me-2"></i> ຈັດການເກມ & ແພັກເກັດ</a></li>
            <li><a href="manage_members.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_members.php' ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> ຈັດການສະມາຊິກ</a></li>
            <li><a href="manage_suppliers.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_suppliers.php' ? 'active' : ''; ?>"><i class="fas fa-server me-2"></i> ຈັດການ Supplier</a></li>
            <li><a href="import_from_api.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'import_from_api.php' ? 'active' : ''; ?>"><i class="fas fa-cloud-download-alt me-2"></i> Import from API</a></li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle fa-lg me-2"></i>
                <strong><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
            </ul>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-dark bg-dark mobile-navbar">
          <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu">
              <span class="navbar-toggler-icon"></span>
            </button>
          </div>
        </nav>
        
        <div class="offcanvas offcanvas-start bg-dark text-white sidebar" tabindex="-1" id="sidebarMenu">
          <div class="offcanvas-header">
             <a href="dashboard.php" class="d-flex align-items-center text-white text-decoration-none">
                <i class="fas fa-gamepad fa-2x me-2"></i><span class="fs-4">Admin Panel</span>
            </a>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
          </div>
          <div class="offcanvas-body d-flex flex-column p-3 pt-0">
            <hr>
            <ul class="nav nav-pills flex-column mb-auto">
                <li class="nav-item"><a href="dashboard.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                <li><a href="manage_orders.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar me-2"></i> ຈັດການອໍເດີ້</a></li>
                <li><a href="manage_games.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_games.php' ? 'active' : ''; ?>"><i class="fas fa-cogs me-2"></i> ຈັດການເກມ & ແພັກເກັດ</a></li>
                <li><a href="manage_members.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_members.php' ? 'active' : ''; ?>"><i class="fas fa-users me-2"></i> ຈັດການສະມາຊິກ</a></li>
                <li><a href="manage_suppliers.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'manage_suppliers.php' ? 'active' : ''; ?>"><i class="fas fa-server me-2"></i> ຈັດການ Supplier</a></li>
                <li><a href="import_from_api.php" class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'import_from_api.php' ? 'active' : ''; ?>"><i class="fas fa-cloud-download-alt me-2"></i> Import from API</a></li>
            </ul>
            <hr>
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle fa-lg me-2"></i>
                    <strong><?php echo htmlspecialchars($_SESSION["admin_username"]); ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                    <li><a class="dropdown-item" href="logout.php">ອອກຈາກລະບົບ</a></li>
                </ul>
            </div>
          </div>
        </div>

        <div class="p-4">