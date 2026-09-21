<?php
$user = auth_user();
$isOwner = is_owner();
?>
<nav class="navbar wk-navbar px-3">
    <div class="container-fluid d-flex align-items-center justify-content-between p-0">
        <div class="d-flex align-items-center gap-2">
            <!-- Hamburger button for offcanvas navigation -->
            <button class="btn btn-outline-secondary <?= !empty($hideDesktopSidebar) ? '' : 'd-lg-none' ?> border-0 p-1" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Toggle navigation" title="Menu Navigasi">
                <i class="bi bi-list fs-4 text-wk-primary"></i>
            </button>
            <a class="navbar-brand wk-brand-wordmark text-uppercase m-0" href="<?= url($isOwner ? '/dashboard' : '/pos') ?>">
                <?= e(shop_name()) ?>
            </a>
            <span class="badge bg-secondary-subtle text-secondary rounded-pill d-none d-sm-inline-block px-2 py-1" style="font-size: 0.75rem;">
                <?= e($user['role'] ?? 'GUEST') ?>
            </span>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- POS Quick Link for Owner -->
            <?php if ($isOwner): ?>
                <a href="<?= url('/pos') ?>" class="btn btn-sm btn-wk-primary d-none d-sm-inline-flex align-items-center gap-1">
                    <i class="bi bi-cart3"></i> POS
                </a>
            <?php endif; ?>

            <!-- Dark Mode Toggle Button -->
            <button id="themeToggleBtn" class="btn btn-sm btn-outline-secondary border-0 p-2 rounded-circle" type="button" title="Ganti Mode Gelap/Terang">
                <i id="themeToggleIcon" class="bi bi-moon-stars-fill"></i>
            </button>

            <!-- User Dropdown Menu -->
            <div class="dropdown">
                <button class="btn btn-sm d-flex align-items-center gap-2 py-1 px-2 rounded-pill wk-nav-profile-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle bg-wk-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; font-weight: 600; font-size: 0.85rem;">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <span class="d-none d-md-inline fw-semibold wk-nav-profile-name" style="font-size: 0.88rem;"><?= e($user['name'] ?? 'User') ?></span>
                    <i class="bi bi-chevron-down opacity-75" style="font-size: 0.75rem;"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-wk mt-1" style="border-radius: 10px; font-size: 0.9rem;">
                    <li class="px-3 py-2 border-bottom">
                        <div class="fw-bold"><?= e($user['name'] ?? '') ?></div>
                        <small class="text-muted">@<?= e($user['username'] ?? '') ?></small>
                    </li>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('/attendance') ?>">
                            <i class="bi bi-clock-history"></i> Absensi Saya
                        </a>
                    </li>
                    <?php if ($isOwner): ?>
                    <li>
                        <a class="dropdown-item py-2 d-flex align-items-center gap-2" href="<?= url('/settings') ?>">
                            <i class="bi bi-gear"></i> Pengaturan
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><hr class="dropdown-divider my-1"></li>
                    <li>
                        <form action="<?= url('/logout') ?>" method="POST" class="m-0">
                            <?= csrf_field() ?>
                            <button type="submit" class="dropdown-item py-2 text-danger d-flex align-items-center gap-2">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>
