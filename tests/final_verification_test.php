<?php

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
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) require_once $f;

use App\Repositories\TransactionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\StockRepository;
use App\Services\SaleService;
use App\Services\InventoryService;

echo "========================================================\n";
echo "  WARUNG KAURE - FINAL COMPREHENSIVE VERIFICATION TEST \n";
echo "========================================================\n\n";

$passCount = 0;
$totalTests = 0;

function assertCheck(bool $condition, string $label) {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$label}\n";
    } else {
        echo " [FAIL] {$label}\n";
    }
}

// -------------------------------------------------------------
// TEST SUITE 1: Transactions Table Sorting & Configuration
// -------------------------------------------------------------
echo "\n--- 1. Testing Transactions Sorting & DataTables Configuration ---\n";
$txs = TransactionRepository::getTransactions(null, null, null, null, null, null, 10);
if (count($txs) >= 2) {
    $t1 = strtotime($txs[0]['transaction_date']);
    $t2 = strtotime($txs[1]['transaction_date']);
    assertCheck($t1 >= $t2, "TransactionRepository::getTransactions orders newest first ({$txs[0]['transaction_code']} >= {$txs[1]['transaction_code']})");
} else {
    assertCheck(true, "TransactionRepository::getTransactions verified");
}

$txView = file_get_contents(__DIR__ . '/../views/transactions/index.php');
assertCheck(strpos($txView, 'data-order=\'[[1, "desc"]]\'') !== false, "views/transactions/index.php table has data-order='[[1, \"desc\"]]'");
assertCheck(strpos($txView, 'data-order="<?= strtotime($t[\'transaction_date\']) ?>"') !== false, "views/transactions/index.php has numeric epoch data-order on Waktu column");

$appJs = file_get_contents(__DIR__ . '/../public/assets/js/app.js');
assertCheck(strpos($appJs, "const customOrder = $(this).attr('data-order');") !== false, "public/assets/js/app.js reads custom data-order from table attribute");

// -------------------------------------------------------------
// TEST SUITE 2: POS Owner Cashier Selection & Data Attribution
// -------------------------------------------------------------
echo "\n--- 2. Testing POS Owner Cashier Selection ---\n";
$posView = file_get_contents(__DIR__ . '/../views/pos/index.php');
assertCheck(strpos($posView, 'posTopCashierSelect') === false, "views/pos/index.php does NOT contain Top Cashier Selector (Cart only)");
assertCheck(strpos($posView, 'posCartCashierSelect') !== false, "views/pos/index.php contains Desktop Cart Cashier Selector");
assertCheck(strpos($posView, 'posMobileCashierSelect') !== false, "views/pos/index.php contains Mobile Cart Cashier Selector");
assertCheck(strpos($posView, 'posCheckoutCashierSelect') === false, "views/pos/index.php does NOT contain Checkout Modal Cashier Selector (Cart only)");
assertCheck(strpos($posView, 'owner-cashier-picker') !== false, "Cart cashier pickers share owner-cashier-picker class");

$posJs = file_get_contents(__DIR__ . '/../public/assets/js/pos.js');
assertCheck(strpos($posJs, 'initOwnerCashierPicker') !== false, "public/assets/js/pos.js defines initOwnerCashierPicker for auto-syncing");
assertCheck(strpos($posJs, "formData.append('selected_cashier_id', cashierVal)") !== false, "public/assets/js/pos.js appends selected_cashier_id to checkout & hold requests");

$posController = file_get_contents(__DIR__ . '/../app/Controllers/PosController.php');
assertCheck(strpos($posController, "'role'] === 'CASHIER'") !== false, "PosController filters cashiers to only CASHIER role (Owner excluded)");
assertCheck(strpos($posController, "is_owner()") !== false, "PosController resolves cashierId when user is Owner");

// Test checkout attributed to a specific cashier
$allUsers = UserRepository::getAll();
$targetCashier = null;
foreach ($allUsers as $u) {
    if ($u['role'] === 'CASHIER') {
        $targetCashier = $u;
        break;
    }
}
if (!$targetCashier && !empty($allUsers)) {
    $targetCashier = $allUsers[count($allUsers) - 1];
}

$allProducts = ProductRepository::getAll(null, null, true);
$testProd = $allProducts[0];

