<div class="container-fluid px-0" style="max-width: 800px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="fw-bold mb-1">Atur Resep BOM</h4>
            <p class="text-muted small mb-0">Menu: <strong><?= e($product['name']) ?></strong> (Harga: <?= format_rupiah($product['selling_price']) ?>)</p>
        </div>
        <a href="<?= url('/recipes') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="wk-card p-4 shadow-sm">
        <form action="<?= url('/recipes/' . $product['id'] . '/update') ?>" method="POST">
            <?= csrf_field() ?>

            <div class="alert alert-info small d-flex align-items-center gap-2 mb-4">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div>
                    Tentukan takaran bahan baku per 1 porsi menu ini. Setiap kali menu ini terjual di kasir POS, stok seluruh bahan baku di bawah akan otomatis berkurang.
                </div>
            </div>

            <div id="recipeRowsContainer" class="d-flex flex-column gap-2 mb-3">
                <?php if (empty($currentRecipes)): ?>
                    <div class="row g-2 align-items-center recipe-row">
                        <div class="col-7 col-sm-8">
                            <select name="ingredient_id[]" class="form-select" required>
                                <option value="">Pilih Bahan Baku...</option>
                                <?php foreach ($ingredients as $ing): ?>
                                    <option value="<?= $ing['id'] ?>"><?= e($ing['name']) ?> (<?= e($ing['unit']) ?>) — Rp<?= number_format($ing['average_cost'], 0) ?>/<?= e($ing['unit']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-3 col-sm-3">
                            <input type="number" step="0.01" name="quantity[]" class="form-control text-end" placeholder="Jumlah" min="0.01" required>
                        </div>
                        <div class="col-2 col-sm-1 text-center">
                            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeRecipeRow(this)">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($currentRecipes as $r): ?>
                        <div class="row g-2 align-items-center recipe-row">
                            <div class="col-7 col-sm-8">
                                <select name="ingredient_id[]" class="form-select" required>
                                    <option value="">Pilih Bahan Baku...</option>
                                    <?php foreach ($ingredients as $ing): ?>
                                        <option value="<?= $ing['id'] ?>" <?= $r['ingredient_id'] == $ing['id'] ? 'selected' : '' ?>>
                                            <?= e($ing['name']) ?> (<?= e($ing['unit']) ?>) — Rp<?= number_format($ing['average_cost'], 0) ?>/<?= e($ing['unit']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-3 col-sm-3">
                                <input type="number" step="0.01" name="quantity[]" class="form-control text-end" value="<?= (float)$r['quantity'] ?>" min="0.01" required>
                            </div>
                            <div class="col-2 col-sm-1 text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeRecipeRow(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm mb-4" onclick="addRecipeRow()">
                <i class="bi bi-plus-circle me-1"></i> Tambah Bahan Baku
            </button>

            <div class="p-3 border rounded bg-body-tertiary d-flex justify-content-between align-items-center mb-4">
                <span class="fw-semibold">Estimasi HPP Saat Ini:</span>
                <span class="fs-5 fw-bold text-danger"><?= format_rupiah($calculatedHpp) ?></span>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <a href="<?= url('/recipes') ?>" class="btn btn-outline-secondary px-4 py-2">Batal</a>
                <button type="submit" class="btn btn-wk-primary px-4 py-2">
                    <i class="bi bi-save me-1"></i> Simpan Resep
                </button>
            </div>
        </form>
    </div>
</div>

<template id="recipeRowTemplate">
    <div class="row g-2 align-items-center recipe-row">
        <div class="col-7 col-sm-8">
            <select name="ingredient_id[]" class="form-select" required>
                <option value="">Pilih Bahan Baku...</option>
                <?php foreach ($ingredients as $ing): ?>
                    <option value="<?= $ing['id'] ?>"><?= e($ing['name']) ?> (<?= e($ing['unit']) ?>) — Rp<?= number_format($ing['average_cost'], 0) ?>/<?= e($ing['unit']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-3 col-sm-3">
            <input type="number" step="0.01" name="quantity[]" class="form-control text-end" placeholder="Jumlah" min="0.01" required>
        </div>
        <div class="col-2 col-sm-1 text-center">
            <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="removeRecipeRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</template>

<script>
function addRecipeRow() {
    const tpl = document.getElementById('recipeRowTemplate');
    const container = document.getElementById('recipeRowsContainer');
    const clone = tpl.content.cloneNode(true);
    container.appendChild(clone);
}

function removeRecipeRow(btn) {
    const rows = document.querySelectorAll('.recipe-row');
    if (rows.length > 1) {
        btn.closest('.recipe-row').remove();
    } else {
        alert('Minimal harus ada satu baris bahan baku.');
    }
}
</script>
