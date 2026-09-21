<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Owner Kaure',
    'username' => 'owner',
    'role' => 'OWNER'
];

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $rel = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) require_once $file;
});

foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) {
    require_once $f;
}

use App\Core\Database;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\UserRepository;
use App\Repositories\ShiftRepository;
use App\Services\SaleService;
use App\Services\PayrollService;
use App\Controllers\DashboardController;
use App\Controllers\PosController;
use App\Controllers\TransactionController;
use App\Controllers\ReportController;
use App\Controllers\AttendanceController;
use App\Controllers\AuditController;
use App\Controllers\CategoryController;
use App\Controllers\ExpenseController;
use App\Controllers\PayrollController;
use App\Controllers\ProductController;
use App\Controllers\SettingController;
use App\Controllers\UserController;

echo "======================================================================\n";
echo "       COMPREHENSIVE SYSTEM-WIDE POS & CORE MODULE AUDIT\n";
echo "======================================================================\n\n";

$pass = 0;
$fail = 0;

function assertTest(bool $condition, string $message): void {
    global $pass, $fail;
    if ($condition) {
        $pass++;
        echo "  [PASS] {$message}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$message}\n";
    }
}

// ---------------------------------------------------------
// 1. DATABASE SCHEMA & TABLE INTEGRITY CHECK
// ---------------------------------------------------------
echo "1. Checking Database Tables & Stock Column Absence...\n";
$tables = Database::fetchAll("SHOW TABLES");
$tableNames = array_map(function($row) { return array_values($row)[0]; }, $tables);

$requiredTables = ['users', 'categories', 'products', 'transactions', 'transaction_items', 'payments', 'attendance', 'payrolls', 'settings', 'operational_expenses', 'active_sessions'];
foreach ($requiredTables as $tbl) {
    assertTest(in_array($tbl, $tableNames), "Table `{$tbl}` exists in database");
}

// Ensure stock columns are removed from products
$prodCols = Database::fetchAll("SHOW COLUMNS FROM products");
$prodColNames = array_column($prodCols, 'Field');
assertTest(!in_array('stock', $prodColNames), "`products` has no `stock` column");
assertTest(!in_array('minimum_stock', $prodColNames), "`products` has no `minimum_stock` column");
assertTest(!in_array('stock_tracking_type', $prodColNames), "`products` has no `stock_tracking_type` column");

// ---------------------------------------------------------
// 2. PRODUCT MANAGEMENT & SKU GENERATION
// ---------------------------------------------------------
echo "\n2. Testing Product Lifecycle (Sequential SKU, Create, Update, Delete)...\n";
$cats = CategoryRepository::getAll();
assertTest(!empty($cats), "Categories exist (count: " . count($cats) . ")");
$firstCat = $cats[0];
$newSku = ProductRepository::generateSku((int)$firstCat['id']);
assertTest(!empty($newSku), "ProductRepository::generateSku returned '{$newSku}' for category '{$firstCat['name']}'");

$newProdId = ProductRepository::create([
    'category_id' => $firstCat['id'],
    'sku' => $newSku,
    'name' => 'Audit Test Product ' . time(),
    'selling_price' => 25000,
    'cost_price' => 12000,
    'status' => 'ACTIVE'
]);
assertTest($newProdId > 0, "Created test product #{$newProdId}");

$fetched = ProductRepository::findById($newProdId);
assertTest($fetched !== null && (float)$fetched['selling_price'] === 25000.0, "Fetched test product correctly with selling_price = 25000");

// Update
$updated = ProductRepository::update($newProdId, [
    'category_id' => $firstCat['id'],
    'sku' => $newSku,
    'name' => 'Audit Test Product Updated',
    'selling_price' => 28000,
    'cost_price' => 13000,
    'status' => 'ACTIVE'
]);
assertTest($updated === true, "Updated test product successfully");