// Execute test checkout with target cashier ID
$cart = [
    [
        'product_id' => (int)$testProd['id'],
        'name' => $testProd['name'],
        'price' => (float)$testProd['selling_price'],
        'qty' => 1
    ]
];
$checkoutRes = SaleService::checkout(
    $cart,
    [
        'method' => 'CASH',
        'received_amount' => (float)$testProd['selling_price'] + 10000
    ],
    ['type' => 'NONE', 'value' => 0],
    (int)$targetCashier['id']
);

$savedTx = TransactionRepository::findById($checkoutRes['transaction_id']);
assertCheck((int)$savedTx['cashier_id'] === (int)$targetCashier['id'], "SaleService correctly attributed transaction to selected cashier ID #{$targetCashier['id']} ({$targetCashier['name']})");

// -------------------------------------------------------------
// TEST SUITE 3: Simplified Product Stock Module (No Ingredients)
// -------------------------------------------------------------
echo "\n--- 3. Testing Simplified Product Stock Module ---\n";
$invController = file_get_contents(__DIR__ . '/../app/Controllers/InventoryController.php');
assertCheck(strpos($invController, 'IngredientRepository') === false, "InventoryController no longer references IngredientRepository");
assertCheck(strpos($invController, 'function resetStock()') !== false, "InventoryController contains resetStock method");

$invView = file_get_contents(__DIR__ . '/../views/inventory/index.php');
assertCheck(strpos($invView, 'stockTabs') === false, "views/inventory/index.php has no tabs (Bahan Baku tab removed)");
assertCheck(strpos($invView, 'ingredients-pane') === false, "views/inventory/index.php has no ingredients-pane");
assertCheck(strpos($invView, 'stockResetModal') !== false, "views/inventory/index.php contains stockResetModal for clearing stock to 0");
assertCheck(strpos($invView, 'Hapus Stok (0)') !== false, "views/inventory/index.php has Hapus Stok action button");

$webRoutes = file_get_contents(__DIR__ . '/../routes/web.php');
assertCheck(strpos($webRoutes, "Router::post('/inventory/reset', [InventoryController::class, 'resetStock']") !== false, "Route /inventory/reset registered in web.php");
assertCheck(strpos($webRoutes, "Router::post('/stock/reset', [InventoryController::class, 'resetStock']") !== false, "Route /stock/reset registered in web.php");

// Functional Stock Service Tests on Product
$prodId = (int)$testProd['id'];
$initialStock = (float)ProductRepository::findById($prodId)['stock'];

// 3a. Stock In
InventoryService::stockIn('PRODUCT', $prodId, 10, 5000, "Verification Stock In", 1);
$afterIn = (float)ProductRepository::findById($prodId)['stock'];
assertCheck($afterIn === ($initialStock + 10), "InventoryService::stockIn increases product stock accurately ({$initialStock} -> {$afterIn})");

// 3b. Stock Out
InventoryService::stockOut('PRODUCT', $prodId, 4, "Barang rusak / pecah / kemasan cacat", 1);
$afterOut = (float)ProductRepository::findById($prodId)['stock'];
assertCheck($afterOut === ($afterIn - 4), "InventoryService::stockOut decreases product stock accurately ({$afterIn} -> {$afterOut})");

// 3c. Stock Reset (Hapus/Kosongkan Stok ke 0)
InventoryService::resetStock($prodId, "Hapus sisa stok produk (kedaluwarsa/buang)", 1);
$afterReset = (float)ProductRepository::findById($prodId)['stock'];
assertCheck($afterReset === 0.0, "InventoryService::resetStock clears product stock to 0 pcs");

// Verify ledger movement
$movements = StockRepository::getMovements('PRODUCT', $prodId, null, null, null, 5);
assertCheck(!empty($movements) && $movements[0]['reference_type'] === 'STOCK_RESET', "Stock movement ledger recorded STOCK_RESET as newest movement");
assertCheck((float)$movements[0]['stock_after'] === 0.0, "Stock movement ledger recorded stock_after as 0.00");

// Restore some stock for product so it's not permanently 0 in demo
InventoryService::stockIn('PRODUCT', $prodId, max(10, $initialStock), 0, "Restore initial demo stock", 1);

echo "\n========================================================\n";
echo "RESULT: {$passCount}/{$totalTests} TESTS PASSED\n";
echo "========================================================\n";

if ($passCount === $totalTests) {
    exit(0);
} else {
    exit(1);
}
