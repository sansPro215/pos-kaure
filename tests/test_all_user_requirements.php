<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/app.php';
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});
$helperFiles = glob(__DIR__ . '/../app/Helpers/*.php');
foreach ($helperFiles as $f) require_once $f;

// Login session as owner
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Budi Santoso (Owner)',
    'username' => 'owner',
    'role' => 'OWNER'
];
$_SESSION['csrf_token'] = bin2hex(random_bytes(16));

function test_assert($cond, $msg) {
    if (!$cond) {
        echo "❌ FAIL: $msg\n";
        throw new Exception("Assertion failed: $msg");
    }
    echo "✅ PASS: $msg\n";
}

echo "\n=======================================================\n";
echo "       STARTING COMPREHENSIVE VERIFICATION SUITE       \n";
echo "=======================================================\n\n";

// ---------------------------------------------------------------------
// TEST 1: Settings Form & Synchronization
// ---------------------------------------------------------------------
echo "--- TEST 1: Settings Layout & Synchronization ---\n";
ob_start();
(new App\Controllers\SettingController())->index();
$settingsHtml = ob_get_clean();

test_assert(strpos($settingsHtml, 'Grid Produk Besar') !== false, 'Settings has Grid Produk Besar option');
test_assert(strpos($settingsHtml, 'Tabel Produk Memanjang (Kolom ke Samping)') !== false, 'Settings has Tabel Produk Memanjang (Kolom ke Samping) option');
test_assert(strpos($settingsHtml, 'Grid Kategori + Produk') === false, 'Settings NO LONGER has Grid Kategori option (removed as requested)');
test_assert(strpos($settingsHtml, 'Lebar Kertas Printer Struk') !== false, 'Settings has Lebar Kertas Printer Struk section');
test_assert(strpos($settingsHtml, '58 mm (Thermal Printer Kecil)') !== false, 'Settings has 58mm thermal option');
test_assert(strpos($settingsHtml, '80 mm (Thermal Printer Besar)') !== false, 'Settings has 80mm thermal option');
test_assert(strpos($settingsHtml, 'Daftar Tarif Gaji Pegawai Kasir Saat Ini') === false, 'Settings NO LONGER displays employee wage table (removed as requested)');
test_assert(strpos($settingsHtml, 'sync_all_cashiers') === false, 'Settings NO LONGER has checkbox (sync is fully automatic)');

// Test saving settings with automatic wage sync (WITHOUT any checkbox)
$_POST = [
    '_token' => $_SESSION['csrf_token'],
    'shop_name' => 'WARUNG KAURE',
    'address' => 'Jl. Test No. 123',
    'phone' => '081234567890',
    'tax_enabled' => '0',
    'tax_percentage' => '0',
    'color_palette' => 'brown',
    'pos_layout' => 'table_list',
    'receipt_width' => '58',
    'default_regular_rate' => '13000',
    'default_overtime_rate' => '7000',
    'manager_incentive_percent' => '50',
    'shareholder_percent' => '50'
];

class TestableSettingController extends App\Controllers\SettingController {
    public string $redirectedTo = '';
    protected function redirect(string $path): void {
        $this->redirectedTo = $path;
    }
}

$testSettingCtrl = new TestableSettingController();
$testSettingCtrl->update();

$dbSettings = \App\Repositories\SettingRepository::get();
test_assert($dbSettings['pos_layout'] === 'table_list', 'pos_layout successfully saved as table_list');
test_assert($dbSettings['receipt_width'] === '58', 'receipt_width successfully saved as 58');
test_assert((int)$dbSettings['default_regular_rate'] === 13000, 'default_regular_rate successfully saved as 13000');
test_assert((int)$dbSettings['default_overtime_rate'] === 7000, 'default_overtime_rate successfully saved as 7000');

