<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Riwayat Transaksi</h4>
            <p class="text-muted small mb-0">Daftar rekaman seluruh penjualan kedai</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= url('/transactions/export-xlsx?' . http_build_query($_GET)) ?>" class="btn btn-outline-success btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow-sm" title="Unduh seluruh data transaksi dalam format Excel (.xlsx)">
                <i class="bi bi-file-earmark-excel fs-6"></i> Ekspor Excel (.xlsx)
            </a>
            <a href="<?= url('/pos') ?>" class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2 shadow-sm">
                <i class="bi bi-cart3"></i> Buka POS Kasir
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-3">
        <form action="<?= url('/transactions') ?>" method="GET" class="row g-2 align-items-center">
            
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small text-muted mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate ?? '') ?>">
            </div>
            <?php if (is_owner() && !empty($cashiers)): ?>
                <div class="col-6 col-md-2">
                    <label class="form-label small text-muted mb-1">Kasir:</label>
                    <select name="cashier_id" class="form-select form-select-sm">
                        <option value="">Semua Kasir</option>
                        <?php foreach ($cashiers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $cashierId == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-6 col-md-<?= (is_owner() && !empty($cashiers)) ? '1' : '2' ?>">
                <label class="form-label small text-muted mb-1">Metode Bayar:</label>
                <select name="payment_method" class="form-select form-select-sm">
                    <option value="">Semua Metode</option>
                    <option value="CASH" <?= $paymentMethod === 'CASH' ? 'selected' : '' ?>>Cash (Tunai)</option>
                    <option value="QRIS" <?= $paymentMethod === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                    <option value="TRANSFER" <?= $paymentMethod === 'TRANSFER' ? 'selected' : '' ?>>Transfer</option>
                    <option value="EWALLET" <?= $paymentMethod === 'EWALLET' ? 'selected' : '' ?>>E-Wallet</option>
                </select>
            </div>
            <div class="col-6 col-md-<?= (is_owner() && !empty($cashiers)) ? '1' : '2' ?>">
                <label class="form-label small text-muted mb-1">Status:</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <option value="PAID" <?= $status === 'PAID' ? 'selected' : '' ?>>PAID</option>
                    <option value="VOID" <?= $status === 'VOID' ? 'selected' : '' ?>>VOID</option>
                    <option value="REFUNDED" <?= $status === 'REFUNDED' ? 'selected' : '' ?>>REFUND</option>
                    <option value="PARTIAL_REFUND" <?= $status === 'PARTIAL_REFUND' ? 'selected' : '' ?>>PARTIAL</option>
                </select>
            </div>
            <div class="col-12 col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-wk-primary btn-sm flex-grow-1" title="Terapkan Filter">Search <i class="bi bi-search"></i></button>
                <a href="<?= url('/transactions') ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filter">Reset <i class="bi bi-arrow-counterclockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Transactions Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable" data-order='[[1, "desc"]]'>
                <thead class="table-light">
                    <tr>
                        <th>No. Transaksi</th>
                        <th>Waktu</th>
                        <th>Kasir</th>
                        <th>Metode Bayar</th>
                        <th class="text-end">Total Tagihan</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/transactions/' . $t['id']) ?>" class="fw-bold text-wk-primary text-decoration-none">
                                    <?= e($t['transaction_code']) ?>
                                </a>
                            </td>
                            <td class="text-muted small text-nowrap" data-order="<?= strtotime($t['transaction_date']) ?>">
                                <?= date('d/m/Y H:i', strtotime($t['transaction_date'])) ?>
                            </td>
                            <td><?= e($t['cashier_name'] ?? 'Kasir') ?></td>
                            <td>
                                <span class="badge bg-light text-dark border small"><?= e($t['payment_method']) ?></span>
                            </td>
                            <td class="text-end fw-bold text-nowrap"><?= format_rupiah($t['grand_total']) ?></td>
                            <td class="text-center">
                                <?php 
                                    $badgeClass = 'bg-success';
                                    if ($t['status'] === 'VOID') $badgeClass = 'bg-danger';
                                    elseif ($t['status'] === 'REFUNDED') $badgeClass = 'bg-warning text-dark';
                                    elseif ($t['status'] === 'PARTIAL_REFUND') $badgeClass = 'bg-info text-dark';
                                    elseif ($t['status'] === 'HELD') $badgeClass = 'bg-secondary';
                                ?>
                                <span class="badge <?= $badgeClass ?> small"><?= e($t['status']) ?></span>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if (is_owner()): ?>
                                    <a href="<?= url('/transactions/' . $t['id'] . '?edit=1') ?>" class="btn btn-sm btn-outline-warning p-1 px-2 me-1" title="Edit Transaksi">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="<?= url('/transactions/' . $t['id']) ?>" class="btn btn-sm btn-outline-secondary p-1 px-2" title="Detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                                <a href="<?= url('/transactions/' . $t['id'] . '/receipt') ?>" target="_blank" class="btn btn-sm btn-outline-primary p-1 px-2" title="Cetak Struk">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
