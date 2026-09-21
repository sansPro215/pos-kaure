<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <div class="wk-card shadow border-0 p-4 p-md-5 text-center text-wk-surface-container" style="border-radius: 20px;">
                <!-- Shift Completed Icon -->
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-4 shadow-sm" style="width: 80px; height: 80px; background-color: rgba(220, 53, 69, 0.1); color: #dc3545;">
                    <i class="bi bi-person-x-fill fs-1"></i>
                </div>

                <!-- Title & Badge -->
                <div class="mb-3">
                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill mb-2 fw-semibold">
                        <i class="bi bi-lock-fill me-1"></i> Transaksi Dinonaktifkan
                    </span>
                    <h3 class="fw-bold text-dark mb-1">Shift Selesai</h3>
                    <p class="text-danger fw-semibold small mb-2">
                        Akun anda sudah absen pulang, tidak bisa melakukan transaksi. Jika ada kesalahan silahkan hubungi owner.
                    </p>
                </div>

                <!-- Attendance Details Box -->
                <div class="p-3 rounded-4 mb-4 text-start border bg-body-tertiary">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center fw-bold fs-5" style="width: 44px; height: 44px;">
                            <?= strtoupper(substr(auth_user()['name'] ?? 'K', 0, 1)) ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold text-dark"><?= e(auth_user()['name'] ?? 'Kasir') ?></div>
                            <div class="text-muted small">Role: <span class="badge bg-secondary-subtle text-secondary"><?= e(auth_user()['role'] ?? 'CASHIER') ?></span> • Tanggal: <?= date('d F Y') ?></div>
                        </div>
                        <div>
                            <span class="badge bg-primary px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Selesai</span>
                        </div>
                    </div>

                    <div class="row g-2 text-center small border-top pt-2">
                        <div class="col-4">
                            <span class="text-muted d-block">Jam Masuk:</span>
                            <strong class="fs-6 text-success font-monospace">
                                <?= !empty($todayAttendance['clock_in']) ? date('H:i', strtotime($todayAttendance['clock_in'])) : '—' ?>
                            </strong>
                        </div>
                        <div class="col-4 border-start">
                            <span class="text-muted d-block">Jam Pulang:</span>
                            <strong class="fs-6 text-danger font-monospace">
                                <?= !empty($todayAttendance['clock_out']) ? date('H:i', strtotime($todayAttendance['clock_out'])) : '—' ?>
                            </strong>
                        </div>
                        <div class="col-4 border-start">
                            <span class="text-muted d-block">Durasi Kerja:</span>
                            <strong class="fs-6 text-dark">
                                <?= !empty($todayAttendance['worked_minutes']) ? format_minutes($todayAttendance['worked_minutes']) : '—' ?>
                            </strong>
                        </div>
                    </div>
                </div>

                <!-- Info Alert -->
                <div class="alert alert-warning py-2 px-3 small text-start mb-4 rounded-3 d-flex align-items-start gap-2">
                    <i class="bi bi-exclamation-triangle-fill text-warning fs-5 flex-shrink-0 mt-1"></i>
                    <div>
                        <strong>Perlu melanjutkan transaksi?</strong><br>
                        Jika Anda tidak sengaja melakukan absen pulang atau shift masih berlanjut, minta Owner kedai untuk melakukan koreksi absensi Anda di menu <strong>Absensi Pegawai</strong>.
                    </div>
                </div>

                <!-- Navigation options -->
                <div class="d-flex flex-wrap align-items-center justify-content-center gap-3 border-top pt-3">
                    <a href="<?= url('/expenses') ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                        <i class="bi bi-wallet2 me-1"></i> Modul Pengeluaran
                    </a>
                    <a href="<?= url('/attendance') ?>" class="btn btn-outline-secondary btn-sm px-3 rounded-pill">
                        <i class="bi bi-calendar-check me-1"></i> Riwayat Absensi
                    </a>
                    <form action="<?= url('/logout') ?>" method="POST" class="m-0 d-inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-link text-danger text-decoration-none p-0 small">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
