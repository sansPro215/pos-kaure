<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <?= is_owner() ? 'Pengeluaran Operasional Kedai' : 'Pengeluaran Saya (Kasir)' ?>
            </h4>
            <p class="text-muted small mb-0">
                <?= is_owner() 
                    ? 'Catat biaya listrik, galon, gas, transport, kebersihan & sampah, dan operasional lainnya' 
                    : 'Catat dan pantau biaya operasional kedai yang Anda input sendiri' ?>
            </p>
        </div>
        <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class="bi bi-plus-circle"></i> Tambah Pengeluaran
        </button>
    </div>

    <!-- Filter Bar -->
    <div class="wk-card p-3 mb-3">
        <form action="<?= url('/expenses') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Dari Tanggal:</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>" title="Dari Tanggal">
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small text-muted mb-1">Sampai Tanggal:</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>" title="Sampai Tanggal">
            </div>
            <div class="col-8 col-md-4">
                <select name="category" class="form-select form-select-sm">
                    <option value="">Semua Kategori</option>
                    <option value="LISTRIK" <?= $category === 'LISTRIK' ? 'selected' : '' ?>>Listrik (PLN)</option>
                    <option value="AIR" <?= $category === 'AIR' ? 'selected' : '' ?>>Galon</option>
                    <option value="GAS" <?= $category === 'GAS' ? 'selected' : '' ?>>Gas Elpiji</option>
                    <option value="TRANSPORT" <?= $category === 'TRANSPORT' ? 'selected' : '' ?>>Transport & Pengiriman</option>
                    <option value="KEBERSIHAN" <?= $category === 'KEBERSIHAN' ? 'selected' : '' ?>>Kebersihan & Sampah</option>
                    <option value="LAINNYA" <?= $category === 'LAINNYA' ? 'selected' : '' ?>>Lainnya</option>
                </select>
            </div>
            <div class="col-4 col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-wk-primary btn-sm w-100">Filter</button>
                <a href="<?= url('/expenses') ?>" class="btn btn-outline-secondary btn-sm" title="Reset"><i class="bi bi-arrow-clockwise"></i></a>
            </div>
        </form>
    </div>

    <!-- Expense Summary Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 <?= is_owner() ? 'col-md-4' : 'col-md-12' ?>">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-danger">
                <div class="text-muted small fw-semibold">
                    <?= is_owner() ? 'Beban Operasional Kedai' : 'Total Biaya yang Saya Catat' ?>
                </div>
                <h4 class="fw-bold text-danger mt-1 mb-0"><?= format_rupiah($operationalTotal ?? $totalAmount) ?></h4>
                <small class="text-muted" style="font-size: 0.75rem;">
                    <?= count($expenses) ?> <?= is_owner() ? 'transaksi biaya operasional' : 'catatan pengeluaran Anda' ?>
                </small>
            </div>
        </div>
        <?php if (is_owner()): ?>
            <div class="col-12 col-md-4">
                <div class="wk-card p-3 shadow-sm border-start border-4 border-warning">
                    <div class="text-muted small fw-semibold">Beban Gaji Pegawai (Payroll PAID)</div>
                    <h4 class="fw-bold text-warning mt-1 mb-0"><?= format_rupiah($payrollTotal ?? 0) ?></h4>
                    <small class="text-muted" style="font-size: 0.75rem;"><?= count($paidPayrolls ?? []) ?> slip gaji lunas</small>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="wk-card p-3 shadow-sm border-start border-4 border-wk" style="border-left-color: var(--wk-primary) !important;">
                    <div class="text-muted small fw-semibold">Total Seluruh Pengeluaran</div>
                    <h4 class="fw-bold text-wk-primary mt-1 mb-0"><?= format_rupiah($totalAmount) ?></h4>
                    <small class="text-muted" style="font-size: 0.75rem;">Operasional + Payroll</small>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Expenses Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="text-end">Nominal</th>
                        <th>Dicatat Oleh</th>
                        <th class="text-end" style="width: 130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                        $catLabels = [
                            'LISTRIK' => 'Listrik (PLN)',
                            'AIR' => 'Galon',
                            'GAS' => 'Gas Elpiji',
                            'TRANSPORT' => 'Transport',
                            'KEBERSIHAN' => 'Kebersihan & Sampah',
                            'LAINNYA' => 'Lainnya',
                        ];
                    ?>
                    <?php foreach ($expenses as $ex): ?>
                        <tr>
                            <td class="text-nowrap small text-muted" data-order="<?= strtotime($ex['date'] . ' ' . (!empty($ex['created_at']) ? $ex['created_at'] : '00:00:00')) ?>"><?= date('d/m/Y', strtotime($ex['date'])) ?></td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <?= e($catLabels[$ex['category']] ?? $ex['category']) ?>
                                </span>
                            </td>
                            <td class="fw-semibold"><?= e($ex['description']) ?></td>
                            <td class="text-end fw-bold text-danger"><?= format_rupiah($ex['amount']) ?></td>
                            <td class="small text-muted"><?= e($ex['creator_name'] ?? 'Admin') ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editExpenseModal<?= $ex['id'] ?>" 
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form action="<?= url('/expenses/' . $ex['id'] . '/delete') ?>" method="POST" class="d-inline">
                                    <?= csrf_field() ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm"
                                            data-title="Hapus Pengeluaran?"
                                            data-text="Catatan biaya ini akan dihapus."
                                            title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editExpenseModal<?= $ex['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/expenses/' . $ex['id'] . '/update') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold">Edit Pengeluaran</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Tanggal</label>
                                                <input type="date" name="date" class="form-control" value="<?= e($ex['date']) ?>" required>
                                            </div>
                                            <?php 
                                                $stdCats = ['LISTRIK', 'AIR', 'GAS', 'TRANSPORT', 'KEBERSIHAN', 'LAINNYA'];
                                                $isCustomCat = !in_array($ex['category'], $stdCats);
                                                $selectedCat = $isCustomCat ? 'LAINNYA' : $ex['category'];
                                            ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Kategori</label>
                                                <select name="category" id="editCategorySelect<?= $ex['id'] ?>" class="form-select" onchange="toggleEditCustomCategory(<?= $ex['id'] ?>)" required>
                                                    <option value="LISTRIK" <?= $selectedCat === 'LISTRIK' ? 'selected' : '' ?>>Listrik (PLN)</option>
                                                    <option value="AIR" <?= $selectedCat === 'AIR' ? 'selected' : '' ?>>Galon</option>
                                                    <option value="GAS" <?= $selectedCat === 'GAS' ? 'selected' : '' ?>>Gas Elpiji</option>
                                                    <option value="TRANSPORT" <?= $selectedCat === 'TRANSPORT' ? 'selected' : '' ?>>Transport & Pengiriman</option>
                                                    <option value="KEBERSIHAN" <?= $selectedCat === 'KEBERSIHAN' ? 'selected' : '' ?>>Kebersihan & Sampah</option>
                                                    <option value="LAINNYA" <?= $selectedCat === 'LAINNYA' ? 'selected' : '' ?>>Lainnya</option>
                                                </select>
                                            </div>
                                            <?php if (is_owner()): ?>
                                            <div class="mb-3" id="editCustomCategoryDiv<?= $ex['id'] ?>" style="display: <?= $selectedCat === 'LAINNYA' ? 'block' : 'none' ?>;">
                                                <label class="form-label fw-semibold small">Detail Kategori (Ketik Manual Opsional)</label>
                                                <input type="text" name="custom_category" class="form-control" value="<?= $isCustomCat ? e($ex['category']) : '' ?>" placeholder="Contoh: Pembelian Alat">
                                            </div>
                                            <?php endif; ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nominal (Rp)</label>
                                                <input type="number" name="amount" class="form-control" value="<?= (float)$ex['amount'] ?>" min="0" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Keterangan</label>
                                                <textarea name="description" class="form-control" rows="2" required><?= e($ex['description']) ?></textarea>
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

    <?php if (is_owner()): ?>
    <!-- Paid Payroll Disbursements Section -->
    <div class="wk-card p-3 p-md-4 shadow-sm mt-4">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <div>
                <h6 class="fw-bold m-0"><i class="bi bi-credit-card-2-front me-2 text-warning"></i>Beban Gaji Pegawai (Payroll Lunas / PAID)</h6>
                <small class="text-muted">Daftar slip penggajian yang telah berstatus Lunas pada periode ini</small>
            </div>
            <a href="<?= url('/payroll') ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-right-circle me-1"></i> Buka Modul Payroll
            </a>
        </div>
        <?php if (empty($paidPayrolls)): ?>
            <div class="text-center py-4 text-muted small">
                <i class="bi bi-receipt fs-2 d-block mb-1 opacity-50"></i>
                Tidak ada data pembayaran gaji pegawai yang berstatus Lunas pada rentang tanggal ini.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Bayar</th>
                            <th>Nama Pegawai</th>
                            <th>Periode Payroll</th>
                            <th>Metode Bayar</th>
                            <th class="text-end">Gaji Bersih</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paidPayrolls as $pp): ?>
                            <tr>
                                <td class="text-nowrap text-muted"><?= !empty($pp['payment_date']) ? date('d/m/Y', strtotime($pp['payment_date'])) : '—' ?></td>
                                <td class="fw-semibold text-wk-primary"><?= e($pp['user_name']) ?> <span class="badge bg-secondary-subtle text-secondary"><?= e($pp['role']) ?></span></td>
                                <td class="text-muted"><?= e($pp['period_name']) ?></td>
                                <td><?= e($pp['payment_method'] ?? 'TRANSFER') ?></td>
                                <td class="text-end fw-bold text-danger"><?= format_rupiah($pp['net_salary']) ?></td>
                                <td class="text-center"><span class="badge bg-success">PAID</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/expenses/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Tambah Pengeluaran Operasional</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="p-2 mb-3 rounded-3 bg-body-tertiary border small d-flex align-items-center gap-2">
                        <i class="bi bi-person-check-fill text-wk-primary fs-5"></i>
                        <div>
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Dicatat atas nama:</span>
                            <strong class="text-dark"><?= e(auth_user()['name'] ?? 'Saya') ?></strong> <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;"><?= e(auth_user()['role'] ?? 'CASHIER') ?></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Kategori <span class="text-danger">*</span></label>
                        <select name="category" id="addCategorySelect" class="form-select" onchange="toggleAddCustomCategory()" required>
                            <option value="LISTRIK">Listrik (PLN)</option>
                            <option value="AIR">Galon</option>
                            <option value="GAS">Gas Elpiji</option>
                            <option value="TRANSPORT">Transport & Pengiriman</option>
                            <option value="KEBERSIHAN">Kebersihan & Sampah</option>
                            <option value="LAINNYA" selected>Lainnya</option>
                        </select>
                    </div>
                    <?php if (is_owner()): ?>
                    <div class="mb-3" id="addCustomCategoryDiv">
                        <label class="form-label fw-semibold small">Detail Kategori (Ketik Manual Opsional)</label>
                        <input type="text" name="custom_category" class="form-control" placeholder="Contoh: Pembelian Alat">
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nominal Biaya (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" placeholder="Contoh: 150000" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Keterangan / Keperluan <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Contoh: Beli sabun cuci piring & plastik kresek" required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Simpan Biaya</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleAddCustomCategory() {
    const select = document.getElementById('addCategorySelect');
    const customDiv = document.getElementById('addCustomCategoryDiv');
    if (customDiv) {
        if (select.value === 'LAINNYA') {
            customDiv.style.display = 'block';
        } else {
            customDiv.style.display = 'none';
        }
    }
}

function toggleEditCustomCategory(id) {
    const select = document.getElementById('editCategorySelect' + id);
    const customDiv = document.getElementById('editCustomCategoryDiv' + id);
    if (customDiv) {
        if (select.value === 'LAINNYA') {
            customDiv.style.display = 'block';
        } else {
            customDiv.style.display = 'none';
        }
    }
}
</script>
