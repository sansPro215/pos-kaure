<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Daftar Menu & Produk</h4>
            <p class="text-muted small mb-0">Kelola daftar menu dan harga produk</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= url('/products/create') ?>" class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2">
                <i class="bi bi-plus-circle"></i> Tambah Produk Baru
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-3">
        <form action="<?= url('/products') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-transparent text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Cari nama produk atau SKU..." value="<?= e($search) ?>">
                </div>
            </div>
            <div class="col-8 col-md-5">
                <select name="category_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $currentCat == $cat['id'] ? 'selected' : '' ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-4 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-wk-primary w-100">Filter</button>
                <a href="<?= url('/products') ?>" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">Foto</th>
                        <th>Kode (SKU)</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-end">Harga Jual</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td data-order="<?= strtotime($p['created_at']) ?>">
                                <div style="width: 44px; height: 44px; border-radius: 8px; overflow: hidden; background: var(--wk-primary-light);" class="d-flex align-items-center justify-content-center">
                                    <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../../public/uploads/products/' . $p['image'])): ?>
                                        <img src="<?= asset('/uploads/products/' . $p['image']) ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-cup-hot text-wk-primary"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><code class="fw-semibold text-dark"><?= e($p['sku']) ?></code></td>
                            <td>
                                <div class="fw-bold text-wk-primary"><?= e($p['name']) ?></div>
                                <small class="text-muted">Modal: <?= format_rupiah($p['cost_price']) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?= e($p['category_name'] ?? 'Tanpa Kategori') ?></span>
                            </td>
                            <td class="text-end fw-bold"><?= format_rupiah($p['selling_price']) ?></td>
                            <td class="text-end">
                                <a href="<?= url('/products/' . $p['id'] . '/edit') ?>" class="btn btn-sm btn-outline-secondary p-1 px-2" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="<?= url('/products/' . $p['id'] . '/delete') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm" 
                                            data-title="Nonaktifkan Produk?" 
                                            data-text="Produk '<?= e($p['name']) ?>' tidak akan muncul lagi di kasir POS." 
                                            title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
