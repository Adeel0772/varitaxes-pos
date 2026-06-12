<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= \Core\Auth::asset('css/app.css') ?>" rel="stylesheet">
    <meta name="csrf-token" content="<?= \Core\Auth::csrfToken() ?>">
</head>
<body>
<?php
$user = \Core\Auth::user();
$role = \Core\Auth::role();
$isSuperAdmin = \Core\Auth::isSuperAdmin() && !\Core\Auth::isImpersonating();
$isImpersonating = \Core\Auth::isImpersonating();
?>
<?php if ($isImpersonating): ?>
<div class="alert alert-warning mb-0 rounded-0 text-center py-2">
    <i class="bi bi-eye"></i> Read-only observer mode —
    <a href="<?= \Core\Auth::baseUrl('admin/stop-impersonate') ?>" class="alert-link">Exit</a>
</div>
<?php endif; ?>

<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<div class="d-flex" id="wrapper">
    <nav id="sidebar" class="sidebar bg-dark text-white">
        <div class="sidebar-header p-3 border-bottom border-secondary">
            <h5 class="mb-0"><i class="bi bi-shop"></i> <?= htmlspecialchars(APP_NAME) ?></h5>
            <?php if ($user): ?>
            <small class="text-muted"><?= htmlspecialchars($user['name'] ?? '') ?></small>
            <?php endif; ?>
        </div>
        <ul class="nav flex-column p-2">
            <?php if ($isSuperAdmin): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('admin/dashboard') ?>"><i class="bi bi-speedometer2"></i> Admin Dashboard</a></li>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('admin/shops') ?>"><i class="bi bi-building"></i> Shops</a></li>
            <?php else: ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('dashboard') ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
            <?php if (\Core\Auth::can('sales', 'create')): ?>
            <li class="nav-item"><a class="nav-link text-white fw-bold text-warning" href="<?= \Core\Auth::baseUrl('sales/pos') ?>"><i class="bi bi-cart-check"></i> POS Sale</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('products', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('products') ?>"><i class="bi bi-box-seam"></i> Products</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('inventory', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('inventory') ?>"><i class="bi bi-boxes"></i> Inventory</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('purchases', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('purchases') ?>"><i class="bi bi-truck"></i> Purchases</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('sales', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('sales') ?>"><i class="bi bi-receipt"></i> Sales</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('customers', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('customers') ?>"><i class="bi bi-people"></i> Customers</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('suppliers', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('suppliers') ?>"><i class="bi bi-building-add"></i> Suppliers</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('reports', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('reports') ?>"><i class="bi bi-graph-up"></i> Reports</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('barcodes', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('barcodes') ?>"><i class="bi bi-upc-scan"></i> Barcodes</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('users', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('users') ?>"><i class="bi bi-person-gear"></i> Users</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('settings', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('settings') ?>"><i class="bi bi-gear"></i> Settings</a></li>
            <?php endif; ?>
            <?php if (\Core\Auth::can('activity_log', 'read')): ?>
            <li class="nav-item"><a class="nav-link text-white" href="<?= \Core\Auth::baseUrl('activity-log') ?>"><i class="bi bi-journal-text"></i> Activity Log</a></li>
            <?php endif; ?>
            <?php endif; ?>
            <li class="nav-item mt-3 border-top border-secondary pt-2">
                <a class="nav-link text-danger" href="<?= \Core\Auth::baseUrl('auth/logout') ?>"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </li>
        </ul>
    </nav>

    <div id="page-content" class="flex-grow-1">
        <nav class="navbar navbar-light bg-white border-bottom px-3">
            <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle"><i class="bi bi-list"></i></button>
            <span class="navbar-text ms-2"><?= htmlspecialchars($pageTitle ?? '') ?></span>
        </nav>
        <main class="container-fluid p-4">
            <div id="toast-container" class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1100"></div>
            <?= $content ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= \Core\Auth::asset('js/app.js') ?>"></script>
<script src="<?= \Core\Auth::asset('js/product-form.js') ?>"></script>
<script src="<?= \Core\Auth::asset('js/pos.js') ?>"></script>
<script src="<?= \Core\Auth::asset('js/inventory.js') ?>"></script>
<script src="<?= \Core\Auth::asset('js/purchases-form.js') ?>"></script>
<?php if (!empty($flashSuccess = \Core\Auth::flash('success'))): ?>
<script>showToast('<?= addslashes($flashSuccess) ?>', 'success');</script>
<?php endif; ?>
<?php if (!empty($flashError = \Core\Auth::flash('error'))): ?>
<script>showToast('<?= addslashes($flashError) ?>', 'danger');</script>
<?php endif; ?>
</body>
</html>
