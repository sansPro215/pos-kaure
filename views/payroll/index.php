<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Penggajian Pegawai (Payroll 14 Hari)</h4>
            <p class="text-muted small mb-0">Sistem penggajian dua mingguan otomatis berbasis jam kerja dan lembur</p>
        </div>
        <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
            <i class="bi bi-plus-circle"></i> Buka Periode Payroll Baru
        </button>
    </div>

    <!-- Alert Info -->
    <div class="alert alert-info small d-flex align-items-center gap-2 mb-4">
        <i class="bi bi-info-circle-fill fs-5"></i>
        <div>
            Sistem menghitung gaji reguler (maksimal 8 jam/hari) berdasarkan tarif per jam pegawai, ditambah lembur otomatis (Rp5.000/jam secara default). Nilai tarif diabadikan (snapshot) pada saat payroll dihitung agar tidak berubah ketika tarif pegawai nantinya diedit.
        </div>
    </div>

    <!-- Periods Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th>Nama Periode</th>
                        <th class="text-center">Rentang Tanggal (14 Hari)</th>
                        <th class="text-center">Jumlah Pegawai</th>
                        <th class="text-end">Total Pembayaran Gaji</th>
                        <th class="text-center">Status Periode</th>
                        <th class="text-end" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($periods as $p): ?>
                        <tr>
                            <td>
                                <a href="<?= url('/payroll/' . $p['id']) ?>" class="fw-bold text-wk-primary text-decoration-none">
                                    <?= e($p['name']) ?>
                                </a>
                            </td>
                            <td class="text-center small text-nowrap" data-order="<?= strtotime($p['start_date']) ?>">
                                <?= date('d/m/Y', strtotime($p['start_date'])) ?> &mdash; <?= date('d/m/Y', strtotime($p['end_date'])) ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">
                                    <?= (int)$p['employee_count'] ?> Pegawai
                                </span>
                            </td>
                            <td class="text-end fw-bold text-success text-nowrap">
                                <?= format_rupiah($p['total_payroll_amount']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['is_closed']): ?>
                                    <span class="badge bg-secondary">Ditutup</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Aktif / Terbuka</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex align-items-center justify-content-end gap-1">
                                    <a href="<?= url('/payroll/' . $p['id']) ?>" class="btn btn-sm btn-wk-primary px-2 py-1" title="Buka Detail Slip">
                                        <i class="bi bi-eye"></i> Slip
                                    </a>
                                    <a href="<?= url('/payroll/' . $p['id'] . '/print') ?>" target="_blank" class="btn btn-sm btn-outline-secondary px-2 py-1" title="Cetak Rekap Periode">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <form action="<?= url('/payroll/' . $p['id'] . '/delete') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus periode \'<?= addslashes(e($p['name'])) ?>\'? Semua slip gaji di dalamnya akan ikut dihapus.')">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger px-2 py-1" title="Hapus Periode">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Create Period -->
<div class="modal fade" id="createPeriodModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/payroll/create-period') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Buat Periode Payroll 14 Hari</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Nama Periode <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="Contoh: Periode 15 Sep - 28 Sep 2026" required autofocus>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" id="payrollStartDate" class="form-control" value="<?= date('Y-m-01') ?>" required onchange="calcEndDate()">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Tanggal Akhir <span class="text-danger">*</span></label>
                            <input type="date" name="end_date" id="payrollEndDate" class="form-control" value="<?= date('Y-m-14') ?>" required>
                        </div>
                    </div>
                    <div class="form-text small">Disarankan menggunakan siklus 14 hari kerja (2 minggu).</div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Buat Periode</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function calcEndDate() {
    const startInput = document.getElementById('payrollStartDate');
    const endInput = document.getElementById('payrollEndDate');
    if (startInput.value) {
        const d = new Date(startInput.value);
        d.setDate(d.getDate() + 13); // 14 days total
        endInput.value = d.toISOString().split('T')[0];
    }
}
</script>