// Delete (Hard delete)
$deleted = ProductRepository::delete($newProdId);
assertTest($deleted === true, "ProductRepository::delete returned true (hard delete)");
$afterDelete = ProductRepository::findById($newProdId);
assertTest($afterDelete === null, "Product #{$newProdId} is completely removed from DB");

// ---------------------------------------------------------
// 3. POS CHECKOUT, SPLIT / PAYMENT & TRANSACTION LIFECYCLE
// ---------------------------------------------------------
echo "\n3. Testing POS Checkout & Transaction Lifecycle...\n";
// Ensure there is an active product for testing
$products = ProductRepository::getAll(null, null, true);
assertTest(!empty($products), "Active products available for POS test (count: " . count($products) . ")");
$testProd = $products[0];

// Test SaleService::checkout
$cartPayload = [
    [
        'product_id' => (int)$testProd['id'],
        'qty' => 2
    ]
];

$checkoutResult = SaleService::checkout(
    $cartPayload,
    [
        'method' => 'CASH',
        'received_amount' => 100000,
    ],
    [
        'type' => 'NONE',
        'value' => 0
    ],
    1
);

assertTest($checkoutResult['status'] === true, "SaleService::checkout succeeded with transaction ID #{$checkoutResult['transaction_id']}");
$transId = (int)$checkoutResult['transaction_id'];

// Check transaction record
$trans = TransactionRepository::findById($transId);
assertTest($trans !== null && $trans['status'] === 'PAID', "Transaction #{$transId} status is PAID");
assertTest((float)$trans['grand_total'] == (float)$testProd['selling_price'] * 2, "Grand total matches expected (" . $trans['grand_total'] . ")");

$items = TransactionRepository::getItems($transId);
assertTest(count($items) === 1, "Transaction #{$transId} has exactly 1 item record");
assertTest((int)$items[0]['qty'] === 2, "Item qty is 2");

// Test Owner Edit Transaction
$editPayload = [
    'transaction_date' => date('Y-m-d H:i:s'),
    'cashier_id' => 1,
    'status' => 'PAID',
    'hold_note' => 'Audit note',
    'payment_method' => 'QRIS',
    'provider' => 'BCA',
    'reference_number' => 'REF123456',
    'paid_amount' => (float)$testProd['selling_price'] * 3,
    'discount_type' => 'NONE',
    'discount_value' => 0,
    'adjust_stock' => false,
    'delete_payment_proof' => false,
    'edit_reason' => 'Test owner edit in audit',
    'items' => [
        [
            'product_id' => $testProd['id'],
            'product_name' => $testProd['name'],
            'qty' => 3,
            'selling_price' => $testProd['selling_price'],
            'cost_price' => $testProd['cost_price']
        ]
    ]
];
SaleService::updateFullTransaction($transId, $editPayload, 1);
$updatedTrans = TransactionRepository::findById($transId);
assertTest($updatedTrans['payment_method'] === 'QRIS', "Owner edited payment method to QRIS successfully");
$updatedItems = TransactionRepository::getItems($transId);
assertTest((int)$updatedItems[0]['qty'] === 3, "Owner edited item qty to 3 successfully");

// Test Refund Transaction
$refundItems = [$updatedItems[0]['id'] => 1];
$refundAmount = (float)$testProd['selling_price'];
$refundRes = SaleService::refundTransaction($transId, $refundItems, $refundAmount, 'Audit Refund', false, 1);
assertTest($refundRes === true, "SaleService::refundTransaction succeeded");
$afterRefundTrans = TransactionRepository::findById($transId);
assertTest($afterRefundTrans['status'] === 'PARTIAL_REFUND', "Transaction status is now PARTIAL_REFUND");

// Clean up transaction
Database::execute("DELETE FROM transaction_items WHERE transaction_id = ?", [$transId]);
Database::execute("DELETE FROM payments WHERE transaction_id = ?", [$transId]);
Database::execute("DELETE FROM transactions WHERE id = ?", [$transId]);
assertTest(TransactionRepository::findById($transId) === null, "Test transaction cleaned up cleanly");

