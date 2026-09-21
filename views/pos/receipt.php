<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk #<?= e($transaction['transaction_code']) ?> — <?= e(shop_name()) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= asset('/assets/css/app.css') ?>" rel="stylesheet">
    <?= render_theme_css() ?>
    
    <style>
        body {
            background-color: var(--wk-bg, #f4f4f4);
            font-family: 'Inter', -apple-system, sans-serif;
            padding: 24px 10px;
            color: var(--wk-text, #1e293b);
        }
        .receipt-container {
            max-width: <?= $settings['receipt_width'] === '58' ? '280px' : ($settings['receipt_width'] === '80' ? '360px' : '380px') ?>;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px 18px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-top: 5px solid var(--wk-primary, #6F4E37);
            border-radius: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 13px;
            color: #000000;
        }
        .receipt-shop-name {
            color: var(--wk-primary, #6F4E37);
            font-weight: 800;
            letter-spacing: 1.2px;
        }
        .receipt-total-row {
            color: var(--wk-primary, #6F4E37);
            font-weight: 800;
            font-size: 14px;
        }
        .receipt-divider {
            border-top: 1px dashed rgba(var(--wk-primary-rgb, 0,0,0), 0.35);
            margin: 8px 0;
        }
        .btn-wk-primary {
            background-color: var(--wk-primary, #6F4E37);
            border-color: var(--wk-primary, #6F4E37);
            color: #ffffff;
        }
        .btn-wk-primary:hover, .btn-wk-primary:focus {
            background-color: var(--wk-primary-dark, #533A29);
            border-color: var(--wk-primary-dark, #533A29);
            color: #ffffff;
        }

        @page {
            margin: 0;
            size: <?= $settings['receipt_width'] === '58' ? '58mm auto' : ($settings['receipt_width'] === '80' ? '80mm auto' : 'auto') ?>;
        }

        @media print {
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #ffffff !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border-top: none !important;
                border-radius: 0 !important;
                padding: <?= $settings['receipt_width'] === '58' ? '2mm 1mm' : '4mm 2mm' ?> !important;
                margin: 0 auto !important;
                max-width: 100% !important;
                width: <?= $settings['receipt_width'] === '58' ? '58mm' : ($settings['receipt_width'] === '80' ? '80mm' : '100%') ?> !important;
                color: #000000 !important;
                font-size: <?= $settings['receipt_width'] === '58' ? '11px' : '12px' ?> !important;
            }
            .receipt-shop-name,
            .receipt-total-row,
            .receipt-container * {
                color: #000000 !important;
            }
            .receipt-divider {
                border-top: 1px dashed #000000 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- Top Action Buttons (Hidden when printing) -->
    <div class="text-center mb-4 no-print">
        <button onclick="window.print()" class="btn btn-wk-primary btn-sm px-4 py-2 me-2 shadow-sm fw-semibold">
            <i class="bi bi-printer me-1"></i> Cetak Struk
        </button>
        <a href="<?= url('/pos') ?>" class="btn btn-outline-secondary btn-sm px-3 py-2 shadow-sm">
            <i class="bi bi-cart3 me-1"></i> Transaksi Baru
        </a>
    </div>

    <!-- Printable Receipt Box -->
    <div class="receipt-container receipt-print-area">
        <div class="text-center mb-2">
            <h5 class="receipt-shop-name m-0"><?= strtoupper(e($settings['shop_name'])) ?></h5>
            <?php if (!empty($settings['address'])): ?>
                <div style="font-size: 11px;"><?= nl2br(e($settings['address'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($settings['phone'])): ?>
                <div style="font-size: 11px;">Telp: <?= e($settings['phone']) ?></div>
            <?php endif; ?>
        </div>

        <?php if ($isReprint): ?>
            <div class="text-center fw-bold my-1" style="font-size: 13px; letter-spacing: 2px;">
                *** REPRINT ***
            </div>
        <?php endif; ?>

        <?php if ($transaction['status'] === 'VOID'): ?>
            <div class="text-center fw-bold my-1 text-danger" style="font-size: 13px; letter-spacing: 2px;">
                *** TRANSAKSI DIBATALKAN (VOID) ***
            </div>
        <?php elseif ($transaction['status'] === 'REFUNDED' || $transaction['status'] === 'PARTIAL_REFUND'): ?>
            <div class="text-center fw-bold my-1 text-warning" style="font-size: 13px; letter-spacing: 2px;">
                *** <?= $transaction['status'] ?> ***
            </div>
        <?php endif; ?>

        <div class="receipt-divider"></div>

        <div style="font-size: 12px;" class="mb-2">
            <div>No. Transaksi : <?= e($transaction['transaction_code']) ?></div>
            <div>Tanggal        : <?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></div>
            <div>Kasir          : <?= e($transaction['cashier_name'] ?? 'Kasir') ?></div>
        </div>

        <div class="receipt-divider"></div>

        <!-- Items Table -->
        <table style="width: 100%; font-size: 12px;" class="mb-2">
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td colspan="2" class="fw-bold pb-0"><?= e($item['product_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding-left: 10px;">
                            <?= $item['qty'] ?> x <?= number_format($item['selling_price'], 0, ',', '.') ?>
                            <?php if ($item['refunded_qty'] > 0): ?>
                                <span class="text-danger">(Refund: <?= $item['refunded_qty'] ?>)</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end fw-bold"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="receipt-divider"></div>

        <!-- Financial Totals -->
        <table style="width: 100%; font-size: 12px;">
            <tr>
                <td>Subtotal</td>
                <td class="text-end"><?= number_format($transaction['subtotal'], 0, ',', '.') ?></td>
            </tr>
            <?php if ($transaction['discount_amount'] > 0): ?>
                <tr>
                    <td>Diskon <?= $transaction['discount_type'] === 'PERCENT' ? '(' . (float)$transaction['discount_value'] . '%)' : '' ?></td>
                    <td class="text-end">-<?= number_format($transaction['discount_amount'], 0, ',', '.') ?></td>
                </tr>
            <?php endif; ?>
            <tr class="receipt-total-row">
                <td>TOTAL</td>
                <td class="text-end"><?= number_format($transaction['grand_total'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Pembayaran (<?= e($transaction['payment_method']) ?>)</td>
                <td class="text-end"><?= number_format($transaction['paid_amount'], 0, ',', '.') ?></td>
            </tr>
            <tr>
                <td>Kembalian</td>
                <td class="text-end"><?= number_format($transaction['change_amount'], 0, ',', '.') ?></td>
            </tr>
        </table>

        <div class="receipt-divider"></div>

        <!-- Footer -->
        <div class="text-center mt-2" style="font-size: 11px;">
            <?php if (!empty($settings['receipt_footer'])): ?>
                <div><?= nl2br(e($settings['receipt_footer'])) ?></div>
            <?php else: ?>
                <div>Terima kasih atas kunjungan Anda!</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
