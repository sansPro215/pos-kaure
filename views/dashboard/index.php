<div class="container-fluid px-0">
    <!-- Print Only Letterhead -->
    <div class="d-none d-print-block mb-4 pb-3 border-bottom">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h3 class="fw-bold text-dark mb-1"><?= e(shop_name()) ?></h3>
                <h5 class="fw-semibold text-secondary mb-1">LAPORAN RINGKASAN OPERASIONAL & KEUANGAN (DASHBOARD)</h5>
                <div class="small text-muted"><?= e(shop_setting('address') ?: 'Alamat Kedai') ?> • Telp: <?= e(shop_setting('phone') ?: '-') ?></div>
            </div>
            <div class="text-end small">
                <div><strong>Tanggal:</strong> <?= date('d F Y') ?></div>
                <div><strong>Waktu Cetak:</strong> <?= date('H:i') ?> WIB</div>
                <div><strong>Dicetak Oleh:</strong> <?= e(auth_user()['name'] ?? 'Owner') ?> (<?= e(auth_user()['role'] ?? 'OWNER') ?>)</div>
            </div>
        </div>
    </div>

    <!-- Page Header (Screen) -->
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Dashboard</h4>
            <p class="text-muted small mb-0">Ringkasan operasional kedai hari ini (<?= date('d/m/Y') ?>)</p>
        </div>
        <div class="d-flex align-items-center gap-2 no-print">
            <a href="<?= url('/pos') ?>" class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-cart3"></i> Buka POS Kasir
            </a>
            <button type="button" onclick="printDashboardReport()" class="btn btn-outline-secondary btn-sm px-3 py-2 d-flex align-items-center gap-2" title="Cetak Laporan Operasional & Keuangan">
                <i class="bi bi-printer"></i> Cetak Laporan
            </button>
            <iframe id="printFrame" src="" style="display:none; width:0; height:0; border:none;" aria-hidden="true"></iframe>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="row g-3 mb-4">
        <!-- Omzet Hari Ini -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="wk-card p-3 h-100 border-start border-4 border-wk" style="border-left-color: var(--wk-primary) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Omzet Hari Ini</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Net Sales</span>
                </div>
                <h3 class="fw-bold text-wk-primary mb-1"><?= format_rupiah($summary['net_sales']) ?></h3>
                <div class="text-muted small">
                    <span><?= $summary['total_transactions'] ?> transaksi</span> • 
                    <span><?= $summary['items_sold'] ?> item terjual</span>
                </div>
            </div>
        </div>

        <!-- Laba Kotor Hari Ini -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="wk-card p-3 h-100 border-start border-4 border-success">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Laba Kotor Kedai</span>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1">Laba Kotor</span>
                </div>
                <h3 class="fw-bold text-success mb-1"><?= format_rupiah($summary['gross_profit']) ?></h3>
                <div class="text-muted small">
                    <div class="fw-semibold">Beban: <?= format_rupiah($summary['total_expenses']) ?></div>
                    <div class="text-secondary" style="font-size: 0.72rem;">(Operasional: <?= format_rupiah($summary['operational_expenses']) ?> + Gaji Pegawai: <?= format_rupiah($summary['staff_payroll_expenses']) ?>)</div>
                </div>
            </div>
        </div>

        <!-- Gaji Owner / Intensif Manager -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="wk-card p-3 h-100 border-start border-4 border-info">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Gaji Owner (<?= (float)($summary['manager_incentive_percent'] ?? 20) ?>%)</span>
                    <i class="bi bi-person-badge text-info"></i>
                </div>
                <h3 class="fw-bold text-info mb-1"><?= format_rupiah($summary['manager_incentive']) ?></h3>
                <div class="text-muted small">
                    Intensive Manager (<?= (float)($summary['manager_incentive_percent'] ?? 20) ?>% Laba Kotor)
                </div>
            </div>
        </div>

        <!-- Pemegang Saham -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="wk-card p-3 h-100 border-start border-4 <?= $summary['shareholder_dividend'] >= 0 ? 'border-warning' : 'border-danger' ?>">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pemegang Saham (<?= (float)($summary['shareholder_percent'] ?? 80) ?>%)</span>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1">Dividen</span>
                </div>
                <h3 class="fw-bold <?= $summary['shareholder_dividend'] >= 0 ? 'text-warning' : 'text-danger' ?> mb-1">
                    <?= format_rupiah($summary['shareholder_dividend']) ?>
                </h3>
                <div class="text-muted small">
                    Sisa laba kotor setelah gaji intensive manager
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <!-- 7 Days Trend Chart -->
        <div class="col-12 col-lg-8">
            <div class="wk-card p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-graph-up me-2"></i>Tren Penjualan 7 Hari Terakhir</h6>
                    <span class="badge bg-secondary-subtle text-secondary">Omzet vs HPP</span>
                </div>
                <div style="position: relative; height: 260px;">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Cash vs Cashless Doughnut Chart -->
        <div class="col-12 col-lg-4">
            <div class="wk-card p-3 p-md-4 h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart me-2"></i>Pembayaran Hari Ini</h6>
                <div style="position: relative; height: 200px;" class="d-flex align-items-center justify-content-center">
                    <canvas id="paymentMethodChart"></canvas>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-around text-center small">
                    <div>
                        <div class="text-muted">Cash</div>
                        <div class="fw-bold"><?= format_rupiah($summary['cash_sales']) ?></div>
                    </div>
                    <div>
                        <div class="text-muted">QRIS / Transfer</div>
                        <div class="fw-bold text-primary"><?= format_rupiah($summary['cashless_sales']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Top Products & Recent Transactions -->
    <div class="row g-3 mb-4">
        <!-- Top Products -->
        <div class="col-12 col-lg-6">
            <div class="wk-card p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-star-fill text-warning me-2"></i>Produk Terlaris Hari Ini</h6>
                    <span class="badge bg-primary-subtle text-primary">Top 5</span>
                </div>

                <?php if (empty($topProducts)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-inbox fs-2 d-block mb-1"></i>
                        Belum ada penjualan produk hari ini.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Omzet</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $idx => $p): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-secondary-subtle text-secondary me-1">#<?= $idx + 1 ?></span>
                                            <span class="fw-semibold"><?= e($p['product_name']) ?></span>
                                        </td>
                                        <td class="text-center fw-bold"><?= (int)$p['total_qty'] ?></td>
                                        <td class="text-end text-success fw-semibold"><?= format_rupiah($p['total_omzet']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="col-12 col-lg-6">
            <div class="wk-card p-3 p-md-4 h-100">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold m-0"><i class="bi bi-clock-history me-2"></i>Transaksi Terbaru</h6>
                    <a href="<?= url('/transactions') ?>" class="small text-decoration-none d-print-none">Semua &rarr;</a>
                </div>

                <?php if (empty($recentTransactions)): ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="bi bi-receipt fs-2 d-block mb-1"></i>
                        Belum ada transaksi tercatat.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Waktu</th>
                                    <th>Metode</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentTransactions as $t): ?>
                                    <tr>
                                        <td>
                                            <a href="<?= url('/transactions/' . $t['id']) ?>" class="fw-bold text-wk-primary text-decoration-none">
                                                <?= e($t['transaction_code']) ?>
                                            </a>
                                        </td>
                                        <td class="text-muted text-nowrap"><?= date('d/m H:i', strtotime($t['transaction_date'])) ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark border"><?= e($t['payment_method']) ?></span>
                                        </td>
                                        <td class="text-end fw-bold"><?= format_rupiah($t['grand_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Attendance Today Status -->
    <div class="wk-card p-3 mb-4">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold m-0"><i class="bi bi-people me-2"></i>Kehadiran Pegawai Hari Ini</h6>
                <button type="button" class="btn btn-sm btn-wk-primary px-2 py-0 text-decoration-none rounded-pill d-print-none" data-bs-toggle="modal" data-bs-target="#attendanceDetailModal" style="font-size: 0.75rem;">
                    <i class="bi bi-info-circle me-1"></i> Buka Informasi
                </button>
            </div>
            <div class="d-flex align-items-center gap-2 d-print-none">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attendanceDetailModal">
                    <i class="bi bi-card-checklist me-1"></i> Detail Pegawai
                </button>
                <a href="<?= url('/attendance') ?>" class="btn btn-sm btn-outline-secondary">Rekap Absensi</a>
            </div>
        </div>
        <div class="row g-2 text-center small">
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-body-tertiary attendance-stat-btn shadow-sm" 
                     style="cursor: pointer; transition: all 0.2s;" 
                     onclick="openAttendanceModalWithFilter('WORKING')" 
                     title="Klik untuk membuka daftar pegawai yang sedang bekerja">
                    <span class="text-muted d-block"><i class="bi bi-clock-history text-success me-1"></i>Sedang Bekerja</span>
                    <strong class="fs-5 text-success"><?= $attendance['currently_working'] ?></strong>
                    <div class="text-muted d-print-none" style="font-size: 0.7rem;">(Klik detail)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-body-tertiary attendance-stat-btn shadow-sm" 
                     style="cursor: pointer; transition: all 0.2s;" 
                     onclick="openAttendanceModalWithFilter('FINISHED')" 
                     title="Klik untuk membuka daftar pegawai yang sudah selesai shift">
                    <span class="text-muted d-block"><i class="bi bi-check2-circle text-primary me-1"></i>Selesai Shift</span>
                    <strong class="fs-5 text-primary"><?= $attendance['finished_shift'] ?></strong>
                    <div class="text-muted d-print-none" style="font-size: 0.7rem;">(Klik detail)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-body-tertiary attendance-stat-btn shadow-sm" 
                     style="cursor: pointer; transition: all 0.2s;" 
                     onclick="openAttendanceModalWithFilter('LEAVE')" 
                     title="Klik untuk membuka daftar pegawai izin atau sakit">
                    <span class="text-muted d-block"><i class="bi bi-bandaid text-warning me-1"></i>Izin / Sakit</span>
                    <strong class="fs-5 text-warning"><?= $attendance['izin_count'] + $attendance['sakit_count'] ?></strong>
                    <div class="text-muted d-print-none" style="font-size: 0.7rem;">(Klik detail)</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-2 border rounded bg-body-tertiary attendance-stat-btn shadow-sm" 
                     style="cursor: pointer; transition: all 0.2s;" 
                     onclick="openAttendanceModalWithFilter('ALPHA')" 
                     title="Klik untuk membuka daftar pegawai alpha">
                    <span class="text-muted d-block"><i class="bi bi-x-circle text-danger me-1"></i>Alpha</span>
                    <strong class="fs-5 text-danger"><?= $attendance['alpha_count'] ?></strong>
                    <div class="text-muted d-print-none" style="font-size: 0.7rem;">(Klik detail)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Only Signatures -->
    <div class="d-none d-print-block mt-4 pt-4 border-top">
        <div class="row text-center">
            <div class="col-6">
                <p class="mb-5 text-muted small">Penanggung Jawab Kasir / Shift,</p>
                <p class="fw-bold mb-0">__________________________</p>
                <small class="text-muted">( Kasir Bertugas )</small>
            </div>
            <div class="col-6">
                <p class="mb-5 text-muted small">Disetujui Oleh,</p>
                <p class="fw-bold mb-0"><u><?= e(auth_user()['name'] ?? 'Owner') ?></u></p>
                <small class="text-muted">( Owner / Pengelola Kedai )</small>
            </div>
        </div>
        <div class="text-center text-muted small mt-4" style="font-size: 0.72rem;">
            Laporan ini dicetak secara otomatis dari Sistem POS <?= e(shop_name()) ?> pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
    </div>
</div>

<!-- Modal Detail Informasi Kehadiran Pegawai Hari Ini -->
<div class="modal fade" id="attendanceDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom py-3">
                <div>
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="bi bi-people-fill text-wk-primary me-2"></i>Informasi Kehadiran Pegawai
                    </h5>
                    <small class="text-muted">Status kehadiran shift hari ini: <?= date('d F Y') ?></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3 p-md-4">
                <!-- Filter Pills inside Modal -->
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <button type="button" class="btn btn-sm btn-wk-primary att-filter-btn active" data-filter="ALL" onclick="filterModalAttendance('ALL')">
                        Semua (<?= count($todayAttendanceList ?? []) ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success att-filter-btn" data-filter="WORKING" onclick="filterModalAttendance('WORKING')">
                        Sedang Bekerja (<?= $attendance['currently_working'] ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary att-filter-btn" data-filter="FINISHED" onclick="filterModalAttendance('FINISHED')">
                        Selesai Shift (<?= $attendance['finished_shift'] ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning att-filter-btn" data-filter="LEAVE" onclick="filterModalAttendance('LEAVE')">
                        Izin / Sakit (<?= $attendance['izin_count'] + $attendance['sakit_count'] ?>)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger att-filter-btn" data-filter="ALPHA" onclick="filterModalAttendance('ALPHA')">
                        Alpha (<?= $attendance['alpha_count'] ?>)
                    </button>
                </div>

                <!-- Attendance List -->
                <?php if (empty($todayAttendanceList)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x fs-1 d-block mb-2 opacity-50"></i>
                        <div class="fw-semibold">Belum ada catatan absensi pegawai untuk hari ini.</div>
                        <small>Absensi akan tercatat otomatis saat kasir login atau melakukan absen mandiri.</small>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Pegawai</th>
                                    <th class="text-center">Jam Masuk</th>
                                    <th class="text-center">Jam Pulang</th>
                                    <th class="text-center">Durasi Kerja</th>
                                    <th class="text-center">Status</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($todayAttendanceList as $attRow): ?>
                                    <?php
                                        $rowStatus = $attRow['status'];
                                        $hasClockOut = !empty($attRow['clock_out']);
                                        $filterCategory = 'WORKING';
                                        if ($rowStatus === 'SELESAI' || ($rowStatus === 'HADIR' && $hasClockOut)) {
                                            $filterCategory = 'FINISHED';
                                        } elseif ($rowStatus === 'IZIN' || $rowStatus === 'SAKIT') {
                                            $filterCategory = 'LEAVE';
                                        } elseif ($rowStatus === 'ALPHA') {
                                            $filterCategory = 'ALPHA';
                                        }
                                    ?>
                                    <tr class="modal-att-row" data-category="<?= $filterCategory ?>">
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-wk-primary text-white d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width: 34px; height: 34px;">
                                                    <?= strtoupper(substr($attRow['user_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="fw-bold text-dark"><?= e($attRow['user_name']) ?></div>
                                                    <small class="text-muted"><?= e($attRow['role']) ?> • @<?= e($attRow['username']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center font-monospace">
                                            <?= !empty($attRow['clock_in']) ? '<span class="text-success fw-bold">' . date('H:i', strtotime($attRow['clock_in'])) . '</span>' : '—' ?>
                                        </td>
                                        <td class="text-center font-monospace">
                                            <?= !empty($attRow['clock_out']) ? '<span class="text-primary fw-bold">' . date('H:i', strtotime($attRow['clock_out'])) . '</span>' : '<span class="badge bg-secondary-subtle text-secondary small">Aktif</span>' ?>
                                        </td>
                                        <td class="text-center small">
                                            <?php if (!empty($attRow['worked_minutes'])): ?>
                                                <span class="fw-semibold"><?= format_minutes($attRow['worked_minutes']) ?></span>
                                                <?php if (!empty($attRow['overtime_minutes']) && $attRow['overtime_minutes'] >= 60): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis ms-1">+<?= (int)floor($attRow['overtime_minutes'] / 60) ?>j</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($rowStatus === 'SELESAI' || ($rowStatus === 'HADIR' && $hasClockOut)): ?>
                                                <span class="badge bg-primary text-white px-2 py-1"><i class="bi bi-check2-circle me-1"></i>Selesai</span>
                                            <?php elseif ($rowStatus === 'HADIR'): ?>
                                                <span class="badge bg-success text-white px-2 py-1"><i class="bi bi-clock-history me-1"></i>Bekerja</span>
                                            <?php elseif ($rowStatus === 'IZIN'): ?>
                                                <span class="badge bg-info text-dark px-2 py-1">Izin</span>
                                            <?php elseif ($rowStatus === 'SAKIT'): ?>
                                                <span class="badge bg-warning text-dark px-2 py-1">Sakit</span>
                                            <?php elseif ($rowStatus === 'ALPHA'): ?>
                                                <span class="badge bg-danger text-white px-2 py-1">Alpha</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary text-white px-2 py-1"><?= e($rowStatus) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted">
                                            <?= !empty($attRow['note']) ? e($attRow['note']) : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-top p-3 d-flex justify-content-between">
                <a href="<?= url('/attendance') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-pencil-square me-1"></i> Kelola di Menu Absensi
                </a>
                <button type="button" class="btn btn-secondary btn-sm px-4" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function openAttendanceModalWithFilter(category) {
    const modalEl = document.getElementById('attendanceDetailModal');
    if (modalEl) {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        filterModalAttendance(category);
    }
}

function filterModalAttendance(category) {
    const buttons = document.querySelectorAll('.att-filter-btn');
    buttons.forEach(btn => {
        if (btn.getAttribute('data-filter') === category) {
            btn.className = 'btn btn-sm btn-wk-primary att-filter-btn active';
        } else {
            const f = btn.getAttribute('data-filter');
            let outlineClass = 'btn-outline-secondary';
            if (f === 'WORKING') outlineClass = 'btn-outline-success';
            else if (f === 'FINISHED') outlineClass = 'btn-outline-primary';
            else if (f === 'LEAVE') outlineClass = 'btn-outline-warning';
            else if (f === 'ALPHA') outlineClass = 'btn-outline-danger';
            btn.className = `btn btn-sm ${outlineClass} att-filter-btn`;
        }
    });

    const rows = document.querySelectorAll('.modal-att-row');
    rows.forEach(row => {
        if (category === 'ALL' || row.getAttribute('data-category') === category) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<!-- Chart.js Scripts Initialization -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 0. Ambil warna tema dan deteksi mode tampilan secara dinamis
    const rootStyles = getComputedStyle(document.documentElement);
    const themePrimary = rootStyles.getPropertyValue('--wk-primary').trim() || '#6F4E37';
    const themePrimaryRgb = rootStyles.getPropertyValue('--wk-primary-rgb').trim() || '111, 78, 55';
    const themePrimaryDark = rootStyles.getPropertyValue('--wk-primary-dark').trim() || '#543B2A';
    
    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark' || document.body.classList.contains('dark-theme');
    const labelColor = isDark ? '#adb5bd' : '#6c757d';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.05)';

    // 1. Sales Trend Line Chart
    const trendCtx = document.getElementById('salesTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trendData['labels']) ?>,
                datasets: [
                    {
                        label: 'Omzet Penjualan',
                        data: <?= json_encode($trendData['omzet']) ?>,
                        borderColor: themePrimary,
                        backgroundColor: `rgba(${themePrimaryRgb}, 0.15)`,
                        pointBackgroundColor: themePrimary,
                        pointBorderColor: '#ffffff',
                        pointHoverBackgroundColor: '#ffffff',
                        pointHoverBorderColor: themePrimary,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5
                    },
                    {
                        label: 'HPP (Modal)',
                        data: <?= json_encode($trendData['cogs']) ?>,
                        borderColor: '#dc3545',
                        backgroundColor: 'rgba(220, 53, 69, 0.05)',
                        pointBackgroundColor: '#dc3545',
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.35,
                        borderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { color: labelColor, usePointStyle: true, boxWidth: 8 }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': Rp' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: labelColor },
                        grid: { color: gridColor }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: labelColor,
                            callback: function(value) {
                                return 'Rp' + (value / 1000) + 'k';
                            }
                        },
                        grid: { color: gridColor }
                    }
                }
            }
        });
    }

    // 2. Cash vs Cashless Doughnut Chart
    const payCtx = document.getElementById('paymentMethodChart');
    if (payCtx) {
        const cashSales = <?= (float)$summary['cash_sales'] ?>;
        const cashlessSales = <?= (float)$summary['cashless_sales'] ?>;
        const total = cashSales + cashlessSales;

        new Chart(payCtx, {
            type: 'doughnut',
            data: {
                labels: ['Cash', 'Cashless (QRIS/Transfer/E-Wallet)'],
                datasets: [{
                    data: total > 0 ? [cashSales, cashlessSales] : [1, 0],
                    backgroundColor: total > 0 ? [themePrimary, '#0d6efd'] : ['#e9ecef', '#e9ecef'],
                    borderColor: isDark ? '#212529' : '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (total === 0) return 'Belum ada transaksi';
                                return context.label + ': Rp' + new Intl.NumberFormat('id-ID').format(context.raw);
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
});
</script>

<script>
function printDashboardReport() {
    var frame = document.getElementById('printFrame');
    var printUrl = '<?= url('/dashboard/print') ?>';

    // Reset iframe src to force fresh load
    frame.src = '';
    setTimeout(function () {
        frame.src = printUrl;
        frame.onload = function () {
            // Give fonts & layout inside iframe time to render
            setTimeout(function () {
                try {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (e) {
                    // Fallback: open in same window if iframe blocked
                    window.location.href = printUrl;
                }
            }, 500);
        };
    }, 50);
}
</script>
