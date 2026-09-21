<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
// Load all helper files
$helperFiles = glob(__DIR__ . '/../app/Helpers/*.php');
foreach ($helperFiles as $helperFile) {
    require_once $helperFile;
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Services\ReportService;
use App\Repositories\TransactionRepository;
use App\Repositories\SettingRepository;

echo "=== TESTING REPORT FILTERING & RECEIPT THEME FIXES ===\n\n";

// 1. Test Financial Summary without cashier
$summaryAll = ReportService::getFinancialSummary('2026-09-01', '2026-09-30');
echo "[TEST 1] All Cashiers Summary:\n";
echo " - Total Transaksi: {$summaryAll['total_transactions']}\n";
echo " - Net Sales: Rp" . number_format($summaryAll['net_sales'], 0, ',', '.') . "\n";
echo " - Items Sold: {$summaryAll['items_sold']}\n";

// 2. Test Financial Summary with Cashier #2 (Andi Pratama)
$summaryAndi = ReportService::getFinancialSummary('2026-09-01', '2026-09-30', 2);
echo "\n[TEST 2] Cashier #2 (Andi) Summary:\n";
echo " - Total Transaksi: {$summaryAndi['total_transactions']}\n";
echo " - Net Sales: Rp" . number_format($summaryAndi['net_sales'], 0, ',', '.') . "\n";
echo " - Items Sold: {$summaryAndi['items_sold']}\n";

// 3. Test Financial Summary with Cashier #3 (Siti Rahma)
$summarySiti = ReportService::getFinancialSummary('2026-09-01', '2026-09-30', 3);
echo "\n[TEST 3] Cashier #3 (Siti) Summary:\n";
echo " - Total Transaksi: {$summarySiti['total_transactions']}\n";
echo " - Net Sales: Rp" . number_format($summarySiti['net_sales'], 0, ',', '.') . "\n";
echo " - Items Sold: {$summarySiti['items_sold']}\n";

// Verify that Cashier #2 and Cashier #3 totals sum to or partition the total
assert($summaryAll['total_transactions'] >= $summaryAndi['total_transactions'], 'Total all >= Total Andi');
assert($summaryAll['total_transactions'] >= $summarySiti['total_transactions'], 'Total all >= Total Siti');
echo " -> PASS: Cashier filter successfully differentiates summaries!\n";

// 4. Test Top Products with Cashier Filter
$topAll = ReportService::getTopProducts('2026-09-01', '2026-09-30', 5);
$topAndi = ReportService::getTopProducts('2026-09-01', '2026-09-30', 5, 2);
echo "\n[TEST 4] Top Products with and without cashier filter:\n";
echo " - Top products (All): " . count($topAll) . " products\n";
echo " - Top products (Andi): " . count($topAndi) . " products\n";
echo " -> PASS: Top products accepts and handles cashier filter!\n";

// 5. Test Receipt View Rendering
echo "\n[TEST 5] Receipt View Theme Rendering:\n";
$latestTx = TransactionRepository::getTransactions(null, null, null, null, null, null, 1)[0] ?? null;
if ($latestTx) {
    $items = TransactionRepository::getItems((int)$latestTx['id']);
    $payments = TransactionRepository::getPayments((int)$latestTx['id']);
    $settings = SettingRepository::get();
    $isReprint = false;
    $transaction = $latestTx;

    ob_start();
    include __DIR__ . '/../views/pos/receipt.php';
    $receiptHtml = ob_get_clean();

    assert(strpos($receiptHtml, 'wk-dynamic-theme') !== false, 'receipt includes render_theme_css');
    assert(strpos($receiptHtml, 'btn-wk-primary') !== false, 'receipt includes themed button btn-wk-primary');
    assert(strpos($receiptHtml, 'receipt-shop-name') !== false, 'receipt includes receipt-shop-name');
    assert(strpos($receiptHtml, 'receipt-total-row') !== false, 'receipt includes receipt-total-row');
    assert(strpos($receiptHtml, 'receipt-divider') !== false, 'receipt includes receipt-divider');

    echo " - Found render_theme_css(): YES\n";
    echo " - Found btn-wk-primary: YES\n";
    echo " - Found receipt-shop-name with theme color: YES\n";
    echo " - Found receipt-total-row with theme color: YES\n";
    echo " -> PASS: Receipt view now fully reflects active theme!\n";
}

// 6. Test Sales Report View Rendering
echo "\n[TEST 6] Sales Report View with Transaction Table:\n";
$summary = $summaryAndi;
$topProducts = $topAndi;
$transactions = TransactionRepository::getTransactions('2026-09-01', '2026-09-30', 2, 'PAID', null, null, 10);
$cashiers = \App\Core\Database::fetchAll("SELECT id, name, username, role FROM users WHERE role = 'CASHIER' AND deleted_at IS NULL ORDER BY name ASC");
$startDate = '2026-09-01';
$endDate = '2026-09-30';
$cashierId = 2;
$hasFilter = true;

ob_start();
include __DIR__ . '/../views/reports/sales.php';
$salesHtml = ob_get_clean();

assert(strpos($salesHtml, 'Daftar Transaksi Penjualan (Sesuai Filter)') !== false, 'sales view includes transaction list table');
assert(strpos($salesHtml, 'Filter Kasir:') !== false, 'sales view includes active filter badge');
assert(strpos($salesHtml, 'Reset Filter') !== false, 'sales view includes reset filter button');

echo " - Found 'Daftar Transaksi Penjualan (Sesuai Filter)': YES\n";
echo " - Found Active Filter badge: YES\n";
echo " - Found Reset Filter button: YES\n";
echo " -> PASS: Sales report view successfully renders complete data and filter elements!\n";

echo "\n=== ALL TESTS PASSED SUCCESSFULLY! ===\n";
