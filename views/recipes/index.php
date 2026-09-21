<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Resep Produk / Bill of Materials (BOM)</h4>
            <p class="text-muted small mb-0">Atur takaran bahan baku yang otomatis berkurang saat menu terjual</p>
        </div>
        <a href="<?= url('/products') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-box-seam me-1"></i> Kelola Produk
        </a>
    </div>

    <div class="row g-3">
        <?php if (empty($products)): ?>
            <div class="col-12">
                <div class="wk-card p-5 text-center text-muted">
                    <i class="bi bi-journal-x fs-1 d-block mb-2"></i>
                    Belum ada produk bertipe RECIPE.<br>
                    Ubah tipe tracking produk menjadi <strong>RECIPE</strong> di menu Produk.
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($products as $p): ?>
                <?php 
                    $margin = (float)$p['selling_price'] - (float)$p['calculated_hpp'];
                    $marginPct = $p['selling_price'] > 0 ? round(($margin / $p['selling_price']) * 100, 1) : 0;
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="wk-card p-3 h-100 d-flex flex-column justify-content-between shadow-sm">
                        <div>
                            <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                <h6 class="fw-bold text-wk-primary mb-0"><?= e($p['name']) ?></h6>
                                <span class="badge bg-light text-dark border"><code><?= e($p['sku']) ?></code></span>
                            </div>

                            <div class="d-flex justify-content-between small text-muted mb-2">
                                <span>Harga Jual: <strong><?= format_rupiah($p['selling_price']) ?></strong></span>
                                <span>HPP Bahan: <strong class="text-danger"><?= format_rupiah($p['calculated_hpp']) ?></strong></span>
                            </div>
                            
                            <div class="p-2 rounded bg-body-tertiary mb-3 small d-flex justify-content-between">
                                <span class="text-muted">Estimasi Margin:</span>
                                <strong class="text-success"><?= format_rupiah($margin) ?> (<?= $marginPct ?>%)</strong>
                            </div>

                            <label class="form-label text-muted small fw-semibold mb-1">Komposisi Bahan Baku:</label>
                            <?php if (empty($p['recipes'])): ?>
                                <div class="alert alert-warning p-2 small mb-0">
                                    <i class="bi bi-exclamation-triangle me-1"></i> Resep belum ditentukan!
                                </div>
                            <?php else: ?>
                                <ul class="list-group list-group-flush small mb-0 border rounded">
                                    <?php foreach ($p['recipes'] as $r): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-2">
                                            <span><?= e($r['ingredient_name']) ?></span>
                                            <span class="fw-bold"><?= number_format($r['quantity'], 1) ?> <?= e($r['unit']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3 pt-2 border-top">
                            <a href="<?= url('/recipes/' . $p['id']) ?>" class="btn btn-sm btn-wk-primary w-100 py-2">
                                <i class="bi bi-pencil-square me-1"></i> Atur Komposisi Bahan
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
