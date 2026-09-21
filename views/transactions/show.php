<div class="container-fluid px-0" style="max-width: 900px;">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0">Transaksi #<?= e($trans['transaction_code']) ?></h4>
                <?php 
                    $badgeClass = 'bg-success';
                    if ($trans['status'] === 'VOID') $badgeClass = 'bg-danger';
                    elseif ($trans['status'] === 'REFUNDED') $badgeClass = 'bg-warning text-dark';
                    elseif ($trans['status'] === 'PARTIAL_REFUND') $badgeClass = 'bg-info text-dark';
                ?>
                <span class="badge <?= $badgeClass ?>"><?= e($trans['status']) ?></span>
            </div>
            <p class="text-muted small mb-0"><?= date('d/m/Y H:i', strtotime($trans['transaction_date'])) ?> • Kasir: <?= e($trans['cashier_name'] ?? 'Kasir') ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (is_owner()): ?>
                <button type="button" class="btn btn-warning text-dark btn-sm px-3 py-2 fw-semibold d-flex align-items-center gap-1 shadow-sm" data-bs-toggle="modal" data-bs-target="#editTransactionModal">
                    <i class="bi bi-pencil-square"></i> Edit Transaksi
                </button>
            <?php endif; ?>
            <a href="<?= url('/transactions/' . $trans['id'] . '/receipt') ?>" target="_blank" class="btn btn-outline-primary btn-sm px-3 py-2">
                <i class="bi bi-printer me-1"></i> Cetak Ulang Struk (Reprint)
            </a>
            <a href="<?= url('/transactions') ?>" class="btn btn-outline-secondary btn-sm px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Alert if VOID or REFUND -->
    <?php if ($trans['status'] === 'VOID'): ?>
        <div class="alert alert-danger shadow-sm border-0 mb-4 p-3 rounded-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-x-circle me-1"></i> Transaksi Ini Telah Dibatalkan (VOID)</h6>
            <div class="small">Alasan: <?= e($trans['void_reason'] ?? '—') ?></div>
            <div class="small text-muted mt-1">Dibatalkan oleh: <?= e($trans['void_by_name'] ?? 'Owner') ?> pada <?= date('d/m/Y H:i', strtotime($trans['void_at'])) ?></div>
        </div>
    <?php elseif ($trans['status'] === 'REFUNDED' || $trans['status'] === 'PARTIAL_REFUND'): ?>
        <div class="alert alert-warning shadow-sm border-0 mb-4 p-3 rounded-3">
            <h6 class="fw-bold mb-1"><i class="bi bi-arrow-counterclockwise me-1"></i> Transaksi Mengalami Refund</h6>
            <div class="small">Alasan: <?= e($trans['refund_reason'] ?? '—') ?></div>
            <div class="small text-muted mt-1">Diproses oleh: <?= e($trans['refund_by_name'] ?? 'Owner') ?> pada <?= date('d/m/Y H:i', strtotime($trans['refund_at'])) ?></div>
        </div>
    <?php endif; ?>

    <!-- Transaction Summary Card -->
    <div class="row g-3 mb-4">
        <!-- Items Table -->
        <div class="col-12 col-md-8">
            <div class="wk-card p-3 p-md-4 shadow-sm h-100">
                <h6 class="fw-bold mb-3"><i class="bi bi-bag-check me-2"></i>Daftar Item Menu</h6>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-wk-primary"><?= e($item['product_name']) ?></div>
                                        <?php if ($item['refunded_qty'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger">Refund: <?= (int)$item['refunded_qty'] ?> unit</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= format_rupiah($item['selling_price']) ?></td>
                                    <td class="text-center fw-bold"><?= (int)$item['qty'] ?></td>
                                    <td class="text-end fw-semibold"><?= format_rupiah($item['subtotal']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment & Financial Overview -->
        <div class="col-12 col-md-4">
            <div class="wk-card p-3 p-md-4 shadow-sm h-100 d-flex flex-column justify-content-between">
                <div>
                    <h6 class="fw-bold mb-3"><i class="bi bi-cash-stack me-2"></i>Rincian Pembayaran</h6>
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Subtotal:</span>
                        <span><?= format_rupiah($trans['subtotal']) ?></span>
                    </div>
                    <?php if ($trans['discount_amount'] > 0): ?>
                        <div class="d-flex justify-content-between text-muted small mb-1">
                            <span>Diskon:</span>
                            <span class="text-danger">-<?= format_rupiah($trans['discount_amount']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between fw-bold fs-5 my-2 pt-2 border-top">
                        <span>Total Tagihan:</span>
                        <span class="text-wk-primary"><?= format_rupiah($trans['grand_total']) ?></span>
                    </div>

                    <hr class="my-3">

                    <div class="small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Metode:</span>
                            <span class="badge bg-light text-dark border"><?= e($trans['payment_method']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Dibayar:</span>
                            <span><?= format_rupiah($trans['paid_amount']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Kembalian:</span>
                            <?php if ($trans['change_amount'] < 0): ?>
                                <span class="fw-semibold text-danger">- <?= format_rupiah(abs($trans['change_amount'])) ?> (Kurang)</span>
                            <?php else: ?>
                                <span class="fw-semibold text-success"><?= format_rupiah($trans['change_amount']) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($trans['total_cogs'])): ?>
                            <div class="d-flex justify-content-between mb-1 pt-2 border-top text-muted" style="font-size: 0.8rem;">
                                <span>HPP (Modal):</span>
                                <span><?= format_rupiah($trans['total_cogs']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($trans['payment_proof']) && file_exists(__DIR__ . '/../../public/uploads/payments/' . $trans['payment_proof'])): ?>
                        <div class="mt-3 pt-3 border-top">
                            <label class="fw-semibold small text-muted d-flex align-items-center justify-content-between mb-2">
                                <span><i class="bi bi-image text-primary me-1"></i> Bukti Bayar Non-Tunai:</span>
                                <span class="badge bg-primary-subtle text-primary small">Non-Tunai</span>
                            </label>
                            <div class="position-relative rounded overflow-hidden border p-1 bg-body-tertiary text-center shadow-sm" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#paymentProofModal" title="Klik untuk memperbesar">
                                <img src="<?= asset('/uploads/payments/' . $trans['payment_proof']) ?>" alt="Bukti Pembayaran" style="max-height: 160px; max-width: 100%; object-fit: contain; border-radius: 6px;">
                                <div class="mt-2 small text-primary fw-semibold d-flex align-items-center justify-content-center gap-1">
                                    <i class="bi bi-zoom-in"></i> Klik untuk Perbesar
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Owner Actions: Void & Refund -->
                <?php if (is_owner() && $trans['status'] === 'PAID'): ?>
                    <div class="pt-3 border-top mt-3 d-flex flex-column gap-2">
                        <button class="btn btn-outline-warning btn-sm w-100" data-bs-toggle="modal" data-bs-target="#refundModal">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Refund Item
                        </button>
                        <button class="btn btn-outline-danger btn-sm w-100" data-bs-toggle="modal" data-bs-target="#voidModal">
                            <i class="bi bi-x-octagon me-1"></i> Batalkan Transaksi (Void)
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- VOID MODAL (Owner Only) -->
<?php if (is_owner() && $trans['status'] === 'PAID'): ?>
<div class="modal fade" id="voidModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/transactions/' . $trans['id'] . '/void') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Batalkan Transaksi (VOID)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Tindakan ini akan membatalkan status transaksi dan mencatat pembatalan ke dalam sistem.
                    </p>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Pembatalan <span class="text-danger">*</span></label>
                        <textarea name="void_reason" class="form-control" rows="3" placeholder="Contoh: Kesalahan input kasir / pelanggan membatalkan pesanan" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-danger btn-sm px-3">Konfirmasi VOID</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- REFUND MODAL (Owner Only) -->
<div class="modal fade" id="refundModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/transactions/' . $trans['id'] . '/refund') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold text-warning text-dark"><i class="bi bi-arrow-counterclockwise me-2"></i>Proses Refund Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Pilih jumlah item yang ingin direfund ke pelanggan.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Jumlah Item Refund:</label>
                        <?php foreach ($items as $item): ?>
                            <?php $maxRefundable = (int)$item['qty'] - (int)$item['refunded_qty']; ?>
                            <?php if ($maxRefundable > 0): ?>
                                <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="small">
                                        <div class="fw-semibold"><?= e($item['product_name']) ?></div>
                                        <div class="text-muted">Maksimal: <?= $maxRefundable ?> unit</div>
                                    </div>
                                    <div style="width: 90px;">
                                        <input type="number" name="refund_qty[<?= $item['id'] ?>]" class="form-control form-control-sm text-center" min="0" max="<?= $maxRefundable ?>" value="0">
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nominal Refund (Rp):</label>
                        <input type="number" name="refund_amount" class="form-control" placeholder="Nominal uang yang dikembalikan ke pelanggan" required>
                    </div>



                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Refund <span class="text-danger">*</span></label>
                        <textarea name="refund_reason" class="form-control" rows="2" placeholder="Contoh: Kopi tumpah / pesanan tidak sesuai" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm px-3">Proses Refund</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- OWNER EDIT TRANSACTION MODAL -->
<?php if (is_owner()): ?>
<div class="modal fade" id="editTransactionModal" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable my-3" style="max-height: calc(100vh - 3rem);">
        <form action="<?= url('/transactions/' . $trans['id'] . '/update') ?>" 
              method="POST" 
              id="formEditTransaction" 
              enctype="multipart/form-data"
              class="modal-content border-0 shadow-lg" 
              style="max-height: calc(100vh - 3rem); display: flex; flex-direction: column; overflow: hidden;">
            <?= csrf_field() ?>
            <div class="modal-header border-bottom py-3 bg-body-tertiary" style="flex-shrink: 0; position: sticky; top: 0; z-index: 20;">
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="modal-title fw-bold mb-0">
                            <i class="bi bi-pencil-square text-warning me-1"></i> Edit Detail Transaksi #<?= e($trans['transaction_code']) ?>
                        </h5>
                        <span class="badge bg-warning text-dark small">Khusus Owner</span>
                    </div>
                    <small class="text-muted">Koreksi tanggal, kasir, rincian menu penjualan, harga, status, diskon, dan metode pembayaran</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>

            <div class="modal-body p-3 p-md-4" style="flex-grow: 1; overflow-y: auto;">
                    <!-- SECTION 1: HEADER METADATA (Tanggal, Kasir, Status, Catatan) -->
                    <div class="p-3 rounded-3 mb-4 border bg-body-tertiary">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="bi bi-info-circle me-1 text-primary"></i> 1. Informasi Utama Transaksi
                        </h6>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small">Tanggal & Waktu Transaksi <span class="text-danger">*</span></label>
                                <input type="datetime-local" 
                                       name="transaction_date" 
                                       class="form-control" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($trans['transaction_date'])) ?>" 
                                       required>
                                <small class="text-muted" style="font-size: 0.72rem;">Dapat diatur mundur/maju untuk koreksi pembukuan</small>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small">Kasir yang Bertugas <span class="text-danger">*</span></label>
                                <select name="cashier_id" class="form-select" required>
                                    <?php foreach ($cashiers as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (int)$c['id'] === (int)$trans['cashier_id'] ? 'selected' : '' ?>>
                                            <?= e($c['name']) ?> (<?= e($c['role']) ?> • @<?= e($c['username']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label fw-semibold small">Status Transaksi <span class="text-danger">*</span></label>
                                <select name="status" class="form-select" required>
                                    <option value="PAID" <?= $trans['status'] === 'PAID' ? 'selected' : '' ?>>PAID (Lunas / Berhasil)</option>
                                    <option value="HELD" <?= $trans['status'] === 'HELD' ? 'selected' : '' ?>>HELD (Ditahan)</option>
                                    <option value="VOID" <?= $trans['status'] === 'VOID' ? 'selected' : '' ?>>VOID (Dibatalkan)</option>
                                    <option value="REFUNDED" <?= $trans['status'] === 'REFUNDED' ? 'selected' : '' ?>>REFUNDED (Refund Penuh)</option>
                                    <option value="PARTIAL_REFUND" <?= $trans['status'] === 'PARTIAL_REFUND' ? 'selected' : '' ?>>PARTIAL_REFUND (Sebagian)</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold small">Catatan Pesanan / Meja</label>
                                <input type="text" 
                                       name="hold_note" 
                                       class="form-control" 
                                       value="<?= e($trans['hold_note'] ?? '') ?>" 
                                       placeholder="Contoh: Meja 4 / Pesanan Takeaway Bpk. Rudi">
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: DETAIL PENJUALAN (ITEMS) -->
                    <div class="p-3 rounded-3 mb-4 border">
                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
                            <h6 class="fw-bold text-dark m-0">
                                <i class="bi bi-cart-check me-1 text-success"></i> 2. Rincian Item Penjualan
                            </h6>
                            <div class="d-flex align-items-center gap-2 w-100 w-sm-auto">
                                <select id="selectQuickProduct" class="form-select form-select-sm" style="max-width: 250px;">
                                    <option value="">-- Pilih Menu dari Katalog --</option>
                                    <?php foreach ($availableProducts as $prod): ?>
                                        <option value="<?= $prod['id'] ?>" 
                                                data-name="<?= e($prod['name']) ?>" 
                                                data-price="<?= (float)$prod['selling_price'] ?>"
                                                data-cost="<?= (float)$prod['cost_price'] ?>">
                                            <?= e($prod['name']) ?> (Rp <?= number_format($prod['selling_price'], 0, ',', '.') ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-sm btn-outline-success text-nowrap" onclick="addCatalogItem()">
                                    <i class="bi bi-plus-circle"></i> Tambah Menu
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-2 small" id="editItemsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width: 220px;">Nama Produk / Menu</th>
                                        <th class="text-center" style="width: 110px;">Qty</th>
                                        <th class="text-end" style="width: 150px;">Harga Satuan (Rp)</th>
                                        <th class="text-end" style="width: 160px;">Subtotal (Rp)</th>
                                        <th class="text-center" style="width: 60px;">Hapus</th>
                                    </tr>
                                </thead>
                                <tbody id="editItemsTbody">
                                    <?php foreach ($items as $idx => $it): ?>
                                        <tr class="item-row">
                                            <td>
                                                <input type="hidden" name="item_product_id[]" value="<?= e($it['product_id'] ?? '') ?>">
                                                <input type="text" name="item_product_name[]" class="form-control form-control-sm fw-semibold" value="<?= e($it['product_name']) ?>" required>
                                            </td>
                                            <td>
                                                <input type="number" name="item_qty[]" class="form-control form-control-sm text-center row-qty" min="1" value="<?= (int)$it['qty'] ?>" oninput="recalcEditTransaction()" required>
                                            </td>
                                            <td>
                                                <input type="number" name="item_price[]" class="form-control form-control-sm text-end row-price" min="0" step="500" value="<?= (float)$it['selling_price'] ?>" oninput="recalcEditTransaction()" required>
                                            </td>
                                            <td class="text-end fw-bold row-subtotal-text font-monospace">
                                                <?= format_rupiah($it['subtotal']) ?>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeEditItemRow(this)" title="Hapus Item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                            <span class="text-muted small">Total Subtotal Item:</span>
                            <strong class="fs-6 text-dark font-monospace" id="displaySubtotal">Rp 0</strong>
                        </div>
                    </div>

                    <!-- SECTION 3: DISKON & PEMBAYARAN -->
                    <div class="row g-3 mb-4">
                        <!-- Diskon -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 border h-100 bg-body-tertiary">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="fw-bold text-dark m-0">
                                        <i class="bi bi-tag me-1 text-danger"></i> 3. Diskon Penjualan
                                    </h6>
                                    <?php $hasDiscount = ($trans['discount_type'] !== 'NONE' && (float)$trans['discount_value'] > 0); ?>
                                    <button type="button" id="btnToggleDiscount" class="btn btn-sm btn-outline-danger py-0 px-2 small <?= $hasDiscount ? 'd-none' : '' ?>" onclick="toggleEditDiscount(true)">
                                        <i class="bi bi-plus-circle me-1"></i> Tambah Diskon
                                    </button>
                                    <button type="button" id="btnRemoveDiscount" class="btn btn-sm btn-outline-secondary py-0 px-2 small <?= $hasDiscount ? '' : 'd-none' ?>" onclick="toggleEditDiscount(false)">
                                        <i class="bi bi-x-circle me-1"></i> Hapus Diskon
                                    </button>
                                </div>

                                <div id="editDiscountFields" class="<?= $hasDiscount ? '' : 'd-none' ?>">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small">Tipe Diskon</label>
                                            <select name="discount_type" id="editDiscountType" class="form-select form-select-sm" onchange="recalcEditTransaction()">
                                                <option value="NONE" <?= $trans['discount_type'] === 'NONE' ? 'selected' : '' ?>>Tanpa Diskon</option>
                                                <option value="PERCENT" <?= $trans['discount_type'] === 'PERCENT' ? 'selected' : '' ?>>Persen (%)</option>
                                                <option value="FIXED" <?= $trans['discount_type'] === 'FIXED' ? 'selected' : '' ?>>Nominal Tetap (Rp)</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small">Nilai Diskon</label>
                                            <input type="number" 
                                                   name="discount_value" 
                                                   id="editDiscountValue" 
                                                   class="form-control form-control-sm text-end" 
                                                   min="0" 
                                                   value="<?= (float)($trans['discount_value'] ?? 0) ?>" 
                                                   oninput="recalcEditTransaction()">
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                                        <span>Potongan Diskon:</span>
                                        <strong class="text-danger font-monospace" id="displayDiscountAmount">- Rp 0</strong>
                                    </div>
                                </div>
                                <div id="editDiscountEmptyHint" class="<?= $hasDiscount ? 'd-none' : '' ?> text-muted small py-2">
                                    <i class="bi bi-info-circle me-1"></i> Tidak ada diskon. Klik <strong>"Tambah Diskon"</strong> jika ada potongan harga.
                                </div>
                                <div class="mt-2 pt-2 border-top d-flex justify-content-between fs-6 fw-bold">
                                    <span>Total Tagihan (Grand Total):</span>
                                    <span class="text-wk-primary font-monospace" id="displayGrandTotal">Rp 0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pembayaran -->
                        <div class="col-12 col-md-6">
                            <div class="p-3 rounded-3 border h-100 bg-body-tertiary">
                                <h6 class="fw-bold text-dark mb-3">
                                    <i class="bi bi-cash-stack me-1 text-success"></i> 4. Rincian Pembayaran
                                </h6>
                                
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Metode Bayar <span class="text-danger">*</span></label>
                                        <select name="payment_method" id="editPaymentMethodSelect" class="form-select form-select-sm fw-semibold" onchange="handleEditPaymentMethodChange(this.value)">
                                            <option value="CASH" <?= $trans['payment_method'] === 'CASH' ? 'selected' : '' ?>>CASH (Tunai)</option>
                                            <option value="QRIS" <?= $trans['payment_method'] === 'QRIS' ? 'selected' : '' ?>>QRIS</option>
                                            <option value="TRANSFER" <?= $trans['payment_method'] === 'TRANSFER' ? 'selected' : '' ?>>Transfer Bank</option>
                                            <option value="EWALLET" <?= $trans['payment_method'] === 'EWALLET' ? 'selected' : '' ?>>E-Wallet</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label fw-semibold small">Uang Diterima / Dibayar (Rp) <span class="text-danger">*</span></label>
                                        <input type="number" 
                                               name="paid_amount" 
                                               id="editPaidAmount" 
                                               class="form-control form-control-sm text-end fw-semibold" 
                                               min="0" 
                                               value="<?= (float)($trans['paid_amount'] > 0 ? $trans['paid_amount'] : $trans['grand_total']) ?>" 
                                               oninput="onEditPaidAmountInput()" 
                                               required>
                                    </div>
                                </div>

                                <!-- CASH ONLY: PILIHAN NOMINAL CEPAT & KEMBALIAN (Mirip POS) -->
                                <div id="editCashSection" class="<?= $trans['payment_method'] === 'CASH' ? '' : 'd-none' ?>">
                                    <label class="form-label text-muted small mb-1">Pilihan Nominal Cepat:</label>
                                    <div class="row g-2 mb-2" id="editQuickCashGroup">
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash('exact', this)">Uang Pas</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash(10000, this)">10.000</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash(20000, this)">20.000</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash(50000, this)">50.000</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash(100000, this)">100.000</button>
                                        </div>
                                        <div class="col-4">
                                            <button type="button" class="btn btn-outline-secondary btn-sm w-100 py-1 btn-quick-cash fw-semibold" onclick="setEditQuickCash(200000, this)">200.000</button>
                                        </div>
                                    </div>
                                    <div class="p-2 border rounded-2 bg-body-secondary d-flex justify-content-between align-items-center mb-2">
                                        <span class="small fw-semibold text-muted">Kembalian:</span>
                                        <strong class="text-success fs-6 font-monospace" id="displayChangeAmount">Rp 0</strong>
                                    </div>
                                </div>

                                <!-- NON-CASH ONLY: PROVIDER & NO REFERENSI (Logic mirip POS) -->
                                <div id="editNonCashSection" class="<?= $trans['payment_method'] === 'CASH' ? 'd-none' : '' ?>">
                                    <!-- E-Wallet Provider Selection (Hanya saat EWALLET dipilih) -->
                                    <?php 
                                        $curProvider = $payments[0]['provider'] ?? '';
                                        $ewalletOptions = ['GoPay', 'OVO', 'DANA', 'ShopeePay', 'Lainnya'];
                                    ?>
                                    <div id="editEwalletProviderRow" class="<?= $trans['payment_method'] === 'EWALLET' ? '' : 'd-none' ?> mb-2 p-2 border rounded bg-body-secondary">
                                        <label class="form-label small fw-semibold mb-1 d-block text-muted">Pilih Provider E-Wallet:</label>
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php foreach ($ewalletOptions as $opt): ?>
                                                <div class="form-check form-check-inline m-0">
                                                    <input class="form-check-input" type="radio" name="edit_ewallet_radio" id="editEw_<?= $opt ?>" value="<?= $opt ?>" 
                                                           <?= ($curProvider === $opt || (empty($curProvider) && $opt === 'GoPay')) ? 'checked' : '' ?>
                                                           onchange="onEditEwalletRadioChange(this.value)">
                                                    <label class="form-check-label small fw-semibold" for="editEw_<?= $opt ?>"><?= $opt ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small" id="editProviderLabel">Provider (Bank/Dompet)</label>
                                            <input type="text" 
                                                   name="provider" 
                                                   id="editProviderInput"
                                                   class="form-control form-control-sm" 
                                                   value="<?= e($payments[0]['provider'] ?? '') ?>" 
                                                   placeholder="BCA, Mandiri, GoPay, dll">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label fw-semibold small">No. Referensi / Trx (Opsional)</label>
                                            <input type="text" 
                                                   name="reference_number" 
                                                   id="editReferenceNumberInput"
                                                   class="form-control form-control-sm" 
                                                   value="<?= e($payments[0]['reference_number'] ?? '') ?>" 
                                                   placeholder="No ref/struk/approval">
                                        </div>
                                    </div>
                                </div>

                                <!-- Foto Bukti Pembayaran Non-Tunai (Kamera / Upload) -->
                                <?php 
                                    $hasExistingProof = !empty($trans['payment_proof']) && file_exists(__DIR__ . '/../../public/uploads/payments/' . $trans['payment_proof']); 
                                    $isNonCash = in_array($trans['payment_method'], ['QRIS', 'TRANSFER', 'EWALLET']);
                                ?>
                                <div id="editPaymentProofSection" class="<?= $isNonCash ? '' : 'd-none' ?> mt-2 pt-2 border-top">
                                    <label class="form-label fw-semibold small d-flex align-items-center justify-content-between mb-1">
                                        <span><i class="bi bi-camera-fill text-primary me-1"></i> Foto Bukti Pembayaran Non-Tunai</span>
                                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.72rem;">Opsional</span>
                                    </label>

                                    <input type="file" 
                                           name="payment_proof" 
                                           id="editPaymentProofInput" 
                                           accept="image/jpeg,image/png,image/webp" 
                                           capture="environment" 
                                           class="d-none" 
                                           onchange="previewEditPaymentProof(this)">
                                    <input type="hidden" name="delete_payment_proof" id="deletePaymentProofInput" value="0">

                                    <div id="editProofPlaceholder" class="<?= $hasExistingProof ? 'd-none' : '' ?> p-2 border border-dashed rounded-3 text-center bg-body-secondary">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 fw-semibold d-inline-flex align-items-center gap-1 my-1" onclick="document.getElementById('editPaymentProofInput').click()">
                                            <i class="bi bi-camera fs-6"></i>
                                            <span>Ambil Foto / Pilih File</span>
                                        </button>
                                        <div class="text-muted small" style="font-size: 0.72rem;">Mendukung akses kamera HP/webcam atau upload gambar bukti.</div>
                                    </div>

                                    <div id="editProofPreviewBox" class="<?= $hasExistingProof ? '' : 'd-none' ?> p-2 border rounded-3 bg-body-secondary text-center position-relative">
                                        <img id="editProofPreviewImg" 
                                             src="<?= $hasExistingProof ? asset('/uploads/payments/' . $trans['payment_proof']) : '' ?>" 
                                             alt="Bukti Bayar" 
                                             style="max-height: 150px; max-width: 100%; object-fit: contain; border-radius: 6px;" 
                                             class="shadow-sm border">
                                        <div class="mt-2 d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2 small" onclick="document.getElementById('editPaymentProofInput').click()">
                                                <i class="bi bi-arrow-repeat me-1"></i>Ganti Foto
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 small" onclick="removeEditPaymentProofPhoto()">
                                                <i class="bi bi-trash me-1"></i>Hapus Foto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>

                <div class="modal-footer border-top p-3 bg-body-tertiary d-flex justify-content-between" style="flex-shrink: 0; position: sticky; bottom: 0; z-index: 20;">
                    <button type="button" class="btn btn-outline-secondary btn-sm px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan Transaksi
                    </button>
                </div>
            </form>
    </div>
</div>

<script>
function formatRupiahJs(num) {
    return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(num));
}

function recalcEditTransaction() {
    const rows = document.querySelectorAll('#editItemsTbody .item-row');
    let subtotal = 0;

    rows.forEach(row => {
        const qtyInput = row.querySelector('.row-qty');
        const priceInput = row.querySelector('.row-price');
        const subtotalText = row.querySelector('.row-subtotal-text');

        const qty = Math.max(1, parseFloat(qtyInput.value) || 0);
        const price = Math.max(0, parseFloat(priceInput.value) || 0);
        const rowSubtotal = qty * price;

        subtotal += rowSubtotal;
        if (subtotalText) {
            subtotalText.textContent = formatRupiahJs(rowSubtotal);
        }
    });

    const displaySubtotal = document.getElementById('displaySubtotal');
    if (displaySubtotal) displaySubtotal.textContent = formatRupiahJs(subtotal);

    // Discount
    const discType = document.getElementById('editDiscountType').value;
    const discVal = parseFloat(document.getElementById('editDiscountValue').value) || 0;
    let discAmount = 0;

    if (discType === 'PERCENT') {
        discAmount = (subtotal * discVal) / 100;
    } else if (discType === 'FIXED') {
        discAmount = Math.min(subtotal, discVal);
    }

    const displayDiscountAmount = document.getElementById('displayDiscountAmount');
    if (displayDiscountAmount) displayDiscountAmount.textContent = '- ' + formatRupiahJs(discAmount);

    const grandTotal = Math.max(0, subtotal - discAmount);
    const displayGrandTotal = document.getElementById('displayGrandTotal');
    if (displayGrandTotal) displayGrandTotal.textContent = formatRupiahJs(grandTotal);

    // Paid & Change (Menampilkan minus jika uang kurang dari harga)
    const paidInput = document.getElementById('editPaidAmount');
    const paidAmount = parseFloat(paidInput.value) || 0;
    const change = paidAmount - grandTotal;

    const displayChangeAmount = document.getElementById('displayChangeAmount');
    if (displayChangeAmount) {
        if (change < 0) {
            displayChangeAmount.className = 'text-danger fs-6 font-monospace fw-bold';
            displayChangeAmount.textContent = '- ' + formatRupiahJs(Math.abs(change));
        } else {
            displayChangeAmount.className = 'text-success fs-6 font-monospace fw-bold';
            displayChangeAmount.textContent = formatRupiahJs(change);
        }
    }
}

function removeEditItemRow(btn) {
    const tbody = document.getElementById('editItemsTbody');
    const rows = tbody.querySelectorAll('.item-row');
    if (rows.length <= 1) {
        alert('Transaksi harus memiliki minimal 1 item.');
        return;
    }
    const tr = btn.closest('tr');
    if (tr) {
        tr.remove();
        recalcEditTransaction();
    }
}

function addCatalogItem() {
    const select = document.getElementById('selectQuickProduct');
    const selectedOpt = select.options[select.selectedIndex];
    if (!selectedOpt || !selectedOpt.value) {
        alert('Silakan pilih produk dari katalog terlebih dahulu.');
        return;
    }

    const prodId = selectedOpt.value;
    const prodName = selectedOpt.getAttribute('data-name');
    const prodPrice = parseFloat(selectedOpt.getAttribute('data-price')) || 0;

    appendItemRow(prodId, prodName, 1, prodPrice);
    select.value = '';
}

function toggleEditDiscount(show) {
    const fields = document.getElementById('editDiscountFields');
    const emptyHint = document.getElementById('editDiscountEmptyHint');
    const btnToggle = document.getElementById('btnToggleDiscount');
    const btnRemove = document.getElementById('btnRemoveDiscount');
    const typeSelect = document.getElementById('editDiscountType');
    const valInput = document.getElementById('editDiscountValue');

    if (show) {
        if (fields) fields.classList.remove('d-none');
        if (emptyHint) emptyHint.classList.add('d-none');
        if (btnToggle) btnToggle.classList.add('d-none');
        if (btnRemove) btnRemove.classList.remove('d-none');
        if (typeSelect && typeSelect.value === 'NONE') {
            typeSelect.value = 'PERCENT';
        }
    } else {
        if (fields) fields.classList.add('d-none');
        if (emptyHint) emptyHint.classList.remove('d-none');
        if (btnToggle) btnToggle.classList.remove('d-none');
        if (btnRemove) btnRemove.classList.add('d-none');
        if (typeSelect) typeSelect.value = 'NONE';
        if (valInput) valInput.value = 0;
    }
    recalcEditTransaction();
}

function appendItemRow(prodId, name, qty, price) {
    const tbody = document.getElementById('editItemsTbody');
    const tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = `
        <td>
            <input type="hidden" name="item_product_id[]" value="${prodId}">
            <input type="text" name="item_product_name[]" class="form-control form-control-sm fw-semibold" value="${name}" placeholder="Nama menu/produk" required>
        </td>
        <td>
            <input type="number" name="item_qty[]" class="form-control form-control-sm text-center row-qty" min="1" value="${qty}" oninput="recalcEditTransaction()" required>
        </td>
        <td>
            <input type="number" name="item_price[]" class="form-control form-control-sm text-end row-price" min="0" step="500" value="${price}" oninput="recalcEditTransaction()" required>
        </td>
        <td class="text-end fw-bold row-subtotal-text font-monospace">
            ${formatRupiahJs(qty * price)}
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger p-1" onclick="removeEditItemRow(this)" title="Hapus Item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    recalcEditTransaction();
}

function getEditGrandTotal() {
    const rows = document.querySelectorAll('#editItemsTbody .item-row');
    let subtotal = 0;
    rows.forEach(row => {
        const qty = Math.max(1, parseFloat(row.querySelector('.row-qty')?.value) || 0);
        const price = Math.max(0, parseFloat(row.querySelector('.row-price')?.value) || 0);
        subtotal += qty * price;
    });

    const discType = document.getElementById('editDiscountType')?.value || 'NONE';
    const discVal = parseFloat(document.getElementById('editDiscountValue')?.value) || 0;
    let discAmount = 0;
    if (discType === 'PERCENT') {
        discAmount = (subtotal * discVal) / 100;
    } else if (discType === 'FIXED') {
        discAmount = Math.min(subtotal, discVal);
    }
    return Math.max(0, subtotal - discAmount);
}

function handleEditPaymentMethodChange(method) {
    const cashSec = document.getElementById('editCashSection');
    const nonCashSec = document.getElementById('editNonCashSection');
    const proofSec = document.getElementById('editPaymentProofSection');
    const ewalletRow = document.getElementById('editEwalletProviderRow');
    const providerInput = document.getElementById('editProviderInput');
    const providerLabel = document.getElementById('editProviderLabel');
    const paidInput = document.getElementById('editPaidAmount');

    if (method === 'CASH') {
        if (cashSec) cashSec.classList.remove('d-none');
        if (nonCashSec) nonCashSec.classList.add('d-none');
        if (proofSec) proofSec.classList.add('d-none');
        if (providerInput) providerInput.value = '';
    } else {
        if (cashSec) cashSec.classList.add('d-none');
        if (nonCashSec) nonCashSec.classList.remove('d-none');
        if (proofSec) proofSec.classList.remove('d-none');

        // Non-tunai otomatis set nominal pas sesuai grand total
        const gt = getEditGrandTotal();
        if (paidInput) {
            paidInput.value = Math.round(gt);
        }

        if (method === 'EWALLET') {
            if (ewalletRow) ewalletRow.classList.remove('d-none');
            if (providerLabel) providerLabel.textContent = 'Provider E-Wallet';
            if (providerInput) {
                providerInput.placeholder = 'GoPay, OVO, DANA, ShopeePay, dll';
                const checkedRadio = document.querySelector('input[name="edit_ewallet_radio"]:checked');
                if (checkedRadio && checkedRadio.value !== 'Lainnya') {
                    providerInput.value = checkedRadio.value;
                }
            }
        } else if (method === 'TRANSFER') {
            if (ewalletRow) ewalletRow.classList.add('d-none');
            if (providerLabel) providerLabel.textContent = 'Nama Bank Transfer';
            if (providerInput) {
                providerInput.placeholder = 'BCA, Mandiri, BRI, BNI, dll';
                if (!providerInput.value || ['GoPay','OVO','DANA','ShopeePay'].includes(providerInput.value)) {
                    providerInput.value = 'BCA';
                }
            }
        } else if (method === 'QRIS') {
            if (ewalletRow) ewalletRow.classList.add('d-none');
            if (providerLabel) providerLabel.textContent = 'Provider QRIS';
            if (providerInput) {
                providerInput.placeholder = 'BCA, Mandiri, ShopeePay, Statis, dll';
                if (!providerInput.value || ['GoPay','OVO','DANA'].includes(providerInput.value)) {
                    providerInput.value = 'BCA';
                }
            }
        }
    }
    recalcEditTransaction();
}

function onEditEwalletRadioChange(val) {
    const providerInput = document.getElementById('editProviderInput');
    if (!providerInput) return;
    if (val === 'Lainnya') {
        providerInput.value = '';
        providerInput.focus();
    } else {
        providerInput.value = val;
    }
}

function setEditQuickCash(val, btn) {
    const paidInput = document.getElementById('editPaidAmount');
    if (!paidInput) return;

    // Reset active buttons
    document.querySelectorAll('#editQuickCashGroup .btn-quick-cash').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    if (val === 'exact') {
        paidInput.value = Math.round(getEditGrandTotal());
    } else {
        paidInput.value = val;
    }
    recalcEditTransaction();
}

function onEditPaidAmountInput() {
    recalcEditTransaction();
    const paidInput = document.getElementById('editPaidAmount');
    const val = parseFloat(paidInput?.value) || 0;
    const gt = getEditGrandTotal();

    document.querySelectorAll('#editQuickCashGroup .btn-quick-cash').forEach(btn => {
        const btnClick = btn.getAttribute('onclick') || '';
        if (btnClick.includes("'exact'") && Math.round(val) === Math.round(gt)) {
            btn.classList.add('active');
        } else if (btnClick.includes(val.toString()) && val > 0) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function previewEditPaymentProof(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            const previewImg = document.getElementById('editProofPreviewImg');
            const previewBox = document.getElementById('editProofPreviewBox');
            const placeholder = document.getElementById('editProofPlaceholder');
            const delInput = document.getElementById('deletePaymentProofInput');

            if (previewImg) previewImg.src = e.target.result;
            if (previewBox) previewBox.classList.remove('d-none');
            if (placeholder) placeholder.classList.add('d-none');
            if (delInput) delInput.value = '0';
        };
        reader.readAsDataURL(file);
    }
}

function removeEditPaymentProofPhoto() {
    const input = document.getElementById('editPaymentProofInput');
    const previewImg = document.getElementById('editProofPreviewImg');
    const previewBox = document.getElementById('editProofPreviewBox');
    const placeholder = document.getElementById('editProofPlaceholder');
    const delInput = document.getElementById('deletePaymentProofInput');

    if (input) input.value = '';
    if (previewImg) previewImg.src = '';
    if (previewBox) previewBox.classList.add('d-none');
    if (placeholder) placeholder.classList.remove('d-none');
    if (delInput) delInput.value = '1';
}

document.addEventListener('DOMContentLoaded', () => {
    recalcEditTransaction();

    const methodSelect = document.getElementById('editPaymentMethodSelect');
    if (methodSelect) {
        handleEditPaymentMethodChange(methodSelect.value);
    }

    // Auto open modal if ?edit=1 in URL
    if (new URLSearchParams(window.location.search).has('edit')) {
        const modalEl = document.getElementById('editTransactionModal');
        if (modalEl) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }
});
</script>
<?php endif; ?>
