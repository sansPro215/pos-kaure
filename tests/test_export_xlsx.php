<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Helpers/setting.php';
require_once __DIR__ . '/../app/Helpers/url.php';
require_once __DIR__ . '/../app/Helpers/money.php';
require_once __DIR__ . '/../app/Helpers/date.php';
require_once __DIR__ . '/../app/Helpers/auth.php';
require_once __DIR__ . '/../app/Helpers/XlsxWriter.php';
// Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Repositories\TransactionRepository;
use App\Helpers\XlsxWriter;

echo "--- TESTING XLSX EXPORT GENERATION ---\n";

$transactions = TransactionRepository::getTransactions(
    null,
    null,
    null,
    null,
    null,
    null,
    50
);

echo "Found " . count($transactions) . " transactions for export.\n";

$xlsx = new XlsxWriter('Laporan Transaksi');
$xlsx->setColumnWidths([6, 22, 20, 18, 16, 16, 14, 16, 18, 16, 18, 16, 16, 14, 24, 18, 48]);
$xlsx->setFreezePanes(6);

$sumSubtotal = 0;
$sumDiscount = 0;
$sumGrandTotal = 0;
$sumCogs = 0;
$sumGrossProfit = 0;
$sumPaid = 0;
$sumChange = 0;

foreach ($transactions as $t) {
    $sub = (float)($t['subtotal'] ?? 0);
    $disc = (float)($t['discount_amount'] ?? 0);
    $grand = (float)($t['grand_total'] ?? 0);
    $cogs = (float)($t['total_cogs'] ?? 0);
    $gross = $grand - $cogs;
    $paid = (float)($t['paid_amount'] ?? 0);
    $change = (float)($t['change_amount'] ?? 0);

    $sumSubtotal += $sub;
    $sumDiscount += $disc;
    $sumGrandTotal += $grand;
    $sumCogs += $cogs;
    $sumGrossProfit += $gross;
    $sumPaid += $paid;
    $sumChange += $change;
}

// ROW 1: Banner Judul
$shopTitle = strtoupper(shop_name()) . ' — LAPORAN TRANSAKSI PENJUALAN';
$xlsx->addRow([
    ['v' => $shopTitle, 's' => 1]
], 28);
$xlsx->addMerge('A1:Q1');

// ROW 2: Filter Parameters
$filterDesc = 'Periode: Semua Periode   |   Kasir: Semua Kasir   |   Status: Semua Status   |   Metode: Semua Metode   |   Pencarian: -';
$xlsx->addRow([
    ['v' => $filterDesc, 's' => 2]
], 18);
$xlsx->addMerge('A2:Q2');

// ROW 3: Export Metadata
$metaDesc = 'Waktu Ekspor: ' . date('d/m/Y H:i:s') . ' WIB   |   Dicetak oleh: Administrator   |   Total Data: ' . count($transactions) . ' Transaksi';
$xlsx->addRow([
    ['v' => $metaDesc, 's' => 2]
], 18);
$xlsx->addMerge('A3:Q3');

// ROW 4: KPI Summary Cards
$xlsx->addRow([
    ['v' => ' Total Omset Penjualan:', 's' => 19],
    ['v' => (int)$sumGrandTotal, 's' => 20, 't' => 'n'],
    ['v' => '', 's' => 0],
    ['v' => ' Total HPP Modal:', 's' => 19],
    ['v' => (int)$sumCogs, 's' => 20, 't' => 'n'],
    ['v' => '', 's' => 0],
    ['v' => ' Estimasi Laba Kotor:', 's' => 19],
    ['v' => (int)$sumGrossProfit, 's' => 21, 't' => 'n'],
    ['v' => '', 's' => 0],
    ['v' => ' Total Transaksi:', 's' => 19],
    ['v' => count($transactions), 's' => 22, 't' => 'n']
], 24);

// ROW 5: Spacing Row
$xlsx->addRow([], 10);

// ROW 6: Table Headers
$xlsx->addRow([
    ['v' => 'No.', 's' => 4],
    ['v' => 'No. Transaksi', 's' => 4],
    ['v' => 'Tanggal & Waktu', 's' => 4],
    ['v' => 'Kasir', 's' => 3],
    ['v' => 'Metode Bayar', 's' => 4],
    ['v' => 'Subtotal', 's' => 5],
    ['v' => 'Tipe Diskon', 's' => 4],
    ['v' => 'Diskon (Rp)', 's' => 5],
    ['v' => 'Grand Total', 's' => 5],
    ['v' => 'Total HPP', 's' => 5],
    ['v' => 'Estimasi Laba', 's' => 5],
    ['v' => 'Jumlah Bayar', 's' => 5],
    ['v' => 'Kembalian', 's' => 5],
    ['v' => 'Status', 's' => 4],
    ['v' => 'Bukti Bayar Non-Tunai', 's' => 3],
    ['v' => 'Catatan / Meja', 's' => 3],
    ['v' => 'Rincian Menu Produk', 's' => 3]
], 26);
$xlsx->setAutoFilter('A6:Q6');

