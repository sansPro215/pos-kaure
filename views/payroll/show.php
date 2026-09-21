<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h4 class="fw-bold mb-0"><?= e($period['name']) ?></h4>
                <?php if ($period['is_closed']): ?>
                    <span class="badge bg-secondary">Periode Ditutup</span>
                <?php else: ?>
                    <span class="badge bg-success">Periode Terbuka</span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                Rentang: <?= date('d/m/Y', strtotime($period['start_date'])) ?> &mdash; <?= date('d/m/Y', strtotime($period['end_date'])) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if (!$period['is_closed']): ?>
                <form action="<?= url('/payroll/' . $period['id'] . '/generate') ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3 py-2">
                        <i class="bi bi-arrow-repeat me-1"></i> Hitung Otomatis dari Absensi
                    </button>
                </form>

                <form action="<?= url('/payroll/' . $period['id'] . '/close') ?>" method="POST" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-outline-danger btn-sm px-3 py-2 btn-confirm"
                            data-title="Tutup Periode Payroll?"
                            data-text="Setelah ditutup, slip gaji pada periode ini tidak dapat dihitung ulang otomatis.">
                        <i class="bi bi-lock me-1"></i> Tutup Periode
                    </button>
                </form>
            <?php endif; ?>

            <a href="<?= url('/payroll/' . $period['id'] . '/print') ?>" target="_blank" class="btn btn-outline-secondary btn-sm px-3 py-2">
                <i class="bi bi-printer me-1"></i> Cetak Rekap
            </a>

            <a href="<?= url('/payroll') ?>" class="btn btn-outline-secondary btn-sm px-3 py-2">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Slips Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <?php if (empty($payrolls)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-calculator fs-1 d-block mb-2 opacity-50"></i>
                Belum ada perhitungan gaji untuk periode ini.<br>
                Silakan tekan tombol <strong>"Hitung Otomatis dari Absensi"</strong> di atas.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th>Pegawai</th>
                            <th class="text-center">Jam Reguler</th>
                            <th class="text-center">Jam Lembur</th>
                            <th class="text-end">Gaji Pokok & Lembur</th>
                            <th class="text-end">Bonus</th>
                            <th class="text-end">Potongan</th>
                            <th class="text-end">Total Gaji Bersih</th>
                            <th class="text-center">Status</th>
                            <th class="text-end" style="width: 220px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $py): 
                            $isOwnerSlip = ($py['role'] ?? '') === 'OWNER';
                        ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-wk-primary">
                                        <?= e($py['user_name']) ?>
                                        <?php if ($isOwnerSlip): ?>
                                            <span class="badge bg-primary-subtle text-primary ms-1" style="font-size: 0.7rem;">Owner</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php if ($isOwnerSlip): ?>
                                            Murni Intensif Manager (Bagi Hasil Kedai)
                                        <?php else: ?>
                                            Tarif: <?= format_rupiah($py['hourly_rate_snapshot']) ?>/j • Lembur: <?= format_rupiah($py['overtime_rate_snapshot']) ?>/j
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td class="text-center fw-semibold">
                                    <?php if ($isOwnerSlip): ?>
                                        <span class="badge bg-info-subtle text-info">Intensif Manager</span><br>
                                        <small class="text-muted"><?= format_rupiah($py['regular_pay']) ?></small>
                                    <?php else: ?>
                                        <?= (float)$py['regular_hours'] ?> jam<br>
                                        <small class="text-muted"><?= format_rupiah($py['regular_pay']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-semibold">
                                    <?php if ($isOwnerSlip): ?>
                                        <span class="text-muted">—</span>
                                    <?php elseif ($py['overtime_hours'] > 0): ?>
                                        <span class="text-warning text-dark">+<?= (float)$py['overtime_hours'] ?> jam</span><br>
                                        <small class="text-muted"><?= format_rupiah($py['overtime_pay']) ?></small>
                                    <?php else: ?>
                                        <span class="text-muted">0 jam</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-semibold">
                                    <?= format_rupiah($py['regular_pay'] + $py['overtime_pay']) ?>
                                    <?php if ($isOwnerSlip): ?>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">100% Bagi Hasil</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-success fw-semibold">
                                    +<?= format_rupiah($py['bonus']) ?>
                                </td>
                                <td class="text-end text-danger fw-semibold">
                                    -<?= format_rupiah($py['deduction']) ?>
                                </td>
                                <td class="text-end fw-bold fs-6 text-wk-primary text-nowrap">
                                    <?= format_rupiah($py['net_salary']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($py['payment_status'] === 'PAID'): ?>
                                        <span class="badge bg-success">Lunas (PAID)</span><br>
                                        <small class="text-muted" style="font-size: 0.72rem;">
                                            <?= date('d/m/y', strtotime($py['payment_date'])) ?> via <?= e($py['payment_method']) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Belum Dibayar</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="<?= url('/payroll/slip/' . $py['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary p-1 px-2 me-1" title="Cetak Slip Gaji & Rekap Absensi">
                                        <i class="bi bi-printer"></i> Slip
                                    </a>
                                    <button class="btn btn-sm btn-outline-secondary p-1 px-2 me-1" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editPayrollModal<?= $py['id'] ?>" 
                                            title="Edit Rincian & Status Gaji">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <?php if ($py['payment_status'] === 'UNPAID'): ?>
                                        <button class="btn btn-sm btn-success p-1 px-2 me-1" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#payModal<?= $py['id'] ?>" 
                                                title="Bayar Gaji">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    <form action="<?= url('/payroll/item/' . $py['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus slip gaji <?= addslashes(e($py['user_name'])) ?> dari periode ini?')">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger p-1 px-2" title="Hapus Slip Pegawai">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Edit Full Payroll Item Modal -->
                            <div class="modal fade" id="editPayrollModal<?= $py['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form action="<?= url('/payroll/' . $py['id'] . '/update-item') ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                                            <div class="modal-header border-bottom py-3">
                                                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-wk-primary"></i>Edit Slip: <?= e($py['user_name']) ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-3 p-md-4 text-start">
                                                <?php if ($isOwnerSlip): ?>
                                                    <!-- Owner Incentive Display & Edit -->
                                                    <div class="alert alert-info py-2 px-3 small mb-3">
                                                        <i class="bi bi-info-circle-fill me-1"></i>
                                                        <strong>Gaji Owner (Intensif Manager):</strong> Gaji pokok owner dialokasikan murni dari intensif manager laba periode ini (tanpa bergantung jam absensi).
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-semibold small">Gaji Pokok / Intensif Manager (Rp)</label>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">Rp</span>
                                                            <input type="number" name="regular_pay" class="form-control" value="<?= (float)$py['regular_pay'] ?>" min="0" required>
                                                        </div>
                                                        <input type="hidden" name="regular_hours" value="0">
                                                        <input type="hidden" name="overtime_hours" value="0">
                                                        <input type="hidden" name="hourly_rate_snapshot" value="0">
                                                        <input type="hidden" name="overtime_rate_snapshot" value="0">
                                                    </div>
                                                <?php else: ?>
                                                    <!-- Hours & Rates for Regular Staff -->
                                                    <div class="row g-2 mb-3">
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Jam Reguler (Jam)</label>
                                                            <input type="number" step="0.25" name="regular_hours" class="form-control form-control-sm" value="<?= (float)$py['regular_hours'] ?>" min="0" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Tarif Pokok (Rp/Jam)</label>
                                                            <input type="number" name="hourly_rate_snapshot" class="form-control form-control-sm" value="<?= (int)$py['hourly_rate_snapshot'] ?>" min="0" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Jam Lembur (Jam Penuh)</label>
                                                            <input type="number" step="1" name="overtime_hours" class="form-control form-control-sm" value="<?= (int)$py['overtime_hours'] ?>" min="0" required>
                                                        </div>
                                                        <div class="col-6">
                                                            <label class="form-label fw-semibold small">Tarif Lembur (Rp/Jam)</label>
                                                            <input type="number" name="overtime_rate_snapshot" class="form-control form-control-sm" value="<?= (int)$py['overtime_rate_snapshot'] ?>" min="0" required>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Bonus & Deduction -->
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small text-success">Bonus / Insentif (Rp)</label>
                                                        <input type="number" name="bonus" class="form-control form-control-sm" value="<?= (float)$py['bonus'] ?>" min="0">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small text-danger">Potongan / Kasbon (Rp)</label>
                                                        <input type="number" name="deduction" class="form-control form-control-sm" value="<?= (float)$py['deduction'] ?>" min="0">
                                                    </div>
                                                </div>

                                                <!-- Status & Method -->
                                                <div class="row g-2 mb-3 border-top pt-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Status Pembayaran</label>
                                                        <select name="payment_status" class="form-select form-select-sm">
                                                            <option value="UNPAID" <?= $py['payment_status'] === 'UNPAID' ? 'selected' : '' ?>>Belum Dibayar (UNPAID)</option>
                                                            <option value="PAID" <?= $py['payment_status'] === 'PAID' ? 'selected' : '' ?>>Lunas (PAID)</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Metode Pembayaran</label>
                                                        <select name="payment_method" class="form-select form-select-sm">
                                                            <option value="TRANSFER" <?= ($py['payment_method'] ?? '') === 'TRANSFER' ? 'selected' : '' ?>>Transfer Bank</option>
                                                            <option value="CASH" <?= ($py['payment_method'] ?? '') === 'CASH' ? 'selected' : '' ?>>Tunai (Cash)</option>
                                                            <option value="EWALLET" <?= ($py['payment_method'] ?? '') === 'EWALLET' ? 'selected' : '' ?>>E-Wallet</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="mb-2">
                                                    <label class="form-label fw-semibold small">Catatan Slip / Keterangan</label>
                                                    <input type="text" name="notes" class="form-control form-control-sm" value="<?= e($py['notes'] ?? '') ?>" placeholder="Misal: Penyesuaian jam atau nomor rekening">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top p-3">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-wk-primary btn-sm px-3"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Mark Paid Modal -->
                            <div class="modal fade" id="payModal<?= $py['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form action="<?= url('/payroll/' . $py['id'] . '/mark-paid') ?>" method="POST">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="period_id" value="<?= $period['id'] ?>">
                                            <div class="modal-header border-bottom py-3">
                                                <h5 class="modal-title fw-bold text-success"><i class="bi bi-cash-coin me-2"></i>Tandai Pembayaran Gaji</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <div class="p-3 bg-success-subtle rounded-3 mb-3 text-center">
                                                    <span class="text-muted small d-block">Nominal Gaji Bersih Yang Dibayarkan:</span>
                                                    <h3 class="fw-bold text-success mb-0"><?= format_rupiah($py['net_salary']) ?></h3>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Metode Pembayaran</label>
                                                    <select name="payment_method" class="form-select">
                                                        <option value="TRANSFER" selected>Transfer Bank / M-Banking</option>
                                                        <option value="CASH">Tunai (Cash)</option>
                                                        <option value="EWALLET">E-Wallet</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Catatan Pembayaran</label>
                                                    <input type="text" name="notes" class="form-control" placeholder="Contoh: Ditransfer ke rek BCA">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top p-3">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success btn-sm px-3">Konfirmasi Lunas</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
