<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Riwayat Mutasi Stok Produk</h4>
            <p class="text-muted small mb-0">Log lengkap audit seluruh pergerakan persediaan stok produk menu</p>
        </div>
        <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Stok
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-3">
        <form action="<?= url('/inventory/history') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-6 col-md-3">
                <select name="item_type" class="form-select small">
                    <option value="PRODUCT" <?= $itemType === 'PRODUCT' ? 'selected' : '' ?>>Produk Menu (Product)</option>
                    <option value="" <?= empty($itemType) ? 'selected' : '' ?>>Semua Tipe Item</option>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <select name="movement_type" class="form-select small">
                    <option value="">Semua Jenis Mutasi</option>
                    <option value="IN" <?= $movementType === 'IN' ? 'selected' : '' ?>>IN (Stok Masuk)</option>
                    <option value="OUT" <?= $movementType === 'OUT' ? 'selected' : '' ?>>OUT (Stok Keluar)</option>
                    <option value="SALE" <?= $movementType === 'SALE' ? 'selected' : '' ?>>SALE (Penjualan)</option>
                    <option value="SALE_REVERSAL" <?= $movementType === 'SALE_REVERSAL' ? 'selected' : '' ?>>SALE_REVERSAL</option>
                    <option value="ADJUSTMENT" <?= $movementType === 'ADJUSTMENT' ? 'selected' : '' ?>>ADJUSTMENT (Koreksi)</option>
                    <option value="VOID" <?= $movementType === 'VOID' ? 'selected' : '' ?>>VOID</option>
                    <option value="REFUND" <?= $movementType === 'REFUND' ? 'selected' : '' ?>>REFUND</option>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="start_date" class="form-control small" value="<?= e($startDate) ?>" title="Dari Tanggal">
            </div>
            <div class="col-6 col-md-2">
                <input type="date" name="end_date" class="form-control small" value="<?= e($endDate) ?>" title="Sampai Tanggal">
            </div>
            <div class="col-12 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-wk-primary btn-sm w-100">Filter</button>
                <a href="<?= url('/inventory/history') ?>" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Movement Ledger Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable" data-order='[[0, "desc"]]'>
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Item</th>
                        <th class="text-center">Tipe</th>
                        <th class="text-center">Jenis Mutasi</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-center">Sebelum &rarr; Sesudah</th>
                        <th>User</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td class="text-muted small text-nowrap" data-order="<?= strtotime($m['created_at']) ?>">
                                <?= date('d/m/Y H:i', strtotime($m['created_at'])) ?>
                            </td>
                            <td class="fw-bold text-wk-primary"><?= e($m['item_name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border small"><?= e($m['item_type']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $badge = 'bg-secondary';
                                    if ($m['movement_type'] === 'IN') $badge = 'bg-success';
                                    elseif ($m['movement_type'] === 'SALE') $badge = 'bg-primary';
                                    elseif ($m['movement_type'] === 'SALE_REVERSAL' || $m['movement_type'] === 'VOID') $badge = 'bg-info text-dark';
                                    elseif ($m['movement_type'] === 'REFUND') $badge = 'bg-warning text-dark';
                                    elseif ($m['movement_type'] === 'OUT') $badge = 'bg-danger';
                                    elseif ($m['movement_type'] === 'ADJUSTMENT') $badge = 'bg-dark';
                                ?>
                                <span class="badge <?= $badge ?> small"><?= e($m['movement_type']) ?></span>
                            </td>
                            <td class="text-end fw-bold text-nowrap">
                                <span class="<?= $m['qty'] < 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $m['qty'] > 0 ? '+' : '' ?><?= number_format($m['qty'], 2, ',', '.') ?> <?= e($m['unit']) ?>
                                </span>
                            </td>
                            <td class="text-center text-muted small text-nowrap">
                                <?= number_format($m['stock_before'], 2, ',', '.') ?> &rarr; <strong class="text-dark"><?= number_format($m['stock_after'], 2, ',', '.') ?></strong>
                            </td>
                            <td class="small text-muted"><?= e($m['creator_name'] ?? 'Sistem') ?></td>
                            <td class="small text-muted"><?= e($m['note'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