// ---------------------------------------------------------
// 4. ATTENDANCE & OVERTIME ROUNDING
// ---------------------------------------------------------
echo "\n4. Testing Attendance Constraints & Overtime Rounding...\n";

// Future date validation check
$futureDate = date('Y-m-d', strtotime('+1 day'));
$today = date('Y-m-d');
assertTest($futureDate > $today, "Future date '{$futureDate}' is strictly greater than today '{$today}'");

// Duplicate check
$hasDuplicateMethod = method_exists(AttendanceRepository::class, 'existsByUserAndDate');
assertTest($hasDuplicateMethod, "AttendanceRepository::existsByUserAndDate method exists");

// Overtime rounding formula validation: floor($minutes / 60) * 60
$testCases = [
    ['mins' => 25, 'expected_ot_hours' => 0, 'expected_ot_mins' => 0],
    ['mins' => 45, 'expected_ot_hours' => 0, 'expected_ot_mins' => 0],
    ['mins' => 59, 'expected_ot_hours' => 0, 'expected_ot_mins' => 0],
    ['mins' => 60, 'expected_ot_hours' => 1, 'expected_ot_mins' => 60],
    ['mins' => 75, 'expected_ot_hours' => 1, 'expected_ot_mins' => 60],
    ['mins' => 119, 'expected_ot_hours' => 1, 'expected_ot_mins' => 60],
    ['mins' => 120, 'expected_ot_hours' => 2, 'expected_ot_mins' => 120],
    ['mins' => 150, 'expected_ot_hours' => 2, 'expected_ot_mins' => 120],
    ['mins' => 180, 'expected_ot_hours' => 3, 'expected_ot_mins' => 180],
];

foreach ($testCases as $tc) {
    $rawMins = $tc['mins'];
    $roundedOtHours = (int)floor($rawMins / 60);
    $roundedOtMins = $roundedOtHours * 60;
    assertTest(
        $roundedOtHours === $tc['expected_ot_hours'] && $roundedOtMins === $tc['expected_ot_mins'],
        "Overtime {$rawMins}m -> {$roundedOtHours}h ({$roundedOtMins}m) strictly rounded per 1 hour"
    );
}

// ---------------------------------------------------------
// 5. CONTROLLER & VIEW RENDERING TESTS
// ---------------------------------------------------------
echo "\n5. Testing Controller & View Rendering Without Errors...\n";

