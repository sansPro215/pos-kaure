<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Bahan Baku (Ingredients)</h4>
            <p class="text-muted small mb-0">Kelola stok dasar bahan baku untuk resep BOM produk menu</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/inventory') ?>" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left-right me-1"></i> Stock In / Out
            </a>
            <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
                <i class="bi bi-plus-circle"></i> Tambah Bahan Baku
            </button>
        </div>
    </div>

    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Bahan Baku</th>
                        <th class="text-center">Satuan Unit</th>
                        <th class="text-end">Stok Saat Ini</th>
                        <th class="text-end">Batas Minimum</th>
                        <th class="text-end">Harga Rata-rata (WAC)</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ingredients as $idx => $ing): ?>
                        <?php $isLow = ($ing['current_stock'] <= $ing['minimum_stock']); ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td class="fw-bold text-wk-primary"><?= e($ing['name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary"><?= e($ing['unit']) ?></span>
                            </td>
                            <td class="text-end fw-bold">
                                <span class="<?= $isLow ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($ing['current_stock'], 2, ',', '.') ?> <?= e($ing['unit']) ?>
                                </span>
                            </td>
                            <td class="text-end text-muted">
                                <?= number_format($ing['minimum_stock'], 2, ',', '.') ?> <?= e($ing['unit']) ?>
                            </td>
                            <td class="text-end fw-semibold">
                                <?= format_rupiah($ing['average_cost']) ?> / <?= e($ing['unit']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($ing['status'] === 'ACTIVE'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editIngModal<?= $ing['id'] ?>" 
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="<?= url('/ingredients/' . $ing['id'] . '/delete') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm"
                                            data-title="Hapus Bahan Baku?"
                                            data-text="Bahan baku '<?= e($ing['name']) ?>' akan dinonaktifkan."
                                            title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editIngModal<?= $ing['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/ingredients/' . $ing['id'] . '/update') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold">Edit Bahan Baku</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nama Bahan</label>
                                                <input type="text" name="name" class="form-control" value="<?= e($ing['name']) ?>" required>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Satuan Unit</label>
                                                    <select name="unit" class="form-select" required>
                                                        <option value="gram" <?= $ing['unit'] === 'gram' ? 'selected' : '' ?>>gram</option>
                                                        <option value="ml" <?= $ing['unit'] === 'ml' ? 'selected' : '' ?>>ml</option>
                                                        <option value="pcs" <?= $ing['unit'] === 'pcs' ? 'selected' : '' ?>>pcs</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Batas Minimum Alert</label>
                                                    <input type="number" step="0.01" name="minimum_stock" class="form-control" value="<?= (float)$ing['minimum_stock'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Status</label>
                                                <select name="status" class="form-select">
                                                    <option value="ACTIVE" <?= $ing['status'] === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
                                                    <option value="INACTIVE" <?= $ing['status'] === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top p-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-wk-primary btn-sm px-3">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addIngredientModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/ingredients/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Tambah Bahan Baku Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Bahan Baku <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Biji Kopi Robusta" required autofocus>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Satuan Unit <span class="text-danger">*</span></label>
                            <select name="unit" class="form-select" required>
                                <option value="gram">gram</option>
                                <option value="ml">ml</option>
                                <option value="pcs">pcs</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Stok Awal</label>
                            <input type="number" step="0.01" name="current_stock" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Batas Minimum Alert</label>
                            <input type="number" step="0.01" name="minimum_stock" class="form-control" value="100" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Harga Beli Rata-rata Awal (Rp)</label>
                            <input type="number" step="0.01" name="average_cost" class="form-control" value="0" min="0">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" class="form-select">
                            <option value="ACTIVE" selected>Aktif</option>
                            <option value="INACTIVE">Nonaktif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Tambah Bahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
