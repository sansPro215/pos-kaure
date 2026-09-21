<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Tambah Produk Baru</h4>
            <p class="text-muted small mb-0">Isi data produk menu dan tentukan harga jual & modal</p>
        </div>
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="wk-card p-4 shadow-sm">
        <form action="<?= url('/products/create') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Kategori Menu <span class="text-danger">*</span></label>
                    <select name="category_id" id="categorySelect" class="form-select" required autofocus>
                        <option value="">Pilih Kategori...</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" 
                                    data-name="<?= e($c['name']) ?>"
                                    data-sku="<?= e($c['suggested_sku'] ?? '') ?>">
                                <?= e($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Kode Produk (SKU) <span class="text-muted">(Otomatis)</span></label>
                    <input type="text" name="sku" id="skuInput" class="form-control bg-light font-monospace" value="<?= e($suggestedSku ?? 'PD-UMUM-1') ?>" readonly required>
                    <div class="form-text small">Format otomatis: <code>PD-[namakategori]-id</code></div>
                </div>

                <div class="col-12">
                    <label class="form-label fw-semibold small">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="Contoh: Kopi Susu Aren Spesial" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Harga Jual (Rp) <span class="text-danger">*</span></label>
                    <input type="number" name="selling_price" class="form-control" placeholder="Contoh: 18000" min="0" required>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold small">Harga Modal / HPP Satuan (Rp)</label>
                    <input type="number" name="cost_price" class="form-control" placeholder="Contoh: 8000" min="0" value="0">
                </div>


                <div class="col-12">
                    <label class="form-label fw-semibold small">Foto Produk (Opsional)</label>
                    <input type="file" name="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                    <div class="form-text small">Maksimal 2 MB (JPG, PNG, WEBP)</div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top d-flex justify-content-end gap-2">
                <a href="<?= url('/products') ?>" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                <button type="submit" class="btn btn-wk-primary px-4 py-2">
                    <i class="bi bi-save me-1"></i> Simpan Produk
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const categorySelect = document.getElementById('categorySelect');
    const skuInput = document.getElementById('skuInput');

    categorySelect.addEventListener('change', () => {
        const selectedOption = categorySelect.options[categorySelect.selectedIndex];
        const suggestedSku = selectedOption.getAttribute('data-sku');
        if (suggestedSku) {
            skuInput.value = suggestedSku;
        } else {
            let catName = selectedOption.getAttribute('data-name') || 'UMUM';
            catName = catName.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
            if (!catName) catName = 'UMUM';
            skuInput.value = `PD-${catName}-1`;
        }
    });
});
</script>
