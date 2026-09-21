<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Edit Produk</h4>
            <p class="text-muted small mb-0">Ubah data menu: <?= e($product['name']) ?></p>
        </div>
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="wk-card p-4 shadow-sm">
        <form action="<?= url('/products/' . $product['id'] . '/update') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Kategori Menu <span class="text-danger">*</span></label>
                    <select name="category_id" id="editCategorySelect" class="form-select" required>
                        <option value="">Pilih Kategori...</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" data-name="<?= e($c['name']) ?>" <?= $product['category_id'] == $c['id'] ? 'selected' : '' ?>>
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Kode Produk (SKU) <span class="text-muted">(Format PD-[kategori]-id)</span></label>
                    <input type="text" name="sku" id="editSkuInput" class="form-control bg-light font-monospace" value="<?= e($product['sku']) ?>" required>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($product['name']) ?>" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="selling_price" class="form-control" value="<?= (int)$product['selling_price'] ?>" min="0" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Harga Modal / HPP Satuan (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" value="<?= (int)$product['cost_price'] ?>" min="0">
                </div>


                <div class="col-12">
                    <label class="form-label fw-semibold small">Foto Produk</label>
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($product['image']) && file_exists(__DIR__ . '/../../public/uploads/products/' . $product['image'])): ?>
                            <img src="<?= asset('/uploads/products/' . $product['image']) ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text small">Biarkan kosong jika tidak ingin mengubah foto.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <a href="<?= url('/products') ?>" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                <button type="submit" class="btn btn-wk-primary px-4 py-2">
                    <i class="bi bi-save me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.getElementById('editCategorySelect');
    const skuInput = document.getElementById('editSkuInput');
    const productId = '<?= $product['id'] ?>';

    categorySelect.addEventListener('change', () => {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        let catName = selectedOption.getAttribute('data-name') || 'UMUM';
        catName = catName.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
        if (!catName) catName = 'UMUM';
        skuInput.value = `PD-${catName}-${productId}`;
    });
});
</script>
