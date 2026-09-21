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

echo "--- Testing HTML Render Output ---\n";

// 1. Settings view
ob_start();
(new App\Controllers\SettingController())->index();
$settingsHtml = ob_get_clean();
assert_contains($settingsHtml, 'Tampilan & Layout Modul POS', 'Settings has POS layout section');
assert_contains($settingsHtml, 'Grid Produk Besar', 'Settings has Grid Produk Besar');
assert_contains($settingsHtml, 'Tabel Kasir Modern (Sesuai Foto)', 'Settings has Tabel Kasir Modern (Sesuai Foto)');
assert_contains($settingsHtml, 'Gaji Reguler (Rp/Jam)', 'Settings has regular salary rate');
assert_contains($settingsHtml, 'Nama Kedai / Usaha (Header & Struk)', 'Settings has header sync note');
assert_contains($settingsHtml, shop_name(), 'Header/layout contains dynamic shop_name');
assert_contains($settingsHtml, 'Persentase Intensif Manager / Gaji Owner (%)', 'Settings has manager incentive percent');
assert_contains($settingsHtml, 'Persentase Pemegang Saham / Dividen (%)', 'Settings has shareholder dividend percent');
assert_contains($settingsHtml, 'Nuansa Warna & Tema Aplikasi (Color Palette)', 'Settings has color palette section');
assert_contains($settingsHtml, 'Cokelat Kopi (Barista)', 'Settings has Coffee Barista palette');
assert_contains($settingsHtml, 'Emerald Sage (Hijau Hutan)', 'Settings has Emerald Forest palette');

// 2. Dashboard view (Owner)
ob_start();
(new App\Controllers\DashboardController())->index();
$dashHtml = ob_get_clean();
assert_contains($dashHtml, 'Dashboard', 'Dashboard renders title');
assert_not_contains($dashHtml, 'Anda Belum Absen Masuk Hari Ini', 'Owner dashboard does NOT show attendance warning banner');

// 3. Profit report view
ob_start();
(new App\Controllers\ReportController())->profit();
$profitHtml = ob_get_clean();
assert_contains($profitHtml, 'Laporan HPP & Estimasi Laba Bersih', 'Profit report rendered title');
assert_contains($profitHtml, 'Gaji Owner', 'Profit report shows Gaji Owner');
assert_contains($profitHtml, 'Pemegang Saham', 'Profit report shows Pemegang Saham');
assert_contains($profitHtml, 'LABA KOTOR KEDAI', 'Profit report has minimalist financial table with Laba Kotor');
assert_contains($profitHtml, 'Total Beban (Operasional + Gaji Pegawai)', 'Profit report has Total Beban breakdown');
assert_contains($profitHtml, 'window.print()', 'Profit report has working print button');

// 4. Expenses view
ob_start();
(new App\Controllers\ExpenseController())->index();
$expensesHtml = ob_get_clean();
assert_contains($expensesHtml, 'Beban Operasional Kedai', 'Expenses has operational card');
assert_contains($expensesHtml, 'Beban Gaji Pegawai (Payroll PAID)', 'Expenses has payroll expense card');
assert_contains($expensesHtml, 'Total Seluruh Pengeluaran', 'Expenses has combined total card');
assert_contains($expensesHtml, 'Beban Gaji Pegawai (Payroll Lunas / PAID)', 'Expenses has paid payroll section');

// 5. POS view as Owner (Allowed directly)
ob_start();
(new App\Controllers\PosController())->index();
$posHtml = ob_get_clean();
assert_contains($posHtml, 'pos-product-item', 'POS for Owner rendered products directly');
assert_contains($posHtml, 'posSearchInput', 'POS has search input');

// 6. POS view as Cashier without attendance (Must show lock screen)
$_SESSION['user'] = [
    'id' => 2,
    'name' => 'Andi Pratama (Kasir)',
    'username' => 'kasir',
    'role' => 'CASHIER'
];
// Ensure cashier has no attendance for today
App\Core\Database::execute("DELETE FROM attendance WHERE user_id = 2 AND date = ?", [date('Y-m-d')]);

ob_start();
(new App\Controllers\PosController())->index();
$cashierPosLockedHtml = ob_get_clean();
assert_contains($cashierPosLockedHtml, 'Absen Masuk Diperlukan', 'Cashier without attendance gets locked POS screen');
assert_contains($cashierPosLockedHtml, 'ABSEN MASUK SEKARANG', 'Locked POS screen has ABSEN MASUK SEKARANG button');
assert_not_contains($cashierPosLockedHtml, 'pos-product-item', 'Locked POS screen does not show products');

echo "\n>>> ALL RENDER CHECKS PASSED! <<<\n";

function assert_contains($haystack, $needle, $msg) {
    if (strpos($haystack, $needle) !== false) {
        echo " [PASS] $msg\n";
    } else {
        echo " [FAIL] $msg\n";
        exit(1);
    }
}

function assert_not_contains($haystack, $needle, $msg) {
    if (strpos($haystack, $needle) === false) {
        echo " [PASS] $msg\n";
    } else {
        echo " [FAIL] $msg (Found unexpected substring)\n";
        exit(1);
    }
}
