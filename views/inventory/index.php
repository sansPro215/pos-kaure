<div class="container-fluid px-0">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0 mb-sm-1">Kelola Stok Produk</h4>
            <p class="text-muted small mb-0 d-none d-sm-block">Pantau ketersediaan stok seluruh menu produk. Anda dapat menambah, mengurangi, dan menghapus sisa stok secara langsung.</p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-nowrap flex-shrink-0">
            <button class="btn btn-success btn-sm px-2 px-sm-3 py-2 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#stockInModal" title="Tambah Stok">
                <i class="bi bi-plus-circle me-0 me-sm-1"></i><span class="d-none d-sm-inline">Tambah Stok</span>
            </button>
            <button class="btn btn-danger btn-sm px-2 px-sm-3 py-2 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#stockOutModal" title="Kurangi Stok">
                <i class="bi bi-dash-circle me-0 me-sm-1"></i><span class="d-none d-sm-inline">Kurangi Stok</span>
            </button>
            <button class="btn btn-dark btn-sm px-2 px-sm-3 py-2 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#stockResetModal" title="Hapus Stok (0)">
                <i class="bi bi-trash3 me-0 me-sm-1"></i><span class="d-none d-sm-inline">Hapus Stok (0)</span>
            </button>
        </div>
    </div>

    <!-- Product Stock Table -->
    <?php $allProds = $products ?? []; ?>
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable" data-order='[[0, "asc"]]'>
                <thead class="table-light">
                    <tr>
                        <th>SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-end">Stok Sistem</th>
                        <th class="text-end">Batas Alert</th>
                        <th class="text-center">Status Stok</th>
                        <th class="text-end" style="min-width: 190px;">Aksi Cepat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allProds as $p): ?>
                        <?php 
                            $currStock = (float)$p['stock'];
                            $minStock = (float)$p['minimum_stock'];
                            $isLow = ($currStock <= $minStock);
                            $isZero = ($currStock <= 0);
                        ?>
                        <tr>
                            <td>
                                <code class="fw-bold"><?= e($p['sku']) ?></code>
                            </td>
                            <td>
                                <div class="fw-bold text-wk-primary"><?= e($p['name']) ?></div>
                                <small class="text-muted">Katalog Produk Menu</small>
                            </td>
                            <td><?= e($p['category_name'] ?? 'Tanpa Kategori') ?></td>
                            <td class="text-end fw-semibold"><?= format_rupiah($p['selling_price']) ?></td>
                            <td class="text-end fw-bold">
                                <span class="<?= $isZero ? 'text-danger' : ($isLow ? 'text-warning' : 'text-success') ?>">
                                    <?= (int)$currStock ?> pcs
                                </span>
                            </td>
                            <td class="text-end text-muted"><?= (int)$minStock ?> pcs</td>
                            <td class="text-center">
                                <?php if ($isZero): ?>
                                    <span class="badge bg-danger">Habis</span>
                                <?php elseif ($isLow): ?>
                                    <span class="badge bg-warning text-dark">Menipis</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Aman</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-success" title="Tambah Stok (+)" onclick="quickStockAction('In', <?= $p['id'] ?>)">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" title="Kurangi Stok (-)" onclick="quickStockAction('Out', <?= $p['id'] ?>)">
                                        <i class="bi bi-dash-lg"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-dark" title="Hapus / Kosongkan Stok ke 0" onclick="quickStockAction('Reset', <?= $p['id'] ?>)">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL 1: TAMBAH STOK (STOCK IN) -->
<div class="modal fade" id="stockInModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/inventory/stock-in') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-success me-2"></i>Tambah Stok Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="item_id" id="stockInProdId" class="form-select" required>
                            <?php foreach ($allProds as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?> (Sisa: <?= (int)$p['stock'] ?> pcs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jumlah Tambah (pcs) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="qty" id="stockInQty" class="form-control form-control-lg fw-bold" placeholder="0" min="1" required>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">Simpan Stok Masuk</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: KURANGI STOK (STOCK OUT / WASTE) -->
<div class="modal fade" id="stockOutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/inventory/stock-out') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-dash-circle text-danger me-2"></i>Kurangi Stok Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="item_id" id="stockOutProdId" class="form-select" required>
                            <?php foreach ($allProds as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?> (Sisa: <?= (int)$p['stock'] ?> pcs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Jumlah Pengurangan (pcs) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="qty" id="stockOutQty" class="form-control form-control-lg fw-bold" placeholder="0" min="1" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Pengurangan <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="Barang rusak / pecah / kemasan cacat">Barang rusak / pecah / kemasan cacat</option>
                            <option value="Kadaluarsa / expired">Kadaluarsa / expired</option>
                            <option value="Pemakaian internal / tester / staf">Pemakaian internal / tester / staf</option>
                            <option value="Selisih barang hilang / selisih hitung">Selisih barang hilang / selisih hitung</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">Simpan Pengurangan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 3: HAPUS / KOSONGKAN STOK PRODUK (RESET KE 0) -->
<div class="modal fade" id="stockResetModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/inventory/reset') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3 bg-dark text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-trash3 text-danger me-2"></i>Hapus / Kosongkan Stok Produk</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><strong>Perhatian:</strong> Tindakan ini akan mengosongkan seluruh sisa stok produk terpilih menjadi <strong>0 pcs</strong>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Produk Yang Ingin Dikosongkan <span class="text-danger">*</span></label>
                        <select name="product_id" id="stockResetProdId" class="form-select" required>
                            <?php foreach ($allProds as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?> (Sisa: <?= (int)$p['stock'] ?> pcs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Penghapusan / Pengosongan Stok <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="Hapus sisa stok produk (kedaluwarsa/buang)">Hapus sisa stok produk (kedaluwarsa/buang)</option>
                            <option value="Kosongkan stok untuk opname ulang">Kosongkan stok untuk opname ulang</option>
                            <option value="Barang rusak total / batch ditarik">Barang rusak total / batch ditarik</option>
                            <option value="Reset stok sistem menjadi 0">Reset stok sistem menjadi 0</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger btn-sm px-4">Ya, Kosongkan Stok ke 0</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function quickStockAction(mode, itemId) {
    let selectEl = null;
    if (mode === 'In') selectEl = document.getElementById('stockInProdId');
    else if (mode === 'Out') selectEl = document.getElementById('stockOutProdId');
    else if (mode === 'Reset') selectEl = document.getElementById('stockResetProdId');

    if (selectEl && itemId) {
        selectEl.value = itemId;
    }

    const modalEl = document.getElementById(`stock${mode}Modal`);
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>
