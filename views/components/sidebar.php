<?php
$user = auth_user();
$isOwner = is_owner();
$currentUri = $_SERVER['REQUEST_URI'] ?? '';

if (!function_exists('is_active')) {
    function is_active(string $path, string $currentUri): string {
        $basePath = \App\Core\Router::getBasePath();
        $fullPath = $basePath . '/' . ltrim($path, '/');
        if ($path === '/' || $path === '') {
            return ($currentUri === $fullPath || $currentUri === $basePath || $currentUri === $basePath . '/') ? 'active' : '';
        }
        return (strpos($currentUri, $fullPath) !== false) ? 'active' : '';
    }
}
?>

<!-- Reusable Sidebar Content -->
<?php ob_start(); ?>
<div class="d-flex flex-column gap-1">
    <?php if ($isOwner): ?>
        <a href="<?= url('/dashboard') ?>" class="wk-nav-link <?= is_active('/dashboard', $currentUri) ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <div class="wk-sidebar-heading mt-2">Penjualan</div>
        <a href="<?= url('/pos') ?>" class="wk-nav-link <?= is_active('/pos', $currentUri) ?>">
            <i class="bi bi-cart3"></i> POS Kasir
        </a>
        <a href="<?= url('/transactions') ?>" class="wk-nav-link <?= is_active('/transactions', $currentUri) ?>">
            <i class="bi bi-receipt"></i> Transaksi
        </a>

        <div class="wk-sidebar-heading mt-2">Katalog Produk</div>
        <a href="<?= url('/products') ?>" class="wk-nav-link <?= is_active('/products', $currentUri) ?>">
            <i class="bi bi-box-seam"></i> Produk
        </a>
        <a href="<?= url('/categories') ?>" class="wk-nav-link <?= is_active('/categories', $currentUri) ?>">
            <i class="bi bi-tags"></i> Kategori
        </a>
        <a href="<?= url('/inventory') ?>" class="wk-nav-link <?= is_active('/inventory', $currentUri) ?>">
            <i class="bi bi-boxes"></i> Kelola Stok
        </a>

        <div class="wk-sidebar-heading mt-2">Laporan & Biaya</div>
        <a href="<?= url('/reports/sales') ?>" class="wk-nav-link <?= is_active('/reports/sales', $currentUri) ?>">
            <i class="bi bi-graph-up"></i> Penjualan
        </a>
        <a href="<?= url('/reports/payments') ?>" class="wk-nav-link <?= is_active('/reports/payments', $currentUri) ?>">
            <i class="bi bi-cash-stack"></i> Pembayaran
        </a>
        <a href="<?= url('/reports/profit') ?>" class="wk-nav-link <?= is_active('/reports/profit', $currentUri) ?>">
            <i class="bi bi-pie-chart"></i> HPP & Laba
        </a>
        <a href="<?= url('/expenses') ?>" class="wk-nav-link <?= is_active('/expenses', $currentUri) ?>">
            <i class="bi bi-wallet2"></i> Pengeluaran
        </a>

        <div class="wk-sidebar-heading mt-2">Pegawai</div>
        <a href="<?= url('/users') ?>" class="wk-nav-link <?= is_active('/users', $currentUri) ?>">
            <i class="bi bi-people"></i> Akun Pegawai
        </a>
        <a href="<?= url('/attendance') ?>" class="wk-nav-link <?= is_active('/attendance', $currentUri) ?>">
            <i class="bi bi-calendar-check"></i> Absensi
        </a>
        <a href="<?= url('/payroll') ?>" class="wk-nav-link <?= is_active('/payroll', $currentUri) ?>">
            <i class="bi bi-credit-card-2-front"></i> Payroll (Gaji)
        </a>

        <div class="wk-sidebar-heading mt-2">Sistem</div>
        <a href="<?= url('/audit') ?>" class="wk-nav-link <?= is_active('/audit', $currentUri) ?>">
            <i class="bi bi-shield-check"></i> Audit Log
        </a>
        <a href="<?= url('/settings') ?>" class="wk-nav-link <?= is_active('/settings', $currentUri) ?>">
            <i class="bi bi-gear"></i> Pengaturan
        </a>

    <?php else: ?>
        <!-- Cashier Simple Menu -->
        <a href="<?= url('/pos') ?>" class="wk-nav-link <?= is_active('/pos', $currentUri) ?>">
            <i class="bi bi-cart3"></i> POS Kasir
        </a>
        <a href="<?= url('/transactions') ?>" class="wk-nav-link <?= is_active('/transactions', $currentUri) ?>">
            <i class="bi bi-receipt"></i> Riwayat Transaksi Saya
        </a>
        <a href="<?= url('/expenses') ?>" class="wk-nav-link <?= is_active('/expenses', $currentUri) ?>">
            <i class="bi bi-wallet2"></i> Pengeluaran
        </a>
        <a href="<?= url('/attendance') ?>" class="wk-nav-link <?= is_active('/attendance', $currentUri) ?>">
            <i class="bi bi-calendar-check"></i> Absensi Saya
        </a>
    <?php endif; ?>

    <div class="mt-4 pt-2 border-top">
        <form action="<?= url('/logout') ?>" method="POST" class="m-0">
            <?= csrf_field() ?>
            <button type="submit" class="wk-nav-link text-danger w-100 border-0 bg-transparent text-start">
                <i class="bi bi-box-arrow-right"></i> Keluar
            </button>
        </form>
    </div>
</div>
<?php $sidebarContent = ob_get_clean(); ?>

<!-- Desktop Sidebar -->
<?php if (empty($hideDesktopSidebar)): ?>
<aside class="wk-sidebar d-none d-lg-block">
    <?= $sidebarContent ?>
</aside>
<?php endif; ?>

<!-- Mobile Offcanvas Sidebar -->
<div class="offcanvas offcanvas-start wk-mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
    <div class="offcanvas-header border-bottom py-3">
        <h5 class="offcanvas-title wk-brand-wordmark text-uppercase" id="mobileSidebarLabel"><?= e(shop_name()) ?></h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <?= $sidebarContent ?>
    </div>
</div>