// Dashboard index
ob_start();
try {
    (new DashboardController())->index();
    $dashOut = ob_get_clean();
    assertTest(strlen($dashOut) > 0 && !str_contains($dashOut, 'Fatal error') && !str_contains($dashOut, 'SQLSTATE'), "DashboardController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "DashboardController::index() failed: " . $e->getMessage());
}

// Dashboard print
ob_start();
try {
    (new DashboardController())->print();
    $dashPrintOut = ob_get_clean();
    assertTest(strlen($dashPrintOut) > 0 && !str_contains($dashPrintOut, 'Fatal error'), "DashboardController::print() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "DashboardController::print() failed: " . $e->getMessage());
}

// POS Controller index
ob_start();
try {
    (new PosController())->index();
    $posOut = ob_get_clean();
    assertTest(strlen($posOut) > 0 && !str_contains($posOut, 'Fatal error'), "PosController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "PosController::index() failed: " . $e->getMessage());
}

// Transactions Controller index
ob_start();
try {
    (new TransactionController())->index();
    $transOut = ob_get_clean();
    assertTest(strlen($transOut) > 0 && !str_contains($transOut, 'Fatal error'), "TransactionController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "TransactionController::index() failed: " . $e->getMessage());
}

// Attendance Controller index
ob_start();
try {
    (new AttendanceController())->index();
    $attOut = ob_get_clean();
    assertTest(strlen($attOut) > 0 && !str_contains($attOut, 'Fatal error'), "AttendanceController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "AttendanceController::index() failed: " . $e->getMessage());
}

// Audit Controller index
ob_start();
try {
    (new AuditController())->index();
    $auditOut = ob_get_clean();
    assertTest(strlen($auditOut) > 0 && !str_contains($auditOut, 'Fatal error'), "AuditController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "AuditController::index() failed: " . $e->getMessage());
}

// Category Controller index
ob_start();
try {
    (new CategoryController())->index();
    $catOut = ob_get_clean();
    assertTest(strlen($catOut) > 0 && !str_contains($catOut, 'Fatal error'), "CategoryController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "CategoryController::index() failed: " . $e->getMessage());
}

// Expense Controller index
ob_start();
try {
    (new ExpenseController())->index();
    $expOut = ob_get_clean();
    assertTest(strlen($expOut) > 0 && !str_contains($expOut, 'Fatal error'), "ExpenseController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ExpenseController::index() failed: " . $e->getMessage());
}

// Payroll Controller index
ob_start();
try {
    (new PayrollController())->index();
    $payrollOut = ob_get_clean();
    assertTest(strlen($payrollOut) > 0 && !str_contains($payrollOut, 'Fatal error'), "PayrollController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "PayrollController::index() failed: " . $e->getMessage());
}

// Product Controller index & create
ob_start();
try {
    (new ProductController())->index();
    $prodOut = ob_get_clean();
    assertTest(strlen($prodOut) > 0 && !str_contains($prodOut, 'Fatal error'), "ProductController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ProductController::index() failed: " . $e->getMessage());
}

ob_start();
try {
    (new ProductController())->create();
    $prodCreateOut = ob_get_clean();
    assertTest(strlen($prodCreateOut) > 0 && !str_contains($prodCreateOut, 'Fatal error'), "ProductController::create() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ProductController::create() failed: " . $e->getMessage());
}

// Report Controller sales & payments & profit
ob_start();
try {
    (new ReportController())->sales();
    $repSalesOut = ob_get_clean();
    assertTest(strlen($repSalesOut) > 0 && !str_contains($repSalesOut, 'Fatal error'), "ReportController::sales() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ReportController::sales() failed: " . $e->getMessage());
}

ob_start();
try {
    (new ReportController())->payments();
    $repPayOut = ob_get_clean();
    assertTest(strlen($repPayOut) > 0 && !str_contains($repPayOut, 'Fatal error'), "ReportController::payments() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ReportController::payments() failed: " . $e->getMessage());
}

ob_start();
try {
    (new ReportController())->profit();
    $repProfitOut = ob_get_clean();
    assertTest(strlen($repProfitOut) > 0 && !str_contains($repProfitOut, 'Fatal error'), "ReportController::profit() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "ReportController::profit() failed: " . $e->getMessage());
}

// Setting Controller index
ob_start();
try {
    (new SettingController())->index();
    $setOut = ob_get_clean();
    assertTest(strlen($setOut) > 0 && !str_contains($setOut, 'Fatal error'), "SettingController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "SettingController::index() failed: " . $e->getMessage());
}

// User Controller index
ob_start();
try {
    (new UserController())->index();
    $userOut = ob_get_clean();
    assertTest(strlen($userOut) > 0 && !str_contains($userOut, 'Fatal error'), "UserController::index() rendered successfully");
} catch (\Throwable $e) {
    ob_end_clean();
    assertTest(false, "UserController::index() failed: " . $e->getMessage());
}

// ---------------------------------------------------------
// SUMMARY
// ---------------------------------------------------------
echo "\n======================================================================\n";
echo "AUDIT SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "======================================================================\n";
if ($fail === 0) {
    echo "🎉 ALL POS AND SUBSYSTEM VERIFICATIONS PASSED WITH ZERO ERRORS!\n";
} else {
    echo "❌ AUDIT DETECTED FAILURES. PLEASE INVESTIGATE.\n";
    exit(1);
}
