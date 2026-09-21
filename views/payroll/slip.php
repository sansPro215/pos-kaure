<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Slip Gaji Pegawai') ?></title>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f1f5f9;
            font-family: 'Inter', -apple-system, sans-serif;
            color: #1e293b;
            padding: 30px 15px;
        }
        .payslip-container {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        .header-title {
            letter-spacing: 0.5px;
        }
        .table-custom th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .table-custom td {
            font-size: 0.85rem;
        }
        .badge-status {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .signature-box {
            height: 80px;
        }
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .payslip-container {
                box-shadow: none !important;
                border: none !important;
                padding: 10px 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
            @page {
                margin: 1.5cm;
                size: A4 portrait;
            }
        }
    </style>
</head>
<body>

    <!-- Action Bar (Hidden on print) -->
    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-primary px-4 py-2 me-2 shadow-sm fw-bold">
            <i class="bi bi-printer me-1"></i> Cetak / Simpan PDF
        </button>
        <a href="<?= url('/payroll/' . $period['id']) ?>" class="btn btn-outline-secondary px-3 py-2 shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Periode
        </a>
    </div>

    <!-- Printable Payslip Document -->
    <div class="payslip-container">
        <!-- Company Header -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <h3 class="fw-bold text-primary mb-1"><?= strtoupper(e($settings['shop_name'] ?? 'WARUNG KAURE')) ?></h3>
                <?php if (!empty($settings['address'])): ?>
                    <div class="text-muted small"><?= nl2br(e($settings['address'])) ?></div>
                <?php endif; ?>
                <?php if (!empty($settings['phone'])): ?>
                    <div class="text-muted small"><i class="bi bi-telephone me-1"></i><?= e($settings['phone']) ?></div>
                <?php endif; ?>
            </div>
            <div class="text-end">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-6 px-3 py-1 mb-2 d-inline-block">
                    SLIP GAJI RESMI
                </span>
                <div class="text-muted small">No. Referensi: <strong>#PY-<?= str_pad((string)$payroll['id'], 5, '0', STR_PAD_LEFT) ?></strong></div>
                <div class="text-muted small">Dicetak pada: <?= date('d/m/Y H:i') ?></div>
            </div>
        </div>

        <!-- Employee & Period Information -->
        <div class="row g-3 p-3 rounded-3 bg-light border mb-4">
            <div class="col-12 col-md-6">
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 120px;">Nama Pegawai</span>
                    <span class="fw-bold small">: <?= e($payroll['user_name']) ?></span>
                </div>
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 120px;">Username / ID</span>
                    <span class="small">: @<?= e($payroll['username']) ?></span>
                </div>
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 120px;">Role / Jabatan</span>
                    <span class="small">: 
                        <?php if ($payroll['role'] === 'OWNER'): ?>
                            <span class="badge bg-danger-subtle text-danger border">OWNER / MANAGER</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary border">KASIR / OPERASIONAL</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="d-flex">
                    <span class="text-muted small" style="width: 120px;">No. Kontak</span>
                    <span class="small">: <?= e($payroll['phone'] ?: '—') ?></span>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 140px;">Nama Periode</span>
                    <span class="fw-semibold small">: <?= e($period['name']) ?></span>
                </div>
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 140px;">Rentang Periode</span>
                    <span class="small">: <?= date('d/m/Y', strtotime($period['start_date'])) ?> s/d <?= date('d/m/Y', strtotime($period['end_date'])) ?></span>
                </div>
                <div class="d-flex mb-1">
                    <span class="text-muted small" style="width: 140px;">Status Pembayaran</span>
                    <span class="small">: 
                        <?php if ($payroll['payment_status'] === 'PAID'): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">LUNAS (PAID)</span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-warning text-dark border">BELUM DIBAYAR</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="d-flex">
                    <span class="text-muted small" style="width: 140px;">Metode Pembayaran</span>
                    <span class="small">: <?= e($payroll['payment_method'] ?: 'Transfer Bank') ?></span>
                </div>
            </div>
        </div>

        <!-- Section 1: Detailed Attendance Records during Period -->
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <h6 class="fw-bold m-0 text-dark">
                    <i class="bi bi-calendar-check text-primary me-1"></i> Rincian Rekapitulasi Absensi Harian Pegawai
                </h6>
                <span class="badge bg-light text-muted border"><?= count($attendances) ?> Hari Tercatat</span>
            </div>

            <div class="table-responsive border rounded-3 overflow-hidden">
                <table class="table table-bordered table-striped table-hover table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 45px;">No</th>
                            <th>Tanggal</th>
                            <th class="text-center">Jam Masuk</th>
                            <th class="text-center">Jam Pulang</th>
                            <th class="text-center">Reguler</th>
                            <th class="text-center">Lembur</th>
                            <th class="text-center">Status</th>
                            <th>Catatan / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalRegMin = 0;
                        $totalOtMin = 0;
                        $presentDays = 0;
                        if (!empty($attendances)):
                            foreach ($attendances as $idx => $att): 
                                $totalRegMin += (int)$att['regular_minutes'];
                                $totalOtMin += (int)$att['overtime_minutes'];
                                if (in_array($att['status'], ['HADIR', 'SELESAI'])) $presentDays++;
                                $dayName = date('l', strtotime($att['date']));
                                $daysId = [
                                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                                ];
                        ?>
                            <tr>
                                <td class="text-center text-muted"><?= $idx + 1 ?></td>
                                <td class="fw-semibold">
                                    <?= date('d/m/Y', strtotime($att['date'])) ?>
                                    <span class="text-muted small fw-normal">(<?= $daysId[$dayName] ?? $dayName ?>)</span>
                                </td>
                                <td class="text-center font-monospace small"><?= e($att['clock_in'] ?: '—') ?></td>
                                <td class="text-center font-monospace small"><?= e($att['clock_out'] ?: '—') ?></td>
                                <td class="text-center fw-semibold"><?= round($att['regular_minutes'] / 60, 2) ?> jam</td>
                                <td class="text-center">
                                    <?php if ($att['overtime_minutes'] >= 60): ?>
                                        <span class="text-warning text-dark fw-bold">+<?= (int)floor($att['overtime_minutes'] / 60) ?> jam</span>
                                    <?php else: ?>
                                        <span class="text-muted">0 jam</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if (in_array($att['status'], ['HADIR', 'SELESAI'])): ?>
                                        <span class="badge bg-success-subtle text-success">Hadir</span>
                                    <?php elseif ($att['status'] === 'IZIN'): ?>
                                        <span class="badge bg-info-subtle text-info">Izin</span>
                                    <?php elseif ($att['status'] === 'SAKIT'): ?>
                                        <span class="badge bg-warning-subtle text-warning">Sakit</span>
                                    <?php elseif ($att['status'] === 'ALPHA'): ?>
                                        <span class="badge bg-danger-subtle text-danger">Alpha</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-subtle text-secondary"><?= e($att['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= e($att['note'] ?: '—') ?></td>
                            </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                            <tr>
                                <td colspan="8" class="text-center py-3 text-muted">
                                    <em>Tidak ada rekaman absensi pada rentang tanggal periode ini.</em>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAL REKAPITULASI:</td>
                            <td class="text-center"><?= round($totalRegMin / 60, 2) ?> jam</td>
                            <td class="text-center text-warning text-dark"><?= round($totalOtMin / 60, 2) ?> jam</td>
                            <td colspan="2" class="text-center text-success"><?= $presentDays ?> Hari Masuk Kerja</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Section 2: Salary Financial Breakdown -->
        <div class="mb-4">
            <h6 class="fw-bold mb-2 text-dark">
                <i class="bi bi-wallet2 text-success me-1"></i> Rincian Penghitungan Gaji (Take Home Pay)
            </h6>

            <div class="border rounded-3 p-3 bg-light">
                <div class="row g-3">
                    <div class="col-12 col-md-7 border-end">
                        <div class="fw-bold small text-muted text-uppercase mb-2">Komponen Penerimaan Gaji:</div>
                        
                        <?php if (($payroll['role'] ?? '') === 'OWNER'): ?>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span>Gaji Pokok / Intensif Manager:</span>
                                <span class="fw-bold font-monospace"><?= format_rupiah($payroll['regular_pay']) ?></span>
                            </div>
                        <?php else: ?>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <div>
                                    <span>Gaji Pokok Reguler:</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        <?= (float)$payroll['regular_hours'] ?> jam × <?= format_rupiah($payroll['hourly_rate_snapshot']) ?>/jam
                                    </div>
                                </div>
                                <span class="fw-bold font-monospace"><?= format_rupiah($payroll['regular_pay']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <div>
                                    <span>Gaji Lembur:</span>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        <?= (float)$payroll['overtime_hours'] ?> jam × <?= format_rupiah($payroll['overtime_rate_snapshot']) ?>/jam
                                    </div>
                                </div>
                                <span class="fw-bold font-monospace text-warning text-dark"><?= format_rupiah($payroll['overtime_pay']) ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ((float)$payroll['bonus'] > 0): ?>
                            <div class="d-flex justify-content-between py-1 border-bottom small text-success">
                                <span>Bonus / Insentif Tambahan:</span>
                                <span class="fw-bold font-monospace">+ <?= format_rupiah($payroll['bonus']) ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between py-1 fw-bold small text-dark">
                            <span>Total Penghasilan Kotor (Gross):</span>
                            <span class="font-monospace"><?= format_rupiah($payroll['gross_salary']) ?></span>
                        </div>
                    </div>

                    <div class="col-12 col-md-5 d-flex flex-column justify-content-between">
                        <div>
                            <div class="fw-bold small text-muted text-uppercase mb-2">Komponen Potongan:</div>
                            <div class="d-flex justify-content-between py-1 border-bottom small text-danger">
                                <span>Potongan / Kasbon / Denda:</span>
                                <span class="fw-bold font-monospace">- <?= format_rupiah($payroll['deduction']) ?></span>
                            </div>
                            <?php if (!empty($payroll['notes'])): ?>
                                <div class="mt-2 small text-muted">
                                    <em>Catatan: <?= e($payroll['notes']) ?></em>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="p-3 bg-white border rounded-3 mt-3">
                            <span class="text-muted small d-block">TOTAL GAJI BERSIH DITERIMA:</span>
                            <h3 class="fw-bold text-success mb-0 font-monospace"><?= format_rupiah($payroll['net_salary']) ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Signatures -->
        <div class="row pt-4 mt-2">
            <div class="col-6 text-center">
                <div class="small text-muted mb-1">Dibuat & Disetujui Oleh,</div>
                <div class="fw-bold small mb-1">Manajemen Kedai / Owner</div>
                <div class="signature-box border-bottom mx-auto" style="width: 180px;"></div>
                <div class="small text-muted mt-1">( <?= e($payroll['paid_by_name'] ?? 'Owner Kedai') ?> )</div>
            </div>
            <div class="col-6 text-center">
                <div class="small text-muted mb-1">Diterima Dengan Baik Oleh,</div>
                <div class="fw-bold small mb-1">Pegawai Bersangkutan</div>
                <div class="signature-box border-bottom mx-auto" style="width: 180px;"></div>
                <div class="small text-muted mt-1">( <?= e($payroll['user_name']) ?> )</div>
            </div>
        </div>

        <!-- Footer note -->
        <div class="text-center text-muted border-top pt-3 mt-4" style="font-size: 0.72rem;">
            Dokumen ini dihasilkan secara otomatis oleh Sistem Kasir & Manajemen <?= e($settings['shop_name'] ?? 'Warung Kaure') ?> dan merupakan bukti resmi penggajian pegawai.
        </div>
    </div>

</body>
</html>
