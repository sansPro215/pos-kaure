<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Absensi & Jam Kerja</h4>
            <p class="text-muted small mb-0"><?= date('l, d F Y') ?></p>
        </div>
        <?php if (is_owner()): ?>
            <button class="btn btn-wk-primary btn-sm d-flex align-items-center gap-2 px-3 py-2" data-bs-toggle="modal" data-bs-target="#manualAttendanceModal">
                <i class="bi bi-plus-circle"></i> Input Absensi Manual
            </button>
        <?php endif; ?>
    </div>

    <!-- Clock In / Clock Out Card for Current User (Pegawai Saja, Owner Tidak Absen) -->
    <?php if (!is_owner()): ?>
    <div class="wk-card p-4 mb-4 shadow-sm">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-7">
                <span class="text-muted small fw-semibold">Status Absensi Anda Hari Ini:</span>
                <div class="d-flex align-items-center gap-3 mt-2">
                    <div>
                        <small class="text-muted d-block">Jam Masuk:</small>
                        <h4 class="fw-bold mb-0 <?= !empty($todayAttendance['clock_in']) ? 'text-success' : 'text-muted' ?>">
                            <?= !empty($todayAttendance['clock_in']) ? date('H:i', strtotime($todayAttendance['clock_in'])) : '—' ?>
                        </h4>
                    </div>
                    <div class="border-start ps-3">
                        <small class="text-muted d-block">Jam Pulang:</small>
                        <h4 class="fw-bold mb-0 <?= !empty($todayAttendance['clock_out']) ? 'text-primary' : 'text-muted' ?>">
                            <?= !empty($todayAttendance['clock_out']) ? date('H:i', strtotime($todayAttendance['clock_out'])) : '—' ?>
                        </h4>
                    </div>
                    <?php if (!empty($todayAttendance['worked_minutes'])): ?>
                        <div class="border-start ps-3">
                            <small class="text-muted d-block">Durasi Kerja:</small>
                            <h4 class="fw-bold mb-0 text-dark"><?= format_minutes($todayAttendance['worked_minutes']) ?></h4>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($todayAttendance['auto_clock_in'])): ?>
                    <div class="text-muted small mt-2">
                        <i class="bi bi-check2-all text-success me-1"></i> Tercatat otomatis saat Anda login hari ini.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Clock In / Clock Out Button -->
            <div class="col-12 col-md-5 d-flex justify-content-md-end">
                <?php if (empty($todayAttendance)): ?>
                    <form action="<?= url('/attendance/clock-in') ?>" method="POST" class="w-100 w-md-auto">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-success w-100 py-3 fw-bold fs-6 shadow-sm" style="min-width: 200px; border-radius: 12px;">
                            <i class="bi bi-box-arrow-in-right me-1"></i> ABSEN MASUK
                        </button>
                    </form>
                <?php elseif (empty($todayAttendance['clock_out'])): ?>
                    <form action="<?= url('/attendance/clock-out') ?>" method="POST" class="w-100 w-md-auto">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-6 shadow-sm" style="min-width: 200px; border-radius: 12px;">
                            <i class="bi bi-box-arrow-right me-1"></i> ABSEN PULANG
                        </button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success d-flex align-items-center gap-2 m-0 p-3 rounded-3">
                        <i class="bi bi-check-circle-fill fs-4"></i>
                        <div>
                            <strong>Shift Selesai</strong><br>
                            <span class="small">Absensi masuk & pulang hari ini telah tuntas.</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Owner Attendance Overview & Logs -->
    <?php if (is_owner()): ?>
        <!-- Today KPI Summary -->
        <div class="row g-2 mb-4 text-center small">
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-wk-surface shadow-sm">
                    <span class="text-muted d-block">Sedang Bekerja</span>
                    <strong class="fs-4 text-success"><?= $summaryToday['currently_working'] ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-wk-surface shadow-sm">
                    <span class="text-muted d-block">Selesai Shift</span>
                    <strong class="fs-4 text-primary"><?= $summaryToday['finished_shift'] ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-wk-surface shadow-sm">
                    <span class="text-muted d-block">Izin / Sakit</span>
                    <strong class="fs-4 text-warning text-dark"><?= $summaryToday['izin_count'] + $summaryToday['sakit_count'] ?></strong>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="p-3 border rounded bg-wk-surface shadow-sm">
                    <span class="text-muted d-block">Alpha</span>
                    <strong class="fs-4 text-danger"><?= $summaryToday['alpha_count'] ?></strong>
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="wk-card p-3 mb-3">
            <form action="<?= url('/attendance') ?>" method="GET" class="row g-2 align-items-center">
                <div class="col-6 col-md-3">
                    <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>" title="Dari Tanggal">
                </div>
                <div class="col-6 col-md-3">
                    <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>" title="Sampai Tanggal">
                </div>
                <div class="col-6 col-md-3">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Semua Pegawai</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>" <?= $filterUserId == $u['id'] ? 'selected' : '' ?>>
                                <?= e($u['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="HADIR" <?= $status === 'HADIR' ? 'selected' : '' ?>>Hadir</option>
                        <option value="IZIN" <?= $status === 'IZIN' ? 'selected' : '' ?>>Izin</option>
                        <option value="SAKIT" <?= $status === 'SAKIT' ? 'selected' : '' ?>>Sakit</option>
                        <option value="ALPHA" <?= $status === 'ALPHA' ? 'selected' : '' ?>>Alpha</option>
                        <option value="LIBUR" <?= $status === 'LIBUR' ? 'selected' : '' ?>>Libur</option>
                    </select>
                </div>
                <div class="col-12 col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-wk-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Attendance Logs Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar-check me-2"></i>Catatan Rekap Kehadiran</h6>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 datatable">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Pegawai</th>
                        <th class="text-center">Jam Masuk</th>
                        <th class="text-center">Jam Pulang</th>
                        <th class="text-center">Reguler (Maks 8j)</th>
                        <th class="text-center">Lembur</th>
                        <th class="text-center">Status</th>
                        <?php if (is_owner()): ?>
                            <th class="text-end" style="width: 80px;">Aksi</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td class="text-nowrap small" data-order="<?= strtotime($log['date'] . ' ' . (!empty($log['clock_in']) ? $log['clock_in'] : '00:00:00')) ?>"><?= date('d/m/Y', strtotime($log['date'])) ?></td>
                            <td>
                                <div class="fw-bold text-wk-primary"><?= e($log['user_name']) ?></div>
                                <small class="text-muted"><?= e($log['role']) ?></small>
                            </td>
                            <td class="text-center fw-semibold">
                                <?= !empty($log['clock_in']) ? date('H:i', strtotime($log['clock_in'])) : '—' ?>
                            </td>
                            <td class="text-center fw-semibold">
                                <?= !empty($log['clock_out']) ? date('H:i', strtotime($log['clock_out'])) : '—' ?>
                            </td>
                            <td class="text-center">
                                <?= !empty($log['regular_minutes']) ? minutes_to_hours($log['regular_minutes']) . ' jam' : '0 jam' ?>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($log['overtime_minutes']) && $log['overtime_minutes'] >= 60): ?>
                                    <span class="badge bg-warning text-dark">+<?= (int)floor($log['overtime_minutes'] / 60) ?> jam</span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php 
                                    $sBadge = 'bg-success text-white';
                                    $sLabel = '<i class="bi bi-clock-history me-1"></i>Bekerja';
                                    if ($log['status'] === 'SELESAI' || (!empty($log['clock_out']) && $log['status'] === 'HADIR')) {
                                        $sBadge = 'bg-primary text-white';
                                        $sLabel = '<i class="bi bi-check2-circle me-1"></i>Selesai';
                                    } elseif ($log['status'] === 'IZIN') {
                                        $sBadge = 'bg-info text-dark';
                                        $sLabel = 'Izin';
                                    } elseif ($log['status'] === 'SAKIT') {
                                        $sBadge = 'bg-warning text-dark';
                                        $sLabel = 'Sakit';
                                    } elseif ($log['status'] === 'ALPHA') {
                                        $sBadge = 'bg-danger text-white';
                                        $sLabel = 'Alpha';
                                    } elseif ($log['status'] === 'LIBUR') {
                                        $sBadge = 'bg-secondary text-white';
                                        $sLabel = 'Libur';
                                    }
                                ?>
                                <span class="badge <?= $sBadge ?> small px-2 py-1"><?= $sLabel ?></span>
                            </td>
                            <?php if (is_owner()): ?>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn btn-sm btn-outline-secondary p-1 px-2" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editAttModal<?= $log['id'] ?>" 
                                                title="Koreksi">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form action="<?= url('/attendance/' . $log['id'] . '/delete') ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger p-1 px-2 btn-confirm" 
                                                    data-title="Hapus Data Absensi?" 
                                                    data-text="Catatan absensi <?= e($log['user_name']) ?> pada <?= date('d/m/Y', strtotime($log['date'])) ?> akan dihapus." 
                                                    title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>

                        <!-- Owner Correction Modal -->
                        <?php if (is_owner()): ?>
                            <div class="modal fade" id="editAttModal<?= $log['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow">
                                        <form action="<?= url('/attendance/' . $log['id'] . '/update') ?>" method="POST" id="formEditAtt<?= $log['id'] ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action_type" id="actionType<?= $log['id'] ?>" value="">
                                            <div class="modal-header border-bottom py-3">
                                                <div>
                                                    <h5 class="modal-title fw-bold mb-0">Koreksi Absensi: <?= e($log['user_name']) ?></h5>
                                                    <small class="text-muted">Atur jam kehadiran atau pulihkan akses transaksi kasir</small>
                                                </div>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body p-4 text-start">
                                                <!-- Quick Actions Box for Status Selesai / Hadir -->
                                                <div class="p-3 rounded-3 mb-3 border bg-body-tertiary">
                                                    <div class="small fw-bold text-muted text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                                                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Aksi Cepat Status Selesai
                                                    </div>
                                                    <div class="d-flex flex-wrap gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" onclick="cancelFinished<?= $log['id'] ?>()">
                                                            <i class="bi bi-arrow-counterclockwise"></i> Batalkan Selesai (Aktifkan Shift)
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-success d-flex align-items-center gap-1" onclick="setFinishedNow<?= $log['id'] ?>()">
                                                            <i class="bi bi-check2-circle"></i> Set Selesai (Jam Sekarang)
                                                        </button>
                                                    </div>
                                                    <div id="quickActionAlert<?= $log['id'] ?>" class="alert alert-info py-2 px-3 small mt-2 mb-0 d-none"></div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Tanggal</label>
                                                    <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($log['date'])) ?>" readonly>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Jam Masuk</label>
                                                        <input type="time" name="clock_in" id="clockIn<?= $log['id'] ?>" class="form-control" value="<?= e($log['clock_in'] ? substr($log['clock_in'], 0, 5) : '') ?>">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label fw-semibold small">Jam Pulang (Bisa Diatur)</label>
                                                        <input type="time" name="clock_out" id="clockOut<?= $log['id'] ?>" class="form-control" value="<?= e($log['clock_out'] ? substr($log['clock_out'], 0, 5) : '') ?>">
                                                    </div>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Status Kehadiran</label>
                                                    <select name="status" id="statusSelect<?= $log['id'] ?>" class="form-select">
                                                        <option value="HADIR" <?= $log['status'] === 'HADIR' ? 'selected' : '' ?>>Hadir (Sedang Bekerja)</option>
                                                        <option value="SELESAI" <?= ($log['status'] === 'SELESAI' || (!empty($log['clock_out']) && $log['status'] === 'HADIR')) ? 'selected' : '' ?>>Selesai (Sudah Absen Pulang)</option>
                                                        <option value="IZIN" <?= $log['status'] === 'IZIN' ? 'selected' : '' ?>>Izin</option>
                                                        <option value="SAKIT" <?= $log['status'] === 'SAKIT' ? 'selected' : '' ?>>Sakit</option>
                                                        <option value="ALPHA" <?= $log['status'] === 'ALPHA' ? 'selected' : '' ?>>Alpha</option>
                                                        <option value="LIBUR" <?= $log['status'] === 'LIBUR' ? 'selected' : '' ?>>Libur</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold small">Catatan Koreksi</label>
                                                    <input type="text" name="note" id="noteInput<?= $log['id'] ?>" class="form-control" value="<?= e($log['note']) ?>" placeholder="Alasan koreksi absensi">
                                                </div>
                                            </div>
                                            <div class="modal-footer border-top p-3 d-flex justify-content-between">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                <button type="submit" class="btn btn-wk-primary btn-sm px-4">Simpan Koreksi</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <script>
                            function cancelFinished<?= $log['id'] ?>() {
                                document.getElementById('clockOut<?= $log['id'] ?>').value = '';
                                document.getElementById('statusSelect<?= $log['id'] ?>').value = 'HADIR';
                                document.getElementById('actionType<?= $log['id'] ?>').value = 'cancel_finished';
                                const note = document.getElementById('noteInput<?= $log['id'] ?>');
                                if (!note.value.trim()) {
                                    note.value = 'Status selesai dibatalkan oleh Owner (shift diaktifkan kembali)';
                                }
                                const alertBox = document.getElementById('quickActionAlert<?= $log['id'] ?>');
                                alertBox.className = 'alert alert-warning py-2 px-3 small mt-2 mb-0';
                                alertBox.innerHTML = '<i class="bi bi-info-circle me-1"></i> Jam pulang dikosongkan & status diubah ke <strong>Hadir (Aktif)</strong>. Klik <strong>Simpan Koreksi</strong> untuk membuka kembali akses transaksi kasir di POS.';
                                alertBox.classList.remove('d-none');
                            }
                            function setFinishedNow<?= $log['id'] ?>() {
                                const now = new Date();
                                const hh = String(now.getHours()).padStart(2, '0');
                                const mm = String(now.getMinutes()).padStart(2, '0');
                                document.getElementById('clockOut<?= $log['id'] ?>').value = `${hh}:${mm}`;
                                document.getElementById('statusSelect<?= $log['id'] ?>').value = 'SELESAI';
                                document.getElementById('actionType<?= $log['id'] ?>').value = 'finish_now';
                                const alertBox = document.getElementById('quickActionAlert<?= $log['id'] ?>');
                                alertBox.className = 'alert alert-success py-2 px-3 small mt-2 mb-0';
                                alertBox.innerHTML = `<i class="bi bi-check-circle me-1"></i> Jam pulang diatur ke waktu sekarang (<strong>${hh}:${mm}</strong>) & status <strong>Selesai</strong>. Klik <strong>Simpan Koreksi</strong>.`;
                                alertBox.classList.remove('d-none');
                            }
                            </script>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Attendance Modal (Owner Only) -->
<?php if (is_owner()): ?>
<div class="modal fade" id="manualAttendanceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= url('/attendance/manual') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header border-bottom py-3">
                    <h5 class="modal-title fw-bold">Input Absensi Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Pilih Pegawai <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['role']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Jam Masuk</label>
                            <input type="time" name="clock_in" class="form-control" value="08:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Jam Pulang</label>
                            <input type="time" name="clock_out" class="form-control" value="16:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Status Kehadiran</label>
                        <select name="status" class="form-select">
                            <option value="HADIR" selected>Hadir</option>
                            <option value="IZIN">Izin</option>
                            <option value="SAKIT">Sakit</option>
                            <option value="ALPHA">Alpha</option>
                            <option value="LIBUR">Libur</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Catatan / Keterangan</label>
                        <input type="text" name="note" class="form-control" placeholder="Contoh: Pegawai lupa clock out">
                    </div>
                </div>
                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-wk-primary btn-sm px-3">Simpan Absensi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
