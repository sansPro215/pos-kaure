<!-- Hidden CSRF and Global Endpoint Variables -->
<input type="hidden" name="_token" value="<?= csrf_token() ?>">
<script>
    window.posCheckoutUrl = '<?= url('/pos/checkout') ?>';
    window.posHoldUrl = '<?= url('/pos/hold') ?>';
    window.posResumeUrl = '<?= url('/pos/resume') ?>';
</script>

<div class="container-fluid px-3 pt-2">
    <div class="row g-3">
        <!-- LEFT / MAIN: PRODUCT LIST (Mobile: full width, Desktop: 68%) -->
        <div class="col-12 col-lg-8 col-xl-8">
            <!-- Search & Actions Top Bar -->
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="input-group">
                    <span class="input-group-text bg-wk-surface border-end-0 text-muted">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" id="posSearchInput" class="form-control border-start-0 ps-0 bg-wk-surface" placeholder="Cari nama produk atau SKU...">
                </div>

                <!-- Hold Orders Badge Button -->
                <?php if (!empty($heldList)): ?>
                    <button class="btn btn-warning text-nowrap d-flex align-items-center gap-1 shadow-sm" type="button" data-bs-toggle="modal" data-bs-target="#heldOrdersModal">
                        <i class="bi bi-pause-circle"></i>
                        <span>Hold (<?= count($heldList) ?>)</span>
                    </button>
                <?php endif; ?>
            </div>

            <?php 
                $posLayout = $settings['pos_layout'] ?? 'grid_large'; 
                $categoryCounts = [];
                foreach ($products as $pr) {
                    $cid = $pr['category_id'] ?? 0;
                    $categoryCounts[$cid] = ($categoryCounts[$cid] ?? 0) + 1;
                }
            ?>

            <?php if ($posLayout === 'table_list'): ?>
                <!-- LAYOUT 2: TABEL PRODUK MEMANJANG KE SAMPING (KOLOM TIDAK MENUMPUK KE BAWAH) -->
                <!-- Category Pills Horizontal Scroll -->
                <div class="pos-categories-scroll mb-3">
                    <div class="pos-cat-pill active" data-cat-id="all">Semua (<?= count($products) ?>)</div>
                    <?php foreach ($categories as $cat): ?>
                        <div class="pos-cat-pill" data-cat-id="<?= $cat['id'] ?>"><?= e($cat['name']) ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="wk-card shadow-sm border overflow-hidden mb-3 p-0 bg-white">
                    <div class="table-responsive" style="max-height: calc(100vh - 220px); overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="modernPosTable">
                            <thead class="table-light sticky-top" style="border-bottom: 2px solid #e2e8f0; z-index: 5;">
                                <tr class="text-secondary small fw-bold text-uppercase" style="font-size: 0.76rem; letter-spacing: 0.5px;">
                                    <th style="width: 55px;" class="ps-3 py-3 text-center">Foto</th>
                                    <th style="width: 120px;" class="py-3">SKU</th>
                                    <th class="py-3">Nama Produk / Barang</th>
                                    <th style="width: 130px;" class="py-3">Kategori</th>
                                    <th class="text-end py-3" style="width: 140px;">Harga Satuan</th>
                                    <th class="text-center pe-3 py-3" style="width: 110px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="modernPosTbody">
                                <?php foreach ($products as $p): 
                                    $catName = 'Tanpa Kategori';
                                    foreach ($categories as $c) {
                                        if ($c['id'] == $p['category_id']) {
                                            $catName = $c['name'];
                                            break;
                                        }
                                    }
                                ?>
                                    <tr class="pos-product-item modern-product-row"
                                        data-category-id="<?= $p['category_id'] ?>" 
                                        data-name="<?= e($p['name']) ?>" 
                                        data-sku="<?= e($p['sku']) ?>"
                                        data-id="<?= $p['id'] ?>"
                                        data-price="<?= $p['selling_price'] ?>"
                                        style="cursor: pointer;">
                                        <td class="ps-3 py-2 text-center">
                                            <div class="rounded-2 overflow-hidden border bg-light d-inline-flex align-items-center justify-content-center shadow-xs" style="width: 40px; height: 40px;">
                                                <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../../public/uploads/products/' . $p['image'])): ?>
                                                    <img src="<?= asset('/uploads/products/' . $p['image']) ?>" alt="<?= e($p['name']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-box-seam text-secondary fs-6 opacity-50"></i>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="py-2">
                                            <code class="small text-muted font-monospace"><?= e($p['sku'] ?: '-') ?></code>
                                        </td>
                                        <td class="py-2">
                                            <div class="fw-bold text-dark" style="font-size: 0.92rem;"><?= e($p['name']) ?></div>
                                        </td>
                                        <td class="py-2">
                                            <span class="badge bg-light text-dark border rounded-pill px-2 py-1 small"><?= e($catName) ?></span>
                                        </td>
                                        <td class="text-end py-2 fw-bold text-dark font-monospace" style="font-size: 0.92rem;">
                                            <?= format_rupiah($p['selling_price']) ?>
                                        </td>
                                        <td class="text-center pe-3 py-2">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-3 d-inline-flex align-items-center gap-1">
                                                <i class="bi bi-plus-lg"></i> <span>Tambah</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- LAYOUT 1: GRID PRODUK BESAR (DEFAULT) -->
                <!-- Category Pills Horizontal Scroll -->
                <div class="pos-categories-scroll mb-3">
                    <div class="pos-cat-pill active" data-cat-id="all">Semua (<?= count($products) ?>)</div>
                    <?php foreach ($categories as $cat): ?>
                        <div class="pos-cat-pill" data-cat-id="<?= $cat['id'] ?>"><?= e($cat['name']) ?></div>
                    <?php endforeach; ?>
                </div>

                <!-- Product Grid: 2 columns on Mobile, 3-4 on Tablet/Desktop -->
                <div class="pos-grid-scroll" style="max-height: calc(100vh - 220px); overflow-y: auto; overflow-x: hidden; padding-bottom: 2rem; padding-right: 4px;">
                    <div class="row g-2 g-sm-3" id="posProductGrid">
                        <?php foreach ($products as $p): 
                            $catName = 'Tanpa Kategori';
                            foreach ($categories as $c) {
                                if ($c['id'] == $p['category_id']) {
                                    $catName = $c['name'];
                                    break;
                                }
                            }
                        ?>
                            <div class="col-6 col-sm-4 col-md-3 col-xl-3 pos-product-item" 
                                 data-category-id="<?= $p['category_id'] ?>" 
                                 data-name="<?= e($p['name']) ?>" 
                                 data-sku="<?= e($p['sku']) ?>"
                                 data-id="<?= $p['id'] ?>"
                                 data-price="<?= $p['selling_price'] ?>"
                                 style="cursor: pointer;">
                                <div class="product-card h-100 shadow-sm border rounded-3 overflow-hidden"
                                     data-id="<?= $p['id'] ?>"
                                     data-name="<?= e($p['name']) ?>"
                                     data-price="<?= $p['selling_price'] ?>">
                                    <div class="product-img-wrapper">
                                        <?php if (!empty($p['image']) && file_exists(__DIR__ . '/../../public/uploads/products/' . $p['image'])): ?>
                                            <img src="<?= asset('/uploads/products/' . $p['image']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
                                        <?php else: ?>
                                            <i class="bi bi-cup-hot text-wk-primary opacity-50 fs-1"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-2 p-sm-3 d-flex flex-column justify-content-between flex-grow-1">
                                        <div>
                                            <div class="text-muted text-truncate mb-1" style="font-size: 0.72rem; letter-spacing: 0.2px;"><?= e($catName) ?></div>
                                            <div class="fw-semibold text-truncate small mb-1" title="<?= e($p['name']) ?>" style="font-size: 0.88rem; line-height: 1.3;">
                                                <?= e($p['name']) ?>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                                            <div class="fw-bold text-wk-primary small font-monospace" style="font-size: 0.9rem;">
                                                <?= format_rupiah($p['selling_price']) ?>
                                            </div>
                                            <span class="badge bg-light text-wk-primary border rounded-pill px-2 py-1 shadow-xs" style="font-size: 0.72rem;">
                                                <i class="bi bi-plus-lg"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- RIGHT: DESKTOP CART SIDEBAR (Desktop Only: 32%) -->
        <div class="col-lg-4 col-xl-4 d-none d-lg-block">
            <div class="wk-card h-100 d-flex flex-column p-3 sticky-top" style="top: 75px; max-height: calc(100vh - 90px); z-index: 20;">
                <div class="d-flex align-items-center justify-content-between pb-2 border-bottom">
                    <h6 class="fw-bold m-0"><i class="bi bi-cart3 me-1"></i> Keranjang Penjualan</h6>
                    <button class="btn btn-sm btn-link text-danger text-decoration-none p-0 small" onclick="clearCart()">Kosongkan</button>
                </div>

                <?php if (is_owner()): ?>
                    <div class="p-2 my-2 rounded border owner-cashier-box">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label class="form-label small fw-bold mb-0 d-flex align-items-center gap-1 owner-cashier-label">
                                <i class="bi bi-person-badge text-wk-primary"></i> Kasir Bertugas:
                            </label>
                            <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.68rem;">Mode Owner</span>
                        </div>
                        <?php if (!empty($cashiers)): ?>
                            <select id="posCartCashierSelect" name="selected_cashier_id" class="form-select form-select-sm fw-semibold owner-cashier-picker">
                                <?php foreach ($cashiers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i> Belum ada akun Kasir aktif.</div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Desktop Cart Item List Scrollable -->
                <div class="flex-grow-1 overflow-y-auto my-2 pe-1" id="desktopCartItems" style="max-height: 45vh;">
                    <!-- Rendered via pos.js -->
                </div>

                <!-- Summary Section -->
                <div class="pt-2 border-top">
                    <div class="d-flex justify-content-between text-muted small mb-1">
                        <span>Subtotal</span>
                        <span id="desktopSubtotal">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span>Diskon</span>
                        <span class="text-danger" id="desktopDiscount">Rp0</span>
                    </div>
                    <div class="d-flex justify-content-between fw-bold fs-5 mb-3 border-top pt-2">
                        <span>Total</span>
                        <span class="text-wk-primary" id="desktopGrandTotal">Rp0</span>
                    </div>

                    <!-- Cart Actions -->
                    <div class="d-flex gap-2 mb-2">
                        <button class="btn btn-sm btn-outline-secondary flex-grow-1 py-2" type="button" data-bs-toggle="modal" data-bs-target="#discountModal">
                            <i class="bi bi-percent"></i> Diskon
                        </button>
                        <button id="desktopHoldBtn" class="btn btn-sm btn-outline-warning flex-grow-1 py-2" type="button" onclick="holdCurrentCart()" disabled>
                            <i class="bi bi-pause"></i> Hold
                        </button>
                    </div>

                    <button id="desktopPayBtn" class="btn btn-wk-primary w-100 py-3 fw-bold fs-6 shadow-sm" type="button" onclick="openCheckoutModal()" disabled>
                        BAYAR Rp0
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- STICKY BOTTOM CART BAR (Mobile Only) -->
<div id="posMobileBar" class="pos-mobile-cart-bar d-lg-none d-none">
    <div class="d-flex align-items-center justify-content-between gap-2">
        <div>
            <div class="text-muted small" id="mobileCartCount">0 Item</div>
            <div class="fw-bold text-wk-primary fs-5" id="mobileCartTotal">Rp0</div>
        </div>
        <button class="btn btn-wk-primary px-4 py-2 fw-semibold d-flex align-items-center gap-2" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart" style="height: 48px; border-radius: 10px;">
            <span>Lihat Keranjang</span>
            <i class="bi bi-chevron-up"></i>
        </button>
    </div>
</div>

<!-- BOTTOM OFFCANVAS CART (Mobile Only) -->
<div class="offcanvas offcanvas-bottom d-lg-none" tabindex="-1" id="offcanvasCart" aria-labelledby="offcanvasCartLabel" style="height: 80vh; border-top-left-radius: 16px; border-top-right-radius: 16px;">
    <div class="offcanvas-header border-bottom py-3">
        <h5 class="offcanvas-title fw-bold" id="offcanvasCartLabel"><i class="bi bi-cart3 me-2"></i>Keranjang</h5>
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-link text-danger text-decoration-none" onclick="clearCart()">Kosongkan</button>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
    </div>
    <div class="offcanvas-body d-flex flex-column justify-content-between p-3">
        <?php if (is_owner()): ?>
            <div class="p-2 mb-2 rounded border owner-cashier-box">
                <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small fw-bold mb-0 d-flex align-items-center gap-1 owner-cashier-label">
                        <i class="bi bi-person-badge text-wk-primary"></i> Kasir Bertugas:
                    </label>
                    <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.68rem;">Mode Owner</span>
                </div>
                <?php if (!empty($cashiers)): ?>
                    <select id="posMobileCashierSelect" name="selected_cashier_id_mobile" class="form-select form-select-sm fw-semibold owner-cashier-picker">
                        <?php foreach ($cashiers as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= e($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="text-danger small"><i class="bi bi-exclamation-triangle me-1"></i> Belum ada akun Kasir aktif.</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Item List -->
        <div class="flex-grow-1 overflow-y-auto pe-1" id="mobileCartItems">
            <!-- Rendered via pos.js -->
        </div>

        <!-- Summary & Actions -->
        <div class="pt-3 border-top mt-2">
            <div class="d-flex justify-content-between text-muted small mb-1">
                <span>Subtotal</span>
                <span id="offcanvasSubtotal">Rp0</span>
            </div>
            <div class="d-flex justify-content-between text-muted small mb-2">
                <span>Diskon</span>
                <span class="text-danger" id="offcanvasDiscount">Rp0</span>
            </div>
            <div class="d-flex justify-content-between fw-bold fs-5 mb-3 border-top pt-2">
                <span>Total</span>
                <span class="text-wk-primary" id="offcanvasGrandTotal">Rp0</span>
            </div>

            <div class="d-flex gap-2 mb-2">
                <button class="btn btn-outline-secondary flex-grow-1 py-2" type="button" data-bs-toggle="modal" data-bs-target="#discountModal">
                    <i class="bi bi-percent"></i> Tambah Diskon
                </button>
                <button id="offcanvasHoldBtn" class="btn btn-outline-warning flex-grow-1 py-2" type="button" onclick="holdCurrentCart()" disabled>
                    <i class="bi bi-pause"></i> Hold
                </button>
            </div>

            <button id="offcanvasPayBtn" class="btn btn-wk-primary w-100 py-3 fw-bold fs-6 shadow-sm" type="button" onclick="openCheckoutModal()" disabled style="height: 52px; border-radius: 12px;">
                BAYAR Rp0
            </button>
        </div>
    </div>
</div>

<!-- CHECKOUT MODAL -->
<div class="modal fade" id="checkoutModal" tabindex="-1" aria-labelledby="checkoutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold" id="checkoutModalLabel"><i class="bi bi-credit-card me-2"></i>Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-sm-4">
                <!-- Total Display -->
                <div class="text-center p-3 mb-3 rounded-3" style="background-color: var(--wk-primary-light);">
                    <span class="text-muted small d-block">Total Tagihan:</span>
                    <h2 class="fw-bold text-wk-primary mb-0" id="checkoutModalTotal">Rp0</h2>
                </div>

                <!-- Payment Method Selection Grid -->
                <label class="form-label fw-semibold small mb-2">Pilih Metode Pembayaran:</label>
                <div class="row g-2 mb-3">
                    <div class="col-6 col-sm-3">
                        <input type="radio" class="btn-check" name="pos_payment_method" id="methodCash" value="CASH" checked autocomplete="off">
                        <label class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center gap-1 h-100" for="methodCash">
                            <i class="bi bi-cash-stack fs-4 text-success"></i>
                            <span class="fw-bold small">Tunai (Cash)</span>
                        </label>
                    </div>
                    <div class="col-6 col-sm-3">
                        <input type="radio" class="btn-check" name="pos_payment_method" id="methodQris" value="QRIS" autocomplete="off">
                        <label class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center gap-1 h-100" for="methodQris">
                            <i class="bi bi-qr-code-scan fs-4 text-primary"></i>
                            <span class="fw-bold small">QRIS</span>
                        </label>
                    </div>
                    <div class="col-6 col-sm-3">
                        <input type="radio" class="btn-check" name="pos_payment_method" id="methodTransfer" value="TRANSFER" autocomplete="off">
                        <label class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center gap-1 h-100" for="methodTransfer">
                            <i class="bi bi-bank fs-4 text-info"></i>
                            <span class="fw-bold small">Transfer</span>
                        </label>
                    </div>
                    <div class="col-6 col-sm-3">
                        <input type="radio" class="btn-check" name="pos_payment_method" id="methodEwallet" value="EWALLET" autocomplete="off">
                        <label class="btn btn-outline-secondary w-100 py-2 d-flex flex-column align-items-center gap-1 h-100" for="methodEwallet">
                            <i class="bi bi-phone fs-4 text-warning"></i>
                            <span class="fw-bold small">E-Wallet</span>
                        </label>
                    </div>
                </div>

                <!-- CASH PAYMENT SECTION -->
                <div id="cashPaymentSection">
                    <div class="mb-3">
                        <label for="receivedCashInput" class="form-label fw-semibold small">Uang Diterima (Rp):</label>
                        <input type="text" inputmode="numeric" id="receivedCashInput" class="form-control form-control-lg fw-bold text-end" placeholder="0" autocomplete="off">
                    </div>

                    <!-- Quick Cash Preset Buttons -->
                    <label class="form-label text-muted small mb-1">Pilihan Nominal Cepat:</label>
                    <div class="row g-2 mb-3" id="quickCashBtnGroup">
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="exact">Uang Pas</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="10000">10.000</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="20000">20.000</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="50000">50.000</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="100000">100.000</button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-secondary w-100 py-2 btn-quick-cash fw-semibold" data-val="200000">200.000</button>
                        </div>
                    </div>

                    <!-- Change Display -->
                    <div class="d-flex align-items-center justify-content-between p-3 border rounded-3 bg-body-tertiary">
                        <span class="fw-semibold">Kembalian:</span>
                        <span id="changeAmountDisplay" class="fw-bold text-success fs-5">Rp0</span>
                    </div>
                </div>

                <!-- CASHLESS SECTION (QRIS, Transfer, E-Wallet) -->
                <div id="cashlessPaymentSection" class="d-none">
                    <!-- E-Wallet Provider Selection (Shown only when E-Wallet selected) -->
                    <div id="ewalletProviderRow" class="mb-3 d-none p-3 border rounded bg-body-tertiary">
                        <label class="form-label small fw-semibold mb-2 d-block">Pilih Provider E-Wallet:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ewallet_provider" id="ewGopay" value="GoPay" checked>
                                <label class="form-check-label small fw-semibold" for="ewGopay">GoPay</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ewallet_provider" id="ewOvo" value="OVO">
                                <label class="form-check-label small fw-semibold" for="ewOvo">OVO</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ewallet_provider" id="ewDana" value="DANA">
                                <label class="form-check-label small fw-semibold" for="ewDana">DANA</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ewallet_provider" id="ewShopee" value="ShopeePay">
                                <label class="form-check-label small fw-semibold" for="ewShopee">ShopeePay</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="ewallet_provider" id="ewOther" value="Lainnya">
                                <label class="form-check-label small fw-semibold" for="ewOther">Lainnya</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="referenceNumberInput" class="form-label fw-semibold small">Nomor Referensi / ID Transaksi (Opsional):</label>
                        <input type="text" id="referenceNumberInput" class="form-control" placeholder="Contoh: REF12345678">
                        <div class="form-text small">Nomor referensi atau kode transaksi digital dari aplikasi pelanggan.</div>
                    </div>

                    <!-- Payment Proof Photo Capture & Upload (Optional) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold small d-flex align-items-center justify-content-between mb-1">
                            <span><i class="bi bi-camera text-primary me-1"></i> Foto Bukti Pembayaran Non-Tunai</span>
                            <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.72rem;">Opsional</span>
                        </label>

                        <input type="file" id="posPaymentProofInput" accept="image/jpeg,image/png,image/webp" capture="environment" class="d-none">

                        <div id="proofUploadPlaceholder" class="p-3 border border-dashed rounded-3 text-center bg-body-tertiary">
                            <button type="button" class="btn btn-sm btn-outline-primary py-2 px-3 fw-semibold d-inline-flex align-items-center gap-2" onclick="document.getElementById('posPaymentProofInput').click()">
                                <i class="bi bi-camera fs-5"></i>
                                <span>Ambil Foto / Pilih Gambar</span>
                            </button>
                            <div class="text-muted small mt-2" style="font-size: 0.75rem;">Mendukung kamera HP/webcam atau upload screenshot transfer.</div>
                        </div>

                        <div id="proofUploadPreviewBox" class="d-none mt-2 p-2 border rounded-3 bg-body-secondary text-center position-relative">
                            <img id="proofUploadPreviewImg" src="" alt="Bukti Pembayaran" style="max-height: 180px; max-width: 100%; object-fit: contain; border-radius: 6px;">
                            <div class="mt-2 d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small" onclick="document.getElementById('posPaymentProofInput').click()">
                                    <i class="bi bi-arrow-repeat me-1"></i>Ganti Foto
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2 small" onclick="clearPaymentProofPhoto()">
                                    <i class="bi bi-trash me-1"></i>Hapus Foto
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-outline-secondary px-3 py-2" data-bs-dismiss="modal">Batal</button>
                <button type="button" id="submitCheckoutBtn" class="btn btn-wk-primary flex-grow-1 py-2 fw-semibold fs-6">
                    <i class="bi bi-check2-circle me-1"></i> Selesaikan Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<!-- DISCOUNT MODAL -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold" id="discountModalLabel"><i class="bi bi-percent me-2"></i>Atur Diskon Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tipe Diskon:</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discount_type_radio" id="discPercent" value="PERCENT" checked>
                            <label class="form-check-label" for="discPercent">Persentase (%)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discount_type_radio" id="discFixed" value="FIXED">
                            <label class="form-check-label" for="discFixed">Nominal Rupiah (Rp)</label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="discountInputVal" class="form-label fw-semibold small">Nilai Diskon:</label>
                    <input type="number" id="discountInputVal" class="form-control form-control-lg" placeholder="Contoh: 10 atau 5000" min="0">
                    <div class="form-text small">Maksimal diskon kasir: <?= (float)$settings['cashier_max_discount_percent'] ?>%</div>
                </div>
            </div>
            <div class="modal-footer border-top p-3">
                <button type="button" class="btn btn-outline-danger btn-sm me-auto" id="removeDiscountBtn">Hapus Diskon</button>
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-wk-primary btn-sm px-3" id="applyDiscountBtn">Terapkan Diskon</button>
            </div>
        </div>
    </div>
</div>

<!-- HELD ORDERS MODAL -->
<?php if (!empty($heldList)): ?>
<div class="modal fade" id="heldOrdersModal" tabindex="-1" aria-labelledby="heldOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom py-3">
                <h5 class="modal-title fw-bold" id="heldOrdersModalLabel"><i class="bi bi-pause-circle me-2"></i>Daftar Pesanan Tersimpan (Hold)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3">
                <div class="list-group">
                    <?php foreach ($heldList as $h): ?>
                        <div class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3">
                            <div>
                                <div class="fw-bold text-wk-primary"><?= e($h['transaction_code']) ?></div>
                                <div class="text-muted small"><?= e($h['hold_note'] ?: 'Tanpa catatan') ?></div>
                                <div class="small fw-semibold mt-1"><?= format_rupiah($h['grand_total']) ?></div>
                            </div>
                            <button class="btn btn-sm btn-wk-primary" onclick="resumeOrder(<?= $h['id'] ?>)">
                                Lanjutkan &rarr;
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