// Verify sync to cashier users
$cashiers = \App\Core\Database::fetchAll("SELECT * FROM users WHERE role = 'CASHIER' AND status = 'ACTIVE' AND deleted_at IS NULL");
foreach ($cashiers as $c) {
    test_assert((int)$c['hourly_rate'] === 13000, "Cashier {$c['name']} hourly_rate automatically synced to 13000");
    test_assert((int)$c['overtime_rate'] === 7000, "Cashier {$c['name']} overtime_rate automatically synced to 7000");
}

// ---------------------------------------------------------------------
// TEST 2: Thermal Receipt CSS
// ---------------------------------------------------------------------
echo "\n--- TEST 2: Thermal Receipt CSS ---\n";
// Create dummy sale for receipt view test
$sale = \App\Core\Database::fetch("SELECT * FROM transactions LIMIT 1");
if ($sale) {
    ob_start();
    (new App\Controllers\PosController())->receipt((string)$sale['id']);
    $receiptHtml = ob_get_clean();

    test_assert(strpos($receiptHtml, 'size: 58mm auto') !== false, 'Receipt contains thermal @page size 58mm');
    test_assert(strpos($receiptHtml, 'max-width: 280px') !== false, 'Receipt container width matches 58mm spec (280px)');
}

// ---------------------------------------------------------------------
// TEST 3: Edit Transaction Requirements
// ---------------------------------------------------------------------
echo "\n--- TEST 3: Edit Transaction Requirements ---\n";
if ($sale) {
    ob_start();
    (new App\Controllers\TransactionController())->show((string)$sale['id']);
    $txHtml = ob_get_clean();

    test_assert(strpos($txHtml, 'Alasan Koreksi') === false, 'Transaction detail/edit does NOT contain "Alasan Koreksi"');
    test_assert(strpos($txHtml, 'name="edit_reason"') === false, 'Transaction edit form does NOT have edit_reason input');
    test_assert(strpos($txHtml, 'Baris Kustom') === false, 'Transaction edit form does NOT have "Baris Kustom" button');
    test_assert(strpos($txHtml, 'addManualItemRow') === false, 'Transaction edit script does NOT have addManualItemRow()');
    test_assert(strpos($txHtml, 'btnToggleDiscount') !== false, 'Transaction edit has "+ Tambah Diskon" toggle button');
    test_assert(strpos($txHtml, '- Rp') !== false, 'Transaction edit supports negative change display format');
}

// Test SaleService::updateFullTransaction with negative change (Uang bayar kurang)
$testTx = \App\Core\Database::fetch("SELECT * FROM transactions LIMIT 1");
$product = \App\Core\Database::fetch("SELECT * FROM products WHERE status = 'ACTIVE' AND deleted_at IS NULL LIMIT 1");
if ($testTx && $product) {
    // Total price 20000, paid 15000 -> change should be -5000
    $updateData = [
        'customer_name' => 'Testing Negative Change',
        'items' => [
            [
                'product_id' => $product['id'],
                'product_name' => $product['name'],
                'qty' => 1,
                'selling_price' => 20000,
                'cost_price' => 10000
            ]
        ],
        'paid_amount' => 15000,
        'payment_method' => 'CASH'
    ];

    $updatedSale = \App\Services\SaleService::updateFullTransaction((int)$testTx['id'], $updateData, (int)$_SESSION['user']['id']);
    test_assert((float)$updatedSale['grand_total'] == 20000, 'Grand total calculated correctly');

    $dbTx = \App\Repositories\TransactionRepository::findById((int)$testTx['id']);
    test_assert((float)$dbTx['paid_amount'] == 15000, 'Paid amount is 15000');
    test_assert((float)$dbTx['change_amount'] == -5000, 'Change amount is negative -5000 as expected');
}

// ---------------------------------------------------------------------
// TEST 4: POS Layout (Tampilan POS Asli & Tabel Kolom Memanjang ke Samping)
// ---------------------------------------------------------------------
echo "\n--- TEST 4: POS Layout (Tampilan Asli & Tabel Kolom Memanjang ke Samping) ---\n";
ob_start();
(new App\Controllers\PosController())->index();
$posHtml = ob_get_clean();

