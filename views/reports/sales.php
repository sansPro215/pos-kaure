<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Laporan Penjualan</h4>
            <p class="text-muted small mb-0">Rekap omzet, total transaksi, dan produk terjual</p>
        </div>
        <div class="d-flex flex-wrap gap-2 no-print">
            <a href="<?= url('/transactions/export-xlsx?' . http_build_query(array_filter(['start_date' => $startDate, 'end_date' => $endDate, 'cashier_id' => $cashierId, 'status' => 'PAID']))) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow-sm" title="Unduh laporan penjualan dalam format Excel (.xlsx)">
                <i class="bi bi-file-earmark-excel fs-6"></i> Ekspor Excel (.xlsx)
            </a>
            <button onclick="window.print()" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-2">
                <i class="bi bi-printer"></i> Cetak Laporan
            </button>
        </div>
    </div>

    <!-- Print Header Only -->
    <div class="d-none d-print-block mb-3 border-bottom pb-2">
        <h3 class="fw-bold m-0 text-uppercase"><?= e(shop_name()) ?></h3>
        <div class="small">Laporan Rekap Penjualan & Omzet</div>
        <div class="small text-muted">
            Periode: <?= !empty($startDate) ? date('d/m/Y', strtotime($startDate)) : 'Semua' ?> s/d <?= !empty($endDate) ? date('d/m/Y', strtotime($endDate)) : 'Hari Ini' ?> 
            • Dicetak: <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-4 no-print">
        <form action="<?= url('/reports/sales') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate ?? '') ?>">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate ?? '') ?>">
            </div>
            <div class="col-8 col-md-3">
                <label class="form-label small text-muted mb-1">Filter Kasir:</label>
                <select name="cashier_id" class="form-select form-select-sm">
                    <option value="">Semua Kasir</option>
                    <?php foreach ($cashiers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $cashierId == $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-3 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-wk-primary btn-sm flex-grow-1 py-1">
                    <i class="bi bi-funnel me-1"></i> Terapkan
                </button>
                <?php if (!empty($hasFilter)): ?>
                    <a href="<?= url('/reports/sales') ?>" class="btn btn-outline-secondary btn-sm py-1" title="Reset Filter">
                        <i class="bi bi-x-circle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
        <?php if (!empty($cashierId)): ?>
            <?php 
                $selectedCashierName = 'Kasir #' . $cashierId;
                foreach ($cashiers as $c) {
                    if ($c['id'] == $cashierId) { $selectedCashierName = $c['name']; break; }
                }
            ?>
            <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2 small text-muted">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <i class="bi bi-person-badge me-1"></i> Filter Kasir: <?= e($selectedCashierName) ?>
                </span>
                <span>Menampilkan hasil rekap & transaksi khusus kasir ini.</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Summary KPI Row -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 text-center">
                <span class="text-muted small">Total Omzet (Net Sales)</span>
                <h4 class="fw-bold text-wk-primary mb-0 mt-1"><?= format_rupiah($summary['net_sales']) ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 text-center">
                <span class="text-muted small">Total Transaksi</span>
                <h4 class="fw-bold text-dark mb-0 mt-1"><?= $summary['total_transactions'] ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 text-center">
                <span class="text-muted small">Item Terjual</span>
                <h4 class="fw-bold text-dark mb-0 mt-1"><?= $summary['items_sold'] ?></h4>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 text-center">
                <span class="text-muted small">Total Diskon Diberikan</span>
                <h4 class="fw-bold text-danger mb-0 mt-1"><?= format_rupiah($summary['discount']) ?></h4>
            </div>
        </div>
    </div>

    <!-- Top Products in this period -->
    <div class="wk-card p-3 p-md-4 mb-4 shadow-sm">
        <h6 class="fw-bold mb-3"><i class="bi bi-star-fill text-warning me-2"></i>Produk Terlaris Periode Ini</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">Rank</th>
                        <th>Nama Produk</th>
                        <th class="text-center">Qty Terjual</th>
                        <th class="text-end">Total Omzet</th>
                        <th class="text-end">Estimasi Margin</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr><td colspan="5" class="text-center py-3 text-muted">Belum ada data penjualan pada periode ini.</td></tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $i => $tp): ?>
                            <tr>
                                <td><span class="badge bg-secondary-subtle text-secondary">#<?= $i + 1 ?></span></td>
                                <td class="fw-semibold text-wk-primary"><?= e($tp['product_name']) ?></td>
                                <td class="text-center fw-bold"><?= (int)$tp['total_qty'] ?></td>
                                <td class="text-end fw-semibold"><?= format_rupiah($tp['total_omzet']) ?></td>
                                <td class="text-end text-success fw-semibold"><?= format_rupiah($tp['total_margin']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transactions List according to Filter -->
    <div class="wk-card p-3 p-md-4 mb-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
                <h6 class="fw-bold mb-1">
                    <i class="bi bi-receipt me-2 text-wk-primary"></i>Daftar Transaksi Penjualan (Sesuai Filter)
                </h6>
                <span class="text-muted small">Menampilkan <?= count($transactions) ?> transaksi yang sesuai dengan filter di atas</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th style="width: 40px;">No.</th>
                        <th>No. Transaksi</th>
                        <th>Tanggal & Waktu</th>
                        <th>Kasir</th>
                        <th class="text-center">Metode</th>
                        <th class="text-end">Total Belanja</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-1 text-muted opacity-50"></i>
                                Tidak ada transaksi penjualan yang ditemukan untuk filter yang dipilih.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                            $no = 1; 
                            $tableTotal = 0;
                            foreach ($transactions as $t): 
                                $tableTotal += (float)$t['grand_total'];
                        ?>
                            <tr>
                                <td class="text-muted"><?= $no++ ?></td>
                                <td>
                                    <a href="<?= url('/transactions/' . $t['id']) ?>" class="fw-bold text-decoration-none text-wk-primary">
                                        <?= e($t['transaction_code']) ?>
                                    </a>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($t['transaction_date'])) ?></td>
                                <td><?= e($t['cashier_name'] ?? 'Kasir') ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <?= e($t['payment_method']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-wk-primary">
                                    <?= format_rupiah($t['grand_total']) ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-success-subtle text-success">
                                        <?= e($t['status']) ?>
                                    </span>
                                </td>
                                
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($transactions)): ?>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="5" class="text-end">TOTAL (<?= count($transactions) ?> TRANSAKSI):</td>
                            <td class="text-end text-wk-primary"><?= format_rupiah($tableTotal) ?></td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
