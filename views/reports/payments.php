<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Laporan Pembayaran (Cash vs Cashless)</h4>
            <p class="text-muted small mb-0">Rincian pendapatan berdasarkan metode transaksi tunai dan non-tunai</p>
        </div>
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2 no-print">
            <i class="bi bi-printer"></i> Cetak Laporan
        </button>
    </div>

    <!-- Print Header Only -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <h3 class="fw-bold m-0 text-uppercase"><?= e(shop_name()) ?></h3>
        <div class="small">Laporan Pembayaran (Cash vs Cashless)</div>
        <div class="small text-muted">
            Periode: <?= !empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Semua' ?> s/d <?= !empty($endDate) ? date('d/m/Y', strtotime($endDate)) : 'Hari Ini' ?> 
            • Dicetak: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-4 no-print">
        <form action="<?= url('/reports/payments') ?>" method="GET" class="row g-2 align-items-center">
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

    <!-- Cash vs Cashless Overview Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="wk-card p-4 shadow-sm border-start border-4 border-wk">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pembayaran Tunai (Cash)</span>
                    <i class="bi bi-cash-stack fs-4 text-success"></i>
                </div>
                <h3 class="fw-bold text-wk-primary mb-1"><?= format_rupiah($summary['cash_sales']) ?></h3>
                <?php 
                    $total = $summary['net_sales'];
                    $cashPct = $total > 0 ? round(($summary['cash_sales'] / $total) * 100, 1) : 0;
                ?>
                <div class="text-muted small"><?= $cashPct ?>% dari total pendapatan</div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="wk-card p-4 shadow-sm border-start border-4 border-primary">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small fw-semibold">Pembayaran Non-Tunai (Cashless)</span>
                    <i class="bi bi-credit-card fs-4 text-primary"></i>
                </div>
                <h3 class="fw-bold text-primary mb-1"><?= format_rupiah($summary['cashless_sales']) ?></h3>
                <?php 
                    $cashlessPct = $total > 0 ? round(($summary['cashless_sales'] / $total) * 100, 1) : 0;
                ?>
                <div class="text-muted small"><?= $cashlessPct ?>% dari total pendapatan</div>
            </div>
        </div>
    </div>

    <!-- Breakdown Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <h6 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Rincian Kanal Pembayaran</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Metode Pembayaran</th>
                        <th class="text-center">Kategori</th>
                        <th class="text-end">Total Diterima</th>
                        <th class="text-end">Persentase</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-bold"><i class="bi bi-cash me-2 text-success"></i>Tunai (Cash)</td>
                        <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">Cash</span></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($summary['cash_sales']) ?></td>
                        <td class="text-end fw-semibold"><?= $cashPct ?>%</td>
                    </tr>
                    <tr>
                        <td class="fw-bold"><i class="bi bi-qr-code-scan me-2 text-primary"></i>QRIS</td>
                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">Cashless</span></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($summary['qris_sales']) ?></td>
                        <td class="text-end fw-semibold"><?= $total > 0 ? round(($summary['qris_sales'] / $total) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                        <td class="fw-bold"><i class="bi bi-bank me-2 text-info"></i>Transfer Bank</td>
                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">Cashless</span></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($summary['transfer_sales']) ?></td>
                        <td class="text-end fw-semibold"><?= $total > 0 ? round(($summary['transfer_sales'] / $total) * 100, 1) : 0 ?>%</td>
                    </tr>
                    <tr>
                        <td class="fw-bold"><i class="bi bi-phone me-2 text-warning"></i>E-Wallet (GoPay, OVO, DANA, ShopeePay)</td>
                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">Cashless</span></td>
                        <td class="text-end fw-semibold"><?= format_rupiah($summary['ewallet_sales']) ?></td>
                        <td class="text-end fw-semibold"><?= $total > 0 ? round(($summary['ewallet_sales'] / $total) * 100, 1) : 0 ?>%</td>
                    </tr>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2">TOTAL KESELURUHAN</td>
                        <td class="text-end text-wk-primary"><?= format_rupiah($summary['net_sales']) ?></td>
                        <td class="text-end">100%</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
