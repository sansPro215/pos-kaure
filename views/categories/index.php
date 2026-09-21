<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kategori Produk</h4>
            <p class="text-muted small mb-0">Kelola kelompok kategori menu Warung Kaure</p>
        </div>
        <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Tambah Kategori
        </button>
    </div>

    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Kategori</th>
                        <th class="text-center">Jumlah Produk</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $idx => $cat): ?>
                        <tr>
                            <td data-order="<?= strtotime($cat['created_at']) ?>"><?= $idx + 1 ?></td>
                            <td class="fw-bold text-wk-primary"><?= e($cat['name']) ?></td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                    <?= (int)$cat['product_count'] ?> Produk
                                </span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editCategoryModal<?= $cat['id'] ?>"
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <form action="<?= url('/categories/' . $cat['id'] . '/delete') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm"
                                            data-title="Hapus Kategori?"
                                            data-text="Kategori '<?= e($cat['name']) ?>' akan dinonaktifkan."
                                            title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/categories/' . $cat['id'] . '/update') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold">Edit Kategori</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="mb-3">
                                                 <label class="form-label fw-semibold small">Nama Kategori</label>
                                                 <input type="text" name="name" class="form-control" value="<?= e($cat['name']) ?>" required>
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
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/categories/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Tambah Kategori Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Kategori</label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Coffee, Dessert" required autofocus>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Tambah Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>