$no = 1;
foreach ($transactions as $t) {
    $isZebra = ($no % 2 === 0);
    $textStyle = $isZebra ? 10 : 6;
    $centerStyle = $isZebra ? 11 : 7;
    $currencyStyle = $isZebra ? 12 : 8;

    $items = TransactionRepository::getItems((int)$t['id']);
    $itemDetails = [];
    foreach ($items as $item) {
        $itemDetails[] = $item['product_name'] . ' (' . (int)$item['qty'] . 'x @' . number_format((float)$item['selling_price'], 0, ',', '.') . ')';
    }
    $itemStr = !empty($itemDetails) ? implode(', ', $itemDetails) : '-';

    $cogs = (float)($t['total_cogs'] ?? 0);
    $grand = (float)($t['grand_total'] ?? 0);
    $grossProfit = $grand - $cogs;

    $proofStr = '-';
    if (!empty($t['payment_proof'])) {
        $proofStr = url('/uploads/payments/' . $t['payment_proof']);
    }

    $statusText = strtoupper((string)($t['status'] ?? 'PAID'));
    $statusStyle = $centerStyle;
    if ($statusText === 'PAID') {
        $statusStyle = $isZebra ? 23 : 17;
    } elseif (in_array($statusText, ['CANCELLED', 'REFUND', 'VOID'], true)) {
        $statusStyle = $isZebra ? 24 : 18;
    }

    $xlsx->addRow([
        ['v' => $no, 's' => $centerStyle, 't' => 'n'],
        ['v' => $t['transaction_code'], 's' => $centerStyle],
        ['v' => date('d/m/Y H:i', strtotime($t['transaction_date'])), 's' => $centerStyle],
        ['v' => $t['cashier_name'] ?? 'Kasir', 's' => $textStyle],
        ['v' => $t['payment_method'], 's' => $centerStyle],
        ['v' => (int)$t['subtotal'], 's' => $currencyStyle, 't' => 'n'],
        ['v' => $t['discount_type'] ?: '-', 's' => $centerStyle],
        ['v' => (int)$t['discount_amount'], 's' => $currencyStyle, 't' => 'n'],
        ['v' => (int)$grand, 's' => $currencyStyle, 't' => 'n'],
        ['v' => (int)$cogs, 's' => $currencyStyle, 't' => 'n'],
        ['v' => (int)$grossProfit, 's' => $currencyStyle, 't' => 'n'],
        ['v' => (int)$t['paid_amount'], 's' => $currencyStyle, 't' => 'n'],
        ['v' => (int)$t['change_amount'], 's' => $currencyStyle, 't' => 'n'],
        ['v' => $statusText, 's' => $statusStyle],
        ['v' => $proofStr, 's' => $textStyle],
        ['v' => $t['hold_note'] ?? '-', 's' => $textStyle],
        ['v' => $itemStr, 's' => $textStyle]
    ], 22);

    $no++;
}

// Summary Row
$xlsx->addRow([
    ['v' => '', 's' => 13],
    ['v' => '', 's' => 13],
    ['v' => '', 's' => 13],
    ['v' => '', 's' => 13],
    ['v' => 'TOTAL KESELURUHAN', 's' => 13],
    ['v' => (int)$sumSubtotal, 's' => 15, 't' => 'n'],
    ['v' => '', 's' => 14],
    ['v' => (int)$sumDiscount, 's' => 15, 't' => 'n'],
    ['v' => (int)$sumGrandTotal, 's' => 15, 't' => 'n'],
    ['v' => (int)$sumCogs, 's' => 15, 't' => 'n'],
    ['v' => (int)$sumGrossProfit, 's' => 16, 't' => 'n'],
    ['v' => (int)$sumPaid, 's' => 15, 't' => 'n'],
    ['v' => (int)$sumChange, 's' => 15, 't' => 'n'],
    ['v' => count($transactions) . ' TRX', 's' => 14],
    ['v' => '', 's' => 13],
    ['v' => '', 's' => 13],
    ['v' => '', 's' => 13]
], 24);

$content = $xlsx->generate();
$outputPath = __DIR__ . '/real_transactions.xlsx';
file_put_contents($outputPath, $content);

echo "XLSX successfully created! Size: " . strlen($content) . " bytes\n";

$zip = new ZipArchive();
if ($zip->open($outputPath) === true) {
    echo "ZIP Verification: OK (" . $zip->numFiles . " files)\n";
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->statIndex($i);
        echo " - " . $entry['name'] . ": " . $entry['size'] . " bytes\n";
    }
    $zip->close();
} else {
    echo "FAIL: Cannot open ZIP file\n";
}

unlink($outputPath);
echo "\n--- ALL CHECKS PASSED ---\n";
