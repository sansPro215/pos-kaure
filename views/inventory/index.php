<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kelola Stok Produk</h4>
            <p class="text-muted small mb-0">Pantau ketersediaan stok seluruh menu produk. Anda dapat menambah, mengurangi, dan menghapus sisa stok secara langsung.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-success btn-sm px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#stockInModal">
                <i class="bi bi-plus-circle me-1"></i> Tambah Stok
            </button>
            <button class="btn btn-danger btn-sm px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#stockOutModal">
                <i class="bi bi-dash-circle me-1"></i> Kurangi Stok
            </button>
            <button class="btn btn-dark btn-sm px-3 py-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#stockResetModal">
                <i class="bi bi-trash3 me-1"></i> Hapus Stok (0)
            </button>
            <button class="btn btn-warning btn-sm px-3 py-2 text-dark shadow-sm" data-bs-toggle="modal" data-bs-target="#stockAdjustModal">
                <i class="bi bi-sliders me-1"></i> Opname Fisik
            </button>
            <a href="<?= url('/inventory/history') ?>" class="btn btn-outline-secondary btn-sm px-3 py-2 shadow-sm">
                <i class="bi bi-clock-history me-1"></i> Riwayat Mutasi
            </a>
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
                                    <button type="button" class="btn btn-outline-warning text-dark" title="Penyesuaian Opname Fisik" onclick="quickStockAction('Adjust', <?= $p['id'] ?>)">
                                        <i class="bi bi-sliders"></i>
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

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Jumlah Tambah (pcs) <span class="text-danger">*</span></label>
                            <input type="number" step="1" name="qty" id="stockInQty" class="form-control form-control-lg fw-bold" placeholder="0" min="1" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Biaya Beli/Modal Satuan (Rp)</label>
                            <input type="number" step="0.01" name="cost" class="form-control form-control-lg" placeholder="Opsional" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan / Supplier / Keterangan</label>
                        <input type="text" name="note" class="form-control" placeholder="Contoh: Restock pesanan supplier / Produksi baru">
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

<!-- MODAL 4: PENYESUAIAN STOK (STOCK OPNAME) -->
<div class="modal fade" id="stockAdjustModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/inventory/adjust') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold"><i class="bi bi-sliders text-warning me-2"></i>Penyesuaian Stok (Opname Fisik)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Produk <span class="text-danger">*</span></label>
                        <select name="item_id" id="stockAdjustProdId" class="form-select" required>
                            <?php foreach ($allProds as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['name']) ?> (Sistem: <?= (int)$p['stock'] ?> pcs)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Stok Fisik Sebenarnya (Hasil Hitung Riil) <span class="text-danger">*</span></label>
                        <input type="number" step="1" name="physical_stock" id="stockAdjustPhysical" class="form-control form-control-lg fw-bold" placeholder="Hasil hitung riil di kedai" min="0" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Alasan Penyesuaian <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="Contoh: Hasil stock opname berkala" required>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning text-dark btn-sm px-4">Simpan Penyesuaian</button>
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
    else if (mode === 'Adjust') selectEl = document.getElementById('stockAdjustProdId');

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
