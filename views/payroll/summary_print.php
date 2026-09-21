<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Rekapitulasi Penggajian') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --primary-subtle: #eef2ff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-light: #f8fafc;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--text-dark);
            background: #f1f5f9;
            padding: 24px;
            font-size: 13px;
            line-height: 1.5;
        }

        .no-print-bar {
            max-width: 1000px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px -3px rgba(0,0,0,0.07);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: all 0.2s;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: var(--text-dark);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #e2e8f0;
        }

        .report-sheet {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            padding: 36px 40px;
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01);
            border: 1px solid var(--border);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--border);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .shop-info h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .shop-info p {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 2px;
        }

        .report-title-badge {
            text-align: right;
        }

        .report-title-badge h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: var(--text-dark);
            text-transform: uppercase;
        }

        .badge-period {
            display: inline-block;
            margin-top: 6px;
            padding: 4px 12px;
            background: var(--primary-subtle);
            color: var(--primary);
            font-weight: 600;
            font-size: 12px;
            border-radius: 20px;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            background: var(--bg-light);
            padding: 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
        }

        .meta-item .label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .meta-item .val {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-dark);
        }

        .summary-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: #ffffff;
            border: 1px solid var(--border);
            padding: 14px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .kpi-card.success {
            border-left-color: var(--success);
        }

        .kpi-card.warning {
            border-left-color: var(--warning);
        }

        .kpi-card .kpi-label {
            font-size: 11px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
        }

        .kpi-card .kpi-val {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-dark);
            margin-top: 4px;
            font-family: 'Outfit', sans-serif;
        }

        table.report-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            font-size: 12px;
        }

        table.report-table th, 
        table.report-table td {
            padding: 10px 12px;
            border-bottom: 1px solid var(--border);
            text-align: left;
        }

        table.report-table th {
            background: var(--bg-light);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-top: 1px solid var(--border);
        }

        table.report-table tbody tr:hover {
            background: rgba(241, 245, 249, 0.4);
        }

        .text-right { text-align: right !important; }
        .text-center { text-align: center !important; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        .fw-bold { font-weight: 700; }
        .fw-semibold { font-weight: 600; }
        .text-success { color: var(--success) !important; }
        .text-danger { color: var(--danger) !important; }

        .badge-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
        }
        .badge-paid {
            background: #dcfce7;
            color: #15803d;
        }
        .badge-unpaid {
            background: #fef3c7;
            color: #b45309;
        }

        .signatures {
            margin-top: 36px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            page-break-inside: avoid;
        }

        .sign-col {
            text-align: center;
        }

        .sign-col .sign-title {
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 60px;
        }

        .sign-col .sign-name {
            font-weight: 700;
            font-size: 13px;
            text-decoration: underline;
        }

        .sign-col .sign-role {
            font-size: 11px;
            color: var(--text-muted);
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .report-sheet {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
            @page {
                size: A4 landscape;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <div class="no-print-bar">
        <div style="font-weight: 600; color: var(--text-dark);">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Rekapitulasi Penggajian Pegawai
        </div>
        <div style="display: flex; gap: 8px;">
            <button class="btn-action btn-secondary" onclick="window.close()">
                <i class="bi bi-x-lg"></i> Tutup
            </button>
            <button class="btn-action btn-primary" onclick="window.print()">
                <i class="bi bi-printer"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <div class="report-sheet">
        <!-- Header -->
        <div class="report-header">
            <div class="shop-info">
                <h1><?= e($settings['shop_name'] ?? 'WARUNG KAURE') ?></h1>
                <p><i class="bi bi-geo-alt"></i> <?= e($settings['shop_address'] ?? 'Alamat Outlet') ?></p>
                <p><i class="bi bi-telephone"></i> <?= e($settings['shop_phone'] ?? '-') ?></p>
            </div>
            <div class="report-title-badge">
                <h2>Laporan Penggajian Pegawai</h2>
                <div class="badge-period">
                    <i class="bi bi-calendar3"></i> <?= e($period['name']) ?>
                </div>
            </div>
        </div>

        <!-- Meta Info -->
        <div class="meta-grid">
            <div class="meta-item">
                <div class="label">Rentang Waktu</div>
                <div class="val"><?= date('d M Y', strtotime($period['start_date'])) ?> &mdash; <?= date('d M Y', strtotime($period['end_date'])) ?></div>
            </div>
            <div class="meta-item">
                <div class="label">Status Periode</div>
                <div class="val"><?= $period['is_closed'] ? 'Ditutup (Final)' : 'Aktif (Terbuka)' ?></div>
            </div>
            <div class="meta-item">
                <div class="label">Tanggal Cetak</div>
                <div class="val"><?= date('d/m/Y H:i') ?> WIB</div>
            </div>
            <div class="meta-item">
                <div class="label">Total Pegawai Terdaftar</div>
                <div class="val"><?= count($payrolls) ?> Orang</div>
            </div>
        </div>

        <?php
            $totRegularHours = 0;
            $totOvertimeHours = 0;
            $totRegularPay = 0;
            $totOvertimePay = 0;
            $totBonus = 0;
            $totDeduction = 0;
            $totNetSalary = 0;

            foreach ($payrolls as $row) {
                $totRegularHours += (float)$row['regular_hours'];
                $totOvertimeHours += (float)$row['overtime_hours'];
                $totRegularPay += (float)$row['regular_pay'];
                $totOvertimePay += (float)$row['overtime_pay'];
                $totBonus += (float)$row['bonus'];
                $totDeduction += (float)$row['deduction'];
                $totNetSalary += (float)$row['net_salary'];
            }
        ?>

        <!-- KPI Cards -->
        <div class="summary-kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Total Jam Reguler</div>
                <div class="kpi-val"><?= number_format($totRegularHours, 1) ?> Jam</div>
            </div>
            <div class="kpi-card warning">
                <div class="kpi-label">Total Jam Lembur</div>
                <div class="kpi-val"><?= number_format($totOvertimeHours, 1) ?> Jam</div>
            </div>
            <div class="kpi-card">
                <div class="kpi-label">Bonus / Potongan</div>
                <div class="kpi-val" style="font-size: 15px;">
                    <span class="text-success">+<?= format_rupiah($totBonus) ?></span> / 
                    <span class="text-danger">-<?= format_rupiah($totDeduction) ?></span>
                </div>
            </div>
            <div class="kpi-card success">
                <div class="kpi-label">Total Pengeluaran Gaji</div>
                <div class="kpi-val text-success"><?= format_rupiah($totNetSalary) ?></div>
            </div>
        </div>

        <!-- Detail Table -->
        <table class="report-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 35px;">No</th>
                    <th>Pegawai</th>
                    <th class="text-center">Jam Kerja</th>
                    <th class="text-right">Gaji Pokok</th>
                    <th class="text-right">Lembur</th>
                    <th class="text-right">Bonus</th>
                    <th class="text-right">Potongan</th>
                    <th class="text-right">Total Bersih</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payrolls)): ?>
                    <tr>
                        <td colspan="9" class="text-center" style="padding: 24px; color: var(--text-muted);">
                            Belum ada rincian data slip gaji pada periode ini.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($payrolls as $py): 
                        $isOwner = ($py['role'] ?? '') === 'OWNER';
                    ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td>
                                <span class="fw-bold"><?= e($py['user_name']) ?></span>
                                <?php if ($isOwner): ?>
                                    <span style="font-size: 10px; background: #e0e7ff; color: #4338ca; padding: 1px 6px; border-radius: 4px; font-weight: 600;">Owner</span>
                                <?php endif; ?>
                                <br>
                                <small style="color: var(--text-muted); font-size: 11px;">
                                    <?php if ($isOwner): ?>
                                        Intensif Manajemen
                                    <?php else: ?>
                                        Tarif: <?= format_rupiah($py['hourly_rate_snapshot']) ?>/j
                                    <?php endif; ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php if ($isOwner): ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php else: ?>
                                    <span class="font-mono"><?= (float)$py['regular_hours'] ?>j</span>
                                    <?php if ((float)$py['overtime_hours'] > 0): ?>
                                        <br><span class="font-mono text-warning">+<?= (float)$py['overtime_hours'] ?>j lembur</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-right font-mono"><?= format_rupiah($py['regular_pay']) ?></td>
                            <td class="text-right font-mono"><?= format_rupiah($py['overtime_pay']) ?></td>
                            <td class="text-right font-mono <?= (float)$py['bonus'] > 0 ? 'text-success' : '' ?>">
                                <?= (float)$py['bonus'] > 0 ? '+' : '' ?><?= format_rupiah($py['bonus']) ?>
                            </td>
                            <td class="text-right font-mono <?= (float)$py['deduction'] > 0 ? 'text-danger' : '' ?>">
                                <?= (float)$py['deduction'] > 0 ? '-' : '' ?><?= format_rupiah($py['deduction']) ?>
                            </td>
                            <td class="text-right font-mono fw-bold" style="color: var(--primary);">
                                <?= format_rupiah($py['net_salary']) ?>
                            </td>
                            <td class="text-center">
                                <?php if ($py['payment_status'] === 'PAID'): ?>
                                    <span class="badge-status badge-paid">LUNAS</span><br>
                                    <small style="font-size: 10px; color: var(--text-muted);"><?= e($py['payment_method']) ?></small>
                                <?php else: ?>
                                    <span class="badge-status badge-unpaid">BELUM</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: var(--bg-light); border-top: 2px solid var(--border);">
                    <td colspan="2" class="fw-bold text-uppercase" style="letter-spacing: 0.05em;">Total Keseluruhan</td>
                    <td class="text-center font-mono fw-bold"><?= number_format($totRegularHours + $totOvertimeHours, 1) ?> Jam</td>
                    <td class="text-right font-mono fw-bold"><?= format_rupiah($totRegularPay) ?></td>
                    <td class="text-right font-mono fw-bold"><?= format_rupiah($totOvertimePay) ?></td>
                    <td class="text-right font-mono fw-bold text-success">+<?= format_rupiah($totBonus) ?></td>
                    <td class="text-right font-mono fw-bold text-danger">-<?= format_rupiah($totDeduction) ?></td>
                    <td class="text-right font-mono fw-bold fs-6" style="color: var(--primary); font-size: 14px;">
                        <?= format_rupiah($totNetSalary) ?>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- Signatures Section -->
        <div class="signatures">
            <div class="sign-col">
                <div class="sign-title">Dibuat Oleh (Admin / Kasir),</div>
                <div class="sign-name"><?= e(auth_user()['name'] ?? 'Petugas') ?></div>
                <div class="sign-role"><?= e(auth_user()['role'] ?? 'Staff') ?></div>
            </div>
            <div class="sign-col">
                <div class="sign-title">Mengetahui & Menyetujui,</div>
                <div class="sign-name">Pemilik / Owner</div>
                <div class="sign-role"><?= e($settings['shop_name'] ?? 'Kedai') ?></div>
            </div>
        </div>
    </div>

</body>
</html>
