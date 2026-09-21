<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Masuk — <?= e(shop_name()) ?> POS</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
    <?= render_theme_css() ?>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3" style="background-color: var(--wk-bg);">
    <div class="w-100" style="max-width: 400px;">
        <div class="text-center mb-4">
            <h2 class="wk-brand-wordmark fw-bold mb-1 text-uppercase" style="font-size: 1.75rem;"><?= e(shop_name()) ?></h2>
            <p class="text-muted small mb-0">Point of Sale & Management Kedai</p>
        </div>

        <div class="wk-card p-4 p-sm-5 shadow-sm">
            <?php if ($flash = flash_get()): ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : e($flash['type']) ?> alert-dismissible fade show d-flex align-items-center gap-2 mb-4 p-2 small" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <div><?= e($flash['message']) ?></div>
                    <button type="button" class="btn-close p-2" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="<?= url('/login') ?>" method="POST" autocomplete="off">
                <?= csrf_field() ?>

                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold small mb-1">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="bi bi-person"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 ps-0" id="username" name="username" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold small mb-1">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 border-end-0 ps-0" id="password" name="password" placeholder="Masukkan password" required>
                        <button class="btn btn-outline-secondary border-start-0 bg-transparent text-muted" type="button" id="togglePasswordBtn">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-wk-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2" style="height: 48px; border-radius: 10px;">
                    <span>Masuk</span>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.8rem;">
                <div><strong>Akun Bawaan:</strong></div>
                <div>Owner: <code>owner</code> / <code>owner123</code></div>
                <div>Kasir: <code>kasir</code> / <code>kasir123</code></div>
            </div>
        </div>

        <div class="text-center mt-3 text-muted small d-flex align-items-center justify-content-center gap-2">
            <span>&copy; <?= date('Y') ?> <?= e(shop_name()) ?></span>
            <span>•</span>
            <button id="themeToggleBtn" class="btn btn-sm btn-link text-decoration-none text-muted p-0" type="button">
                <i id="themeToggleIcon" class="bi bi-moon-stars-fill"></i>
            </button>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('/assets/js/app.js') ?>"></script>
    <script>
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passInput = document.getElementById('password');
        const passIcon = document.getElementById('togglePasswordIcon');
        if (toggleBtn && passInput) {
            toggleBtn.addEventListener('click', () => {
                if (passInput.type === 'password') {
                    passInput.type = 'text';
                    passIcon.className = 'bi bi-eye-slash';
                } else {
                    passInput.type = 'password';
                    passIcon.className = 'bi bi-eye';
                }
            });
        }
    </script>
</body>
</html>
