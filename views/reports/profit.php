<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Laporan HPP & Estimasi Laba Bersih</h4>
            <p class="text-muted small mb-0">Analisis margin keuntungan, beban operasional, gaji pegawai, dan pembagian dividen</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Print Header Only -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <h3 class="fw-bold m-0 text-uppercase"><?= e(shop_name()) ?></h3>
        <div class="small">Laporan HPP, Laba Rugi & Pembagian Dividen</div>
        <div class="small text-muted">
            Periode: <?= !empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Semua' ?> s/d <?= !empty($endDate) ? date('d/m/Y', strtotime($endDate)) : 'Hari Ini' ?> 
            • Dicetak: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-4 no-print">
        <form action="<?= url('/reports/profit') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-6 col-md-4">
                <label class="form-label small text-muted mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label small text-muted mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
            </div>
            <div class="col-12 col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-wk-primary btn-sm w-100 py-1">Tampilkan Laporan</button>
            </div>
        </form>
    </div>

    <?php 
        $managerPercent = (float)($summary['manager_incentive_percent'] ?? 20);
        $shareholderPercent = (float)($summary['shareholder_percent'] ?? 80);
        $totalExpenses = (float)$summary['operational_expenses'] + (float)$summary['payroll_expenses'];
        $grossSalesMargin = (float)$summary['net_sales'] - (float)$summary['hpp'];
    ?>

    <!-- Minimalist Metric Summary Cards -->
    <div class="row g-3 mb-4">
        <!-- Omzet -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-wk" style="border-left-color: var(--wk-primary) !important;">
                <div class="text-muted small fw-semibold">Omzet Bersih</div>
                <h4 class="fw-bold text-wk-primary mt-1 mb-0"><?= format_rupiah($summary['net_sales']) ?></h4>
                <small class="text-muted" style="font-size: 0.75rem;"><?= (int)$summary['total_transactions'] ?> transaksi kasir</small>
            </div>
        </div>

        <!-- Laba Kotor Kedai -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-success">
                <div class="text-muted small fw-semibold">Laba Kotor Kedai</div>
                <h4 class="fw-bold text-success mt-1 mb-0"><?= format_rupiah($summary['gross_profit']) ?></h4>
                <div class="text-muted" style="font-size: 0.75rem;">Total Beban: <?= format_rupiah($totalExpenses) ?></div>
                <div class="text-secondary" style="font-size: 0.7rem;">(Operasional: <?= format_rupiah($summary['operational_expenses']) ?> + Gaji Pegawai: <?= format_rupiah($summary['staff_payroll_expenses']) ?>)</div>
            </div>
        </div>

        <!-- Gaji Owner / Intensif Manager -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-info">
                <div class="text-muted small fw-semibold">Gaji Owner (<?= $managerPercent ?>%)</div>
                <h4 class="fw-bold text-info mt-1 mb-0"><?= format_rupiah($summary['manager_incentive']) ?></h4>
                <small class="text-muted" style="font-size: 0.75rem;">Intensive Manager (<?= $managerPercent ?>% Laba Kotor)</small>
            </div>
        </div>

        <!-- Pemegang Saham / Dividen -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 <?= $summary['shareholder_dividend'] >= 0 ? 'border-warning' : 'border-danger' ?>">
                <div class="text-muted small fw-semibold">Pemegang Saham (<?= $shareholderPercent ?>%)</div>
                <h4 class="fw-bold <?= $summary['shareholder_dividend'] >= 0 ? 'text-warning' : 'text-danger' ?> mt-1 mb-0">
                    <?= format_rupiah($summary['shareholder_dividend']) ?>
                </h4>
                <small class="text-muted" style="font-size: 0.75rem;">Sisa laba kotor setelah gaji intensive manager</small>
            </div>
        </div>
    </div>

    <!-- Minimalist Financial Breakdown Statement -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <h6 class="fw-bold m-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Rincian Laporan Keuangan & Laba Rugi</h6>
            <span class="badge bg-secondary-subtle text-secondary small">Transparan & Otomatis</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead>
                    <tr class="text-muted border-bottom" style="font-size: 0.78rem;">
                        <th style="width: 50%;">KOMPONEN KEUANGAN</th>
                        <th class="text-end" style="width: 25%;">NOMINAL</th>
                        <th style="width: 25%;">FORMULA / KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- I. PENDAPATAN & PENJUALAN -->
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-uppercase py-2" style="letter-spacing: 0.5px; font-size: 0.76rem;">
                            <i class="bi bi-arrow-down-circle me-1 text-wk-primary"></i> 1. Pendapatan Penjualan
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3">Penjualan Kotor (Subtotal)</td>
                        <td class="text-end fw-semibold"><?= format_rupiah($summary['subtotal']) ?></td>
                        <td class="text-muted">Total transaksi sebelum diskon</td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-danger">Potongan Diskon Penjualan</td>
                        <td class="text-end text-danger">-<?= format_rupiah($summary['discount']) ?></td>
                        <td class="text-muted">Diskon yang diberikan ke pelanggan</td>
                    </tr>
                    <tr class="fw-bold" style="background-color: rgba(111, 78, 55, 0.05);">
                        <td class="ps-3 text-wk-primary">= Omzet Bersih (Net Sales)</td>
                        <td class="text-end text-wk-primary"><?= format_rupiah($summary['net_sales']) ?></td>
                        <td class="text-muted">Subtotal − Diskon</td>
                    </tr>

                    <!-- II. HPP & MARGIN PRODUK -->
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-uppercase py-2" style="letter-spacing: 0.5px; font-size: 0.76rem;">
                            <i class="bi bi-box-seam me-1 text-secondary"></i> 2. Harga Pokok Penjualan (HPP)
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-muted">Estimasi HPP Bahan & Produk Terjual</td>
                        <td class="text-end text-muted"><?= format_rupiah($summary['hpp']) ?></td>
                        <td class="text-muted">Total modal bahan baku terpakai (BOM / Direct)</td>
                    </tr>
                    <tr class="fw-semibold text-muted" style="border-bottom: 1px dashed var(--wk-border);">
                        <td class="ps-3">Margin Penjualan Produk</td>
                        <td class="text-end"><?= format_rupiah($grossSalesMargin) ?></td>
                        <td class="text-muted">Omzet − HPP Bahan</td>
                    </tr>

                    <!-- III. PENGELUARAN & BEBAN USAHA -->
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-uppercase py-2" style="letter-spacing: 0.5px; font-size: 0.76rem;">
                            <i class="bi bi-wallet2 me-1 text-danger"></i> 3. Beban Usaha & Pengeluaran
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-danger">Beban Operasional Kedai</td>
                        <td class="text-end text-danger">-<?= format_rupiah($summary['operational_expenses']) ?></td>
                        <td class="text-muted">Listrik, gas, air galon, kebersihan, dll.</td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-danger">Beban Gaji Pegawai (Bukan Intensive Manager)</td>
                        <td class="text-end text-danger">-<?= format_rupiah($summary['staff_payroll_expenses']) ?></td>
                        <td class="text-muted">Gaji pokok & lembur staf/kasir (PAID)</td>
                    </tr>
                    <tr class="fw-bold text-danger" style="background-color: rgba(220, 53, 69, 0.05);">
                        <td class="ps-3">= Total Beban (Operasional + Gaji Pegawai)</td>
                        <td class="text-end">-<?= format_rupiah($totalExpenses) ?></td>
                        <td class="text-muted">Beban Operasional + Gaji Pegawai</td>
                    </tr>

                    <!-- IV. LABA KOTOR KEDAI -->
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-uppercase py-2" style="letter-spacing: 0.5px; font-size: 0.76rem;">
                            <i class="bi bi-graph-up-arrow me-1 text-success"></i> 4. Laba Kotor Kedai
                        </td>
                    </tr>
                    <tr class="fw-bold fs-6" style="background-color: rgba(25, 135, 84, 0.08);">
                        <td class="ps-3 text-success">= LABA KOTOR KEDAI</td>
                        <td class="text-end text-success"><?= format_rupiah($summary['gross_profit']) ?></td>
                        <td class="text-muted small">Omzet Bersih − Total Beban (Operasional + Gaji Pegawai)</td>
                    </tr>

                    <!-- V. ALOKASI GAJI INTENSIVE MANAGER & DIVIDEN -->
                    <tr class="table-light">
                        <td colspan="3" class="fw-bold text-uppercase py-2" style="letter-spacing: 0.5px; font-size: 0.76rem;">
                            <i class="bi bi-pie-chart me-1 text-primary"></i> 5. Alokasi Gaji Intensive Manager & Dividen Saham
                        </td>
                    </tr>
                    <tr>
                        <td class="ps-3 text-primary">Gaji Intensive Manager (Owner <?= $managerPercent ?>%)</td>
                        <td class="text-end text-primary fw-semibold"><?= format_rupiah($summary['manager_incentive']) ?></td>
                        <td class="text-muted"><?= $managerPercent ?>% dari Laba Kotor Kedai</td>
                    </tr>
                    <tr class="fw-bold fs-6" style="background-color: rgba(255, 193, 7, 0.08);">
                        <td class="ps-3 text-warning text-nowrap">= PEMBAGIAN PEMEGANG SAHAM (SISA SETELAH INTENSIF MANAGER: <?= $shareholderPercent ?>%)</td>
                        <td class="text-end text-warning"><?= format_rupiah($summary['shareholder_dividend']) ?></td>
                        <td class="text-muted small">Sisa dari pengurangan gaji intensive manager (Laba Kotor − Gaji Owner)</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3 p-2 rounded bg-light border text-muted small fst-italic no-print">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Keterangan Formula Keuangan:</strong><br>
            • <strong>Laba Kotor</strong> = Omzet Bersih − Beban Operasional − Gaji Pegawai (bukan gaji intensive manager).<br>
            • <strong>Total Beban</strong> = Beban Operasional + Gaji Pegawai.<br>
            • <strong>Gaji Intensive Manager</strong> = <?= $managerPercent ?>% dari Laba Kotor.<br>
            • <strong>Pembagian Pemegang Saham</strong> = Sisa laba kotor setelah dikurangi gaji intensive manager (Laba Kotor − Gaji Owner).
        </div>
    </div>
</div>