// 4.1 Tabel Memanjang ke Samping dengan 7 kolom terpisah
test_assert(strpos($posHtml, 'id="modernPosTable"') !== false, 'POS renders table layout (id="modernPosTable")');
test_assert(strpos($posHtml, '>Foto</th>') !== false, 'Table has Foto column');
test_assert(strpos($posHtml, '>SKU</th>') !== false, 'Table has SKU column');
test_assert(strpos($posHtml, '>Nama Produk / Barang</th>') !== false, 'Table has Nama Produk / Barang column');
test_assert(strpos($posHtml, '>Kategori</th>') !== false, 'Table has Kategori column');
test_assert(strpos($posHtml, '>Stok</th>') === false, 'Table does NOT have Stok column (stock system removed as requested)');
test_assert(strpos($posHtml, '>Harga Satuan</th>') !== false, 'Table has Harga Satuan column');
test_assert(strpos($posHtml, '>Aksi</th>') !== false, 'Table has Aksi column');

// 4.2 Tampilan Keranjang POS tetap seperti semula (Warung Kaure original)
test_assert(strpos($posHtml, 'Keranjang Penjualan') !== false, 'POS cart title is "Keranjang Penjualan" (tampilan sebelumnya)');
test_assert(strpos($posHtml, 'id="desktopCartItems"') !== false, 'Desktop cart items container exists');
test_assert(strpos($posHtml, 'id="desktopPayBtn"') !== false, 'Desktop pay button exists');
test_assert(strpos($posHtml, 'BAYAR Rp0') !== false, 'Desktop pay button has initial text "BAYAR Rp0"');
test_assert(strpos($posHtml, 'pos-bottom-nav-bar') === false, 'POS DOES NOT have bottom blue nav bar (removed as requested)');
test_assert(strpos($posHtml, 'btn-stepper-modern') === false, 'POS DOES NOT have custom stepper styles');

// ---------------------------------------------------------------------
// TEST 5: Payroll Enhancements (Delete, Slip Print, Summary Print)
// ---------------------------------------------------------------------
echo "\n--- TEST 5: Payroll Enhancements ---\n";

// 5.1 Create a temporary payroll period for testing
$periodId = \App\Repositories\PayrollRepository::createPeriod(
    'Periode Test Otomatisasi',
    date('Y-m-01'),
    date('Y-m-14'),
    1
);
test_assert($periodId > 0, "Created payroll period with ID $periodId");

// Generate payroll calculation
\App\Services\PayrollService::generateForPeriod($periodId, 1);
$items = \App\Repositories\PayrollRepository::getPayrollsByPeriod($periodId);
test_assert(count($items) > 0, "Generated " . count($items) . " payroll slip items");

// 5.2 Test Views index and show for delete and print buttons
ob_start();
(new App\Controllers\PayrollController())->index();
$payrollIndexHtml = ob_get_clean();
test_assert(strpos($payrollIndexHtml, "/payroll/$periodId/delete") !== false, 'Payroll index has delete button for the period');
test_assert(strpos($payrollIndexHtml, "/payroll/$periodId/print") !== false, 'Payroll index has print rekap button for the period');

ob_start();
(new App\Controllers\PayrollController())->show((string)$periodId);
$payrollShowHtml = ob_get_clean();
test_assert(strpos($payrollShowHtml, "/payroll/$periodId/print") !== false, 'Payroll show has print rekap button in header');
$firstItem = $items[0];
test_assert(strpos($payrollShowHtml, "/payroll/slip/{$firstItem['id']}") !== false, 'Payroll show has print slip button for employee');
test_assert(strpos($payrollShowHtml, "/payroll/item/{$firstItem['id']}/delete") !== false, 'Payroll show has delete item button for employee');

// 5.3 Test Printable Slip View
ob_start();
(new App\Controllers\PayrollController())->slip((string)$firstItem['id']);
$slipHtml = ob_get_clean();
test_assert(strpos($slipHtml, 'SLIP GAJI RESMI') !== false, 'Slip view has SLIP GAJI RESMI badge');
test_assert(strpos($slipHtml, 'Rincian Rekapitulasi Absensi Harian Pegawai') !== false, 'Slip view has Rekapitulasi Absensi table');
test_assert(strpos($slipHtml, 'Rincian Penghitungan Gaji (Take Home Pay)') !== false, 'Slip view has Take Home Pay section');
test_assert(strpos($slipHtml, 'window.print()') !== false, 'Slip view has print button with window.print()');

