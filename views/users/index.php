<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Akun Pegawai</h4>
            <p class="text-muted small mb-0">Kelola pengguna kasir, tarif per jam kerja, dan hak akses</p>
        </div>
        <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
            <i class="bi bi-person-plus"></i> Tambah Akun Baru
        </button>
    </div>

    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th>Nama Pegawai</th>
                        <th>Username</th>
                        <th class="text-center">Role</th>
                        <th>No. Telepon</th>
                        <th class="text-end">Tarif Reguler / Jam</th>
                        <th class="text-end">Tarif Lembur / Jam</th>
                        <th class="text-center">Status</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td data-order="<?= strtotime($u['created_at']) ?>">
                                <div class="fw-bold text-wk-primary"><?= e($u['name']) ?></div>
                                <small class="text-muted">Login: <?= !empty($u['last_login_at']) ? date('d/m/Y H:i', strtotime($u['last_login_at'])) : 'Belum pernah' ?></small>
                            </td>
                            <td><code>@<?= e($u['username']) ?></code></td>
                            <td class="text-center">
                                <?php if ($u['role'] === 'OWNER'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">OWNER</span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">CASHIER</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($u['phone'] ?: '—') ?></td>
                            <?php if ($u['role'] === 'OWNER'): ?>
                                <td class="text-end fw-semibold text-info">
                                    <i class="bi bi-award me-1"></i>Intensive Manager
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <?= (float)shop_setting('manager_incentive_percent', 20) ?>% Laba Kotor
                                    </span>
                                </td>
                            <?php else: ?>
                                <td class="text-end fw-semibold"><?= format_rupiah($u['hourly_rate']) ?></td>
                                <td class="text-end fw-semibold text-warning text-dark"><?= format_rupiah($u['overtime_rate']) ?></td>
                            <?php endif; ?>
                            <td class="text-center">
                                <?php if ($u['status'] === 'ACTIVE'): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal<?= $u['id'] ?>" 
                                        title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-warning text-dark p-1 px-2" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#resetPassModal<?= $u['id'] ?>" 
                                        title="Reset Password">
                                    <i class="bi bi-key"></i>
                                </button>
                                <?php if ($u['id'] != auth_id()): ?>
                                    <form action="<?= url('/users/' . $u['id'] . '/delete') ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm"
                                                data-title="Nonaktifkan Akun?"
                                                data-text="Akun '<?= e($u['name']) ?>' tidak akan dapat login lagi."
                                                title="Hapus">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <!-- Edit User Modal -->
                        <div class="modal fade" id="editUserModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/users/' . $u['id'] . '/update') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold">Edit Akun Pegawai</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nama Lengkap</label>
                                                <input type="text" name="name" class="form-control" value="<?= e($u['name']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Username</label>
                                                <input type="text" name="username" class="form-control" value="<?= e($u['username']) ?>" required>
                                            </div>
                                            <div class="row g-2 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Role</label>
                                                    <select name="role" id="roleSelectEdit<?= $u['id'] ?>" class="form-select" onchange="toggleRateInputsEdit<?= $u['id'] ?>()" required>
                                                        <option value="CASHIER" <?= $u['role'] === 'CASHIER' ? 'selected' : '' ?>>Kasir (CASHIER)</option>
                                                        <option value="OWNER" <?= $u['role'] === 'OWNER' ? 'selected' : '' ?>>Pemilik (OWNER)</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="ACTIVE" <?= $u['status'] === 'ACTIVE' ? 'selected' : '' ?>>Aktif</option>
                                                        <option value="INACTIVE" <?= $u['status'] === 'INACTIVE' ? 'selected' : '' ?>>Nonaktif</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                                                <input type="text" name="phone" class="form-control" value="<?= e($u['phone']) ?>">
                                            </div>
                                            <!-- Intensive Manager for Owner -->
                                            <div id="ownerIncentiveBoxEdit<?= $u['id'] ?>" class="mb-3 p-3 rounded bg-info-subtle border border-info-subtle <?= $u['role'] === 'OWNER' ? '' : 'd-none' ?>">
                                                <div class="d-flex align-items-center gap-2 text-info-emphasis fw-bold small mb-1">
                                                    <i class="bi bi-award-fill"></i> Intensive Manager
                                                </div>
                                                <div class="small text-muted mb-0">
                                                    Role Owner tidak menggunakan tarif reguler & lembur per jam. Kompensasi murni dialokasikan dari <strong>Intensive Manager (<?= (float)shop_setting('manager_incentive_percent', 20) ?>% Laba Kotor)</strong>.
                                                </div>
                                            </div>
                                            <!-- Regular Hourly & Overtime Rates for Cashier -->
                                            <div id="cashierRateRowEdit<?= $u['id'] ?>" class="row g-2 mb-3 <?= $u['role'] === 'OWNER' ? 'd-none' : '' ?>">
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Tarif Reguler / Jam (Rp)</label>
                                                    <input type="number" name="hourly_rate" id="hourlyRateEdit<?= $u['id'] ?>" class="form-control" value="<?= (int)$u['hourly_rate'] ?>" min="0">
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label fw-semibold small">Tarif Lembur / Jam (Rp)</label>
                                                    <input type="number" name="overtime_rate" id="overtimeRateEdit<?= $u['id'] ?>" class="form-control" value="<?= (int)$u['overtime_rate'] ?>" min="0">
                                                </div>
                                            </div>
                                            <script>
                                            function toggleRateInputsEdit<?= $u['id'] ?>() {
                                                const sel = document.getElementById('roleSelectEdit<?= $u['id'] ?>');
                                                const box = document.getElementById('ownerIncentiveBoxEdit<?= $u['id'] ?>');
                                                const row = document.getElementById('cashierRateRowEdit<?= $u['id'] ?>');
                                                if (sel.value === 'OWNER') {
                                                    box.classList.remove('d-none');
                                                    row.classList.add('d-none');
                                                } else {
                                                    box.classList.add('d-none');
                                                    row.classList.remove('d-none');
                                                }
                                            }
                                            </script>
                                        </div>
                                        <div class="modal-footer border-top p-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-wk-primary btn-sm px-3">Simpan Perubahan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reset Password Modal -->
                        <div class="modal fade" id="resetPassModal<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <form action="<?= url('/users/' . $u['id'] . '/reset-password') ?>" method="POST">
                                        <?= csrf_field() ?>
                                        <div class="modal-header border-bottom py-3">
                                            <h5 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Reset Password: <?= e($u['username']) ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 text-start">
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold small">Password Baru <span class="text-danger">*</span></label>
                                                <input type="password" name="new_password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top p-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-warning text-dark btn-sm px-3">Simpan Password Baru</button>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/users/create') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Tambah Akun Pegawai Baru</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Rian Hidayat" required autofocus>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Username <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control" placeholder="Contoh: rian" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Role Hak Akses <span class="text-danger">*</span></label>
                            <select name="role" id="roleSelectAdd" class="form-select" onchange="toggleRateInputsAdd()" required>
                                <option value="CASHIER" selected>Kasir (CASHIER)</option>
                                <option value="OWNER">Pemilik (OWNER)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="ACTIVE" selected>Aktif</option>
                                <option value="INACTIVE">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nomor Telepon / WA</label>
                        <input type="text" name="phone" class="form-control" placeholder="Contoh: 081234567890">
                    </div>
                    <!-- Intensive Manager for Owner -->
                    <div id="ownerIncentiveBoxAdd" class="mb-3 p-3 rounded bg-info-subtle border border-info-subtle d-none">
                        <div class="d-flex align-items-center gap-2 text-info-emphasis fw-bold small mb-1">
                            <i class="bi bi-award-fill"></i> Intensive Manager
                        </div>
                        <div class="small text-muted mb-0">
                            Role Owner tidak menggunakan tarif reguler & lembur per jam. Kompensasi murni dialokasikan dari <strong>Intensive Manager (<?= (float)shop_setting('manager_incentive_percent', 20) ?>% Laba Kotor)</strong>.
                        </div>
                    </div>
                    <!-- Regular Hourly & Overtime Rates for Cashier -->
                    <div id="cashierRateRowAdd" class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tarif Reguler / Jam (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="hourly_rate" id="hourlyRateAdd" class="form-control" value="15000" min="0">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tarif Lembur / Jam (Rp) <span class="text-danger">*</span></label>
                            <input type="number" name="overtime_rate" id="overtimeRateAdd" class="form-control" value="5000" min="0">
                        </div>
                    </div>
                    <script>
                    function toggleRateInputsAdd() {
                        const sel = document.getElementById('roleSelectAdd');
                        const box = document.getElementById('ownerIncentiveBoxAdd');
                        const row = document.getElementById('cashierRateRowAdd');
                        if (sel.value === 'OWNER') {
                            box.classList.remove('d-none');
                            row.classList.add('d-none');
                        } else {
                            box.classList.add('d-none');
                            row.classList.remove('d-none');
                        }
                    }
                    </script>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Tambah Akun</button>
                </div>
            </form>
        </div>
    </div>
</div>
