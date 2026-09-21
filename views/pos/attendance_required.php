<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="wk-card shadow border-0 p-4 p-md-5 text-center text-wk-surface-container" style="border-radius: 20px;">
                <!-- Barista / Attendance Icon -->
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 shadow-sm" style="width: 80px; height: 80px; background-color: var(--wk-primary-light); color: var(--wk-primary);">
                    <i class="bi bi-clock-history fs-1"></i>
                </div>

                <!-- Title & Badge -->
                <div class="mb-3">
                    <span class="badge bg-warning-subtle text-warning-emphasis px-3 py-1 rounded-pill mb-2 fw-semibold">
                        <i class="bi bi-shield-lock me-1"></i> Akses POS Terkunci
                    </span>
                    <h3 class="fw-bold text-wk-primary mb-1">Absen Masuk Diperlukan</h3>
                    <p class="text-muted small">Anda harus melakukan absen masuk sebelum dapat menggunakan modul Kasir (POS).</p>
                </div>

                <!-- User Info Box -->
                <div class="p-3 rounded-4 mb-4 text-start border bg-body-tertiary">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-wk-primary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 44px; height: 44px;">
                            <?= strtoupper(substr(auth_user()['name'] ?? 'K', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark"><?= e(auth_user()['name'] ?? 'Kasir') ?></div>
                            <div class="text-muted small">Role: <span class="badge bg-secondary-subtle text-secondary"><?= e(auth_user()['role'] ?? 'CASHIER') ?></span> • Shift: <?= date('d F Y') ?></div>
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex align-items-center justify-content-between text-muted small">
                        <span>Waktu Sekarang:</span>
                        <strong class="fs-6 text-dark font-monospace" id="liveClockPos"><?= date('H:i:s') ?></strong>
                    </div>
                </div>

                <!-- Direct Clock In Form -->
                <form action="<?= url('/attendance/clock-in') ?>" method="POST" class="mb-3">
                    <?= csrf_field() ?>
                    <input type="hidden" name="redirect_to" value="/pos">
                    <button type="submit" class="btn btn-success btn-lg w-100 py-3 fw-bold rounded-pill shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size: 1.05rem;">
                        <i class="bi bi-box-arrow-in-right fs-5"></i>
                        ABSEN MASUK SEKARANG
                    </button>
                </form>

                <p class="text-muted small mb-4" style="font-size: 0.8rem;">
                    Setelah Anda menekan tombol di atas, jam kehadiran akan tercatat dan modul POS akan langsung terbuka secara otomatis.
                </p>

                <!-- Navigation options -->
                <div class="d-flex align-items-center justify-content-center gap-3 border-top pt-3">
                    <a href="<?= url('/attendance') ?>" class="text-decoration-none small text-muted">
                        <i class="bi bi-calendar-check me-1"></i> Buka Menu Absensi
                    </a>
                    <span class="text-muted">•</span>
                    <form action="<?= url('/logout') ?>" method="POST" class="m-0 d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small">
                            <i class="bi bi-box-arrow-right me-1"></i> Keluar (Logout)
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const clockEl = document.getElementById('liveClockPos');
    if (clockEl) {
        setInterval(() => {
            const now = new Date();
            clockEl.textContent = now.toTimeString().split(' ')[0];
        }, 1000);
    }
});
</script>