// 5.4 Test Printable Period Summary View
ob_start();
(new App\Controllers\PayrollController())->printPeriodSummary((string)$periodId);
$summaryPrintHtml = ob_get_clean();
test_assert(strpos($summaryPrintHtml, 'Laporan Penggajian Pegawai') !== false, 'Summary print has Laporan Penggajian Pegawai header');
test_assert(strpos($summaryPrintHtml, 'Total Pengeluaran Gaji') !== false, 'Summary print has KPI cards');
test_assert(strpos($summaryPrintHtml, 'Total Keseluruhan') !== false, 'Summary print has footer grand total');

// 5.5 Test Deleting Individual Payroll Item
$itemCountBefore = count(\App\Repositories\PayrollRepository::getPayrollsByPeriod($periodId));
\App\Repositories\PayrollRepository::deletePayrollItem((int)$firstItem['id']);
$itemCountAfter = count(\App\Repositories\PayrollRepository::getPayrollsByPeriod($periodId));
test_assert($itemCountAfter === $itemCountBefore - 1, "Individual payroll item {$firstItem['id']} successfully deleted");

// 5.6 Test Deleting Whole Payroll Period
\App\Repositories\PayrollRepository::deletePeriod($periodId);
$deletedPeriod = \App\Repositories\PayrollRepository::getPeriodById($periodId);
test_assert($deletedPeriod === null, "Payroll period $periodId successfully deleted from database");

// ---------------------------------------------------------------------
// TEST 6: Dashboard Report Printing (Bukan Link Penjualan, tapi Cetak Laporan Dashboard)
// ---------------------------------------------------------------------
echo "\n--- TEST 6: Dashboard Report Printing ---\n";
ob_start();
(new App\Controllers\DashboardController())->index();
$dashboardHtml = ob_get_clean();

test_assert(strpos($dashboardHtml, "url('/reports/sales')") === false, 'Dashboard button is NOT a link to sales module (url(/reports/sales))');
test_assert(strpos($dashboardHtml, 'Cetak Laporan') !== false, 'Dashboard has "Cetak Laporan" button');
test_assert(strpos($dashboardHtml, 'contentWindow.print()') !== false || strpos($dashboardHtml, 'window.print()') !== false, 'Dashboard has direct print action (via iframe or window)');
test_assert(strpos($dashboardHtml, 'LAPORAN RINGKASAN OPERASIONAL & KEUANGAN (DASHBOARD)') !== false, 'Dashboard has print-only letterhead');
test_assert(strpos($dashboardHtml, '/dashboard/print') !== false, 'Dashboard provides link to dedicated print layout');

// Test dedicated print view
ob_start();
(new App\Controllers\DashboardController())->print();
$dashboardPrintHtml = ob_get_clean();

test_assert(strpos($dashboardPrintHtml, 'Laporan Ringkasan Operasional & Keuangan (Dashboard)') !== false, 'Dedicated print view renders with official header');
test_assert(strpos($dashboardPrintHtml, 'Omzet Hari Ini') !== false, 'Dedicated print view includes Omzet Hari Ini');
test_assert(strpos($dashboardPrintHtml, 'Laba Kotor Kedai') !== false, 'Dedicated print view includes Laba Kotor Kedai');
test_assert(strpos($dashboardPrintHtml, 'Gaji Owner') !== false, 'Dedicated print view includes Gaji Owner');
test_assert(strpos($dashboardPrintHtml, 'window.print()') !== false, 'Dedicated print view includes window.print() trigger');

echo "\n=======================================================\n";
echo "       ALL VERIFICATION TESTS COMPLETED SUCCESSFULLY!  \n";
echo "=======================================================\n\n";

