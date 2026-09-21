<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Laporan Ringkasan Dashboard') ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --bg-light: #f8fafc;
            --success: #16a34a;
            --danger: #dc2626;
            --warning: #d97706;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', system-ui, sans-serif;
            color: var(--text-dark);
            background: #f1f5f9;
            padding: 16px;
            font-size: 11px;
            line-height: 1.4;
        }

        .no-print-bar {
            max-width: 980px;
            margin: 0 auto 12px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            border: none;
        }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-secondary { background: #f1f5f9; color: var(--text-dark); border: 1px solid var(--border); }

        .report-container {
            max-width: 980px;
            margin: 0 auto;
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        /* Letterhead */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border);
            margin-bottom: 12px;
        }

        .shop-name {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 700;
            color: #1e1b4b;
            letter-spacing: -0.3px;
            margin-bottom: 2px;
        }

        .report-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 2px;
        }

        .shop-meta { font-size: 10px; color: var(--text-muted); }

        .header-meta {
            text-align: right;
            font-size: 10px;
            color: var(--text-muted);
            line-height: 1.5;
        }
        .header-meta strong { color: var(--text-dark); }

        /* KPI Cards */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-bottom: 10px;
        }

        .kpi-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 10px;
            border-left: 3px solid var(--primary);
        }
        .kpi-card.green { border-left-color: var(--success); }
        .kpi-card.blue  { border-left-color: #0284c7; }
        .kpi-card.amber { border-left-color: var(--warning); }

        .kpi-label {
            font-size: 9.5px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 3px;
        }

        .kpi-value {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 2px;
        }

        .kpi-sub { font-size: 9.5px; color: var(--text-muted); }

        /* Payment breakdown bar */
        .pay-bar {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .pay-bar-divider { height: 24px; width: 1px; background: var(--border); }

        /* Section Title */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* 2-col grid */
        .two-col-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 10px;
        }

        /* Tables */
        table { width: 100%; border-collapse: collapse; font-size: 10.5px; margin-bottom: 8px; }
        th, td { padding: 5px 8px; border: 1px solid var(--border); vertical-align: middle; }
        th {
            background: var(--bg-light);
            font-weight: 600;
            color: var(--text-dark);
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .text-end    { text-align: right; }
        .text-center { text-align: center; }
        .font-mono   { font-family: 'JetBrains Mono', monospace; }
        .fw-bold     { font-weight: 700; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 1px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: 600;
            border: 1px solid transparent;
        }
        .badge-success   { background: #dcfce7; color: #166534; border-color: #bbf7d0; }
        .badge-primary   { background: #e0e7ff; color: #3730a3; border-color: #c7d2fe; }
        .badge-warning   { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .badge-danger    { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .badge-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }

        /* Attendance mini-cards */
        .att-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }
        .att-card {
            padding: 6px 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            text-align: center;
        }

        /* Signatures */
        .signature-section {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            text-align: center;
            page-break-inside: avoid;
        }

        .sig-box { display: flex; flex-direction: column; align-items: center; }
        .sig-title { font-size: 10px; color: var(--text-muted); margin-bottom: 40px; }
        .sig-name {
            font-weight: 700;
            border-bottom: 1px solid var(--text-dark);
            padding-bottom: 2px;
            min-width: 160px;
        }
        .sig-role { font-size: 9.5px; color: var(--text-muted); margin-top: 3px; }

        .footer-note {
            margin-top: 10px;
            text-align: center;
            font-size: 9.5px;
            color: var(--text-muted);
        }

        /* Attendance section wrapper */
        .att-section { margin-bottom: 10px; }

        /* Print */
        @media print {
            body { background: #fff !important; padding: 0 !important; font-size: 10.5px; }
            .no-print-bar { display: none !important; }
            .report-container { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100% !important; }
            @page { size: A4 portrait; margin: 10mm 12mm; }
            .kpi-card, table, .signature-section, .two-col-grid { break-inside: avoid; page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Screen Only) -->
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <a href="<?= url('/dashboard') ?>" class="btn-action btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
            </a>
            <span style="color: var(--text-muted); font-size: 12px;">
                <i class="bi bi-info-circle me-1"></i> Format resmi cetak laporan operasional & keuangan harian.
            </span>
        </div>
        <button class="btn-action btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Cetak Dokumen (Print)
        </button>
    </div>

    <!-- Main Report Body -->
    <div class="report-container">
        <!-- Header -->
        <div class="report-header">
            <div>
                <div class="shop-name"><?= e(shop_name()) ?></div>
                <div class="report-title">Laporan Ringkasan Operasional & Keuangan (Dashboard)</div>
                <div class="shop-meta">
                    <?= e(shop_setting('address') ?: 'Alamat Kedai') ?> • Telp: <?= e(shop_setting('phone') ?: '-') ?>
                </div>
            </div>
            <div class="header-meta">
                <div><strong>Tanggal Laporan:</strong> <?= date('d F Y') ?></div>
                <div><strong>Waktu Cetak:</strong> <?= date('H:i') ?> WIB</div>
                <div><strong>Dicetak Oleh:</strong> <?= e(auth_user()['name'] ?? 'Owner') ?> (<?= e(auth_user()['role'] ?? 'OWNER') ?>)</div>
            </div>
        </div>

        <!-- 4 KPI Summary Cards -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-label">Omzet Hari Ini</div>
                <div class="kpi-value"><?= format_rupiah($summary['net_sales']) ?></div>
                <div class="kpi-sub"><?= $summary['total_transactions'] ?> Transaksi • <?= $summary['items_sold'] ?> Item</div>
            </div>
            <div class="kpi-card green">
                <div class="kpi-label">Laba Kotor Kedai</div>
                <div class="kpi-value" style="color: var(--success);"><?= format_rupiah($summary['gross_profit']) ?></div>
                <div class="kpi-sub">Beban: <?= format_rupiah($summary['total_expenses']) ?></div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-label">Gaji Owner (<?= (float)($summary['manager_incentive_percent'] ?? 20) ?>%)</div>
                <div class="kpi-value" style="color: #0284c7;"><?= format_rupiah($summary['manager_incentive']) ?></div>
                <div class="kpi-sub">Intensif Manager Kedai</div>
            </div>
            <div class="kpi-card amber">
                <div class="kpi-label">Pemegang Saham (<?= (float)($summary['shareholder_percent'] ?? 80) ?>%)</div>
                <div class="kpi-value" style="color: var(--warning);"><?= format_rupiah($summary['shareholder_dividend']) ?></div>
                <div class="kpi-sub">Alokasi Dividen Pemodal</div>
            </div>
        </div>

        <!-- Payment Breakdown Bar -->
        <div class="pay-bar">
            <div style="text-align:center">
                <div class="kpi-label">Penjualan Tunai (Cash)</div>
                <div class="kpi-value"><?= format_rupiah($summary['cash_sales']) ?></div>
            </div>
            <div class="pay-bar-divider"></div>
            <div style="text-align:center">
                <div class="kpi-label">Non-Tunai (QRIS / Transfer)</div>
                <div class="kpi-value" style="color:var(--primary)"><?= format_rupiah($summary['cashless_sales']) ?></div>
            </div>
            <div class="pay-bar-divider"></div>
            <div style="text-align:center">
                <div class="kpi-label">Beban Operasional & Gaji</div>
                <div class="kpi-value" style="color:var(--danger)"><?= format_rupiah($summary['total_expenses']) ?></div>
            </div>
        </div>

        <!-- 2 Columns: Top Products & Recent Transactions -->
        <div class="two-col-grid">
            <!-- Left: Top Products -->
            <div>
                <div class="section-title">
                    <i class="bi bi-star-fill text-warning"></i> Produk Terlaris Hari Ini
                </div>
                <?php if (empty($topProducts)): ?>
                    <p style="color: var(--text-muted); font-size: 11px; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">Belum ada penjualan tercatat hari ini.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 35px;">#</th>
                                <th>Produk / Menu</th>
                                <th class="text-center" style="width: 50px;">Qty</th>
                                <th class="text-end" style="width: 90px;">Omzet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $idx => $p): ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $idx + 1 ?></td>
                                    <td class="fw-bold"><?= e($p['product_name']) ?></td>
                                    <td class="text-center font-mono"><?= (int)$p['total_qty'] ?></td>
                                    <td class="text-end font-mono"><?= format_rupiah($p['total_omzet']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Right: Recent Transactions -->
            <div>
                <div class="section-title">
                    <i class="bi bi-clock-history text-primary"></i> Transaksi Penjualan Terkini
                </div>
                <?php if (empty($recentTransactions)): ?>
                    <p style="color: var(--text-muted); font-size: 11px; padding: 10px; border: 1px solid var(--border); border-radius: 6px;">Belum ada transaksi hari ini.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Waktu</th>
                                <th class="text-center">Metode</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($recentTransactions, 0, 10) as $t): ?>
                                <tr>
                                    <td class="font-mono fw-bold"><?= e($t['transaction_code']) ?></td>
                                    <td style="color: var(--text-muted);"><?= date('H:i', strtotime($t['transaction_date'])) ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-secondary"><?= e($t['payment_method']) ?></span>
                                    </td>
                                    <td class="text-end font-mono fw-bold"><?= format_rupiah($t['grand_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="att-section">
            <div class="section-title">
                <i class="bi bi-people-fill"></i> Kehadiran Pegawai Kasir Hari Ini (<?= date('d/m/Y') ?>)
            </div>
            <div class="att-grid">
                <div class="att-card" style="background:#f0fdf4">
                    <div class="kpi-label" style="color:var(--success)">Sedang Bekerja</div>
                    <div class="kpi-value" style="color:var(--success);font-size:16px"><?= $attendance['currently_working'] ?></div>
                </div>
                <div class="att-card" style="background:#eff6ff">
                    <div class="kpi-label" style="color:var(--primary)">Selesai Shift</div>
                    <div class="kpi-value" style="color:var(--primary);font-size:16px"><?= $attendance['finished_shift'] ?></div>
                </div>
                <div class="att-card" style="background:#fffbeb">
                    <div class="kpi-label" style="color:var(--warning)">Izin / Sakit</div>
                    <div class="kpi-value" style="color:var(--warning);font-size:16px"><?= $attendance['izin_count'] + $attendance['sakit_count'] ?></div>
                </div>
                <div class="att-card" style="background:#fef2f2">
                    <div class="kpi-label" style="color:var(--danger)">Alpha</div>
                    <div class="kpi-value" style="color:var(--danger);font-size:16px"><?= $attendance['alpha_count'] ?></div>
                </div>
            </div>

            <?php if (!empty($todayAttendanceList)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nama Pegawai</th>
                            <th class="text-center" style="width: 80px;">Role</th>
                            <th class="text-center" style="width: 80px;">Jam Masuk</th>
                            <th class="text-center" style="width: 80px;">Jam Pulang</th>
                            <th class="text-center" style="width: 90px;">Durasi Kerja</th>
                            <th class="text-center" style="width: 90px;">Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todayAttendanceList as $att): ?>
                            <tr>
                                <td class="fw-bold"><?= e($att['user_name']) ?></td>
                                <td class="text-center"><span class="badge badge-secondary"><?= e($att['role']) ?></span></td>
                                <td class="text-center font-mono"><?= !empty($att['clock_in']) ? date('H:i', strtotime($att['clock_in'])) : '—' ?></td>
                                <td class="text-center font-mono"><?= !empty($att['clock_out']) ? date('H:i', strtotime($att['clock_out'])) : 'Aktif' ?></td>
                                <td class="text-center font-mono"><?= !empty($att['worked_minutes']) ? format_minutes($att['worked_minutes']) : '—' ?></td>
                                <td class="text-center">
                                    <?php if ($att['status'] === 'SELESAI' || ($att['status'] === 'HADIR' && !empty($att['clock_out']))): ?>
                                        <span class="badge badge-primary">Selesai</span>
                                    <?php elseif ($att['status'] === 'HADIR'): ?>
                                        <span class="badge badge-success">Bekerja</span>
                                    <?php elseif ($att['status'] === 'IZIN' || $att['status'] === 'SAKIT'): ?>
                                        <span class="badge badge-warning"><?= e($att['status']) ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?= e($att['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-muted);"><?= e($att['note'] ?: '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Official Signatures -->
        <div class="signature-section">
            <div class="sig-box">
                <div class="sig-title">Penanggung Jawab Operasional / Kasir,</div>
                <div class="sig-name">______________________________</div>
                <div class="sig-role">( Kasir Bertugas )</div>
            </div>
            <div class="sig-box">
                <div class="sig-title">Mengetahui & Disetujui Oleh,</div>
                <div class="sig-name"><u><?= e(auth_user()['name'] ?? 'Owner') ?></u></div>
                <div class="sig-role">( Owner / Pengelola Kedai )</div>
            </div>
        </div>

        <div class="footer-note">
            Laporan ini dicetak secara otomatis dari Sistem POS <?= e(shop_name()) ?> pada <?= date('d/m/Y H:i:s') ?> WIB
        </div>
    </div>

</body>
</html>
