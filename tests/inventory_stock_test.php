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

use App\Repositories\ProductRepository;
use App\Repositories\StockRepository;
use App\Services\InventoryService;

echo "========================================================\n";
echo "  WARUNG KAURE - STOCK MODULE FUNCTIONAL TEST          \n";
echo "========================================================\n\n";

$passCount = 0;
$totalTests = 0;

function assertTest(bool $condition, string $label) {
    global $passCount, $totalTests;
    $totalTests++;
    if ($condition) {
        $passCount++;
        echo " [PASS] {$label}\n";
    } else {
        echo " [FAIL] {$label}\n";
    }
}

// 1. Get a product to test
$products = ProductRepository::getAll(null, null, true);
assertTest(!empty($products), "At least one active product exists in database");

$targetProd = $products[0];
$prodId = (int)$targetProd['id'];
$initialStock = (float)$targetProd['stock'];
echo "Testing on product: #{$prodId} {$targetProd['name']} (Initial Stock: {$initialStock})\n";

// 2. Test Stock In
$inQty = 15;
InventoryService::stockIn('PRODUCT', $prodId, $inQty, 12000, "Test Stock In Restock", 1);
$afterIn = ProductRepository::findById($prodId);
assertTest((float)$afterIn['stock'] === ($initialStock + $inQty), "Stock In increases product stock accurately ({$initialStock} -> {$afterIn['stock']})");
assertTest($afterIn['stock_tracking_type'] === 'DIRECT', "Stock In ensures stock_tracking_type is DIRECT");

// 3. Test Stock Out
$outQty = 5;
InventoryService::stockOut('PRODUCT', $prodId, $outQty, "Barang rusak / pecah / kemasan cacat", 1);
$afterOut = ProductRepository::findById($prodId);
assertTest((float)$afterOut['stock'] === ($initialStock + $inQty - $outQty), "Stock Out decreases product stock accurately ({$afterIn['stock']} -> {$afterOut['stock']})");

// 4. Test Stock Adjust (Opname)
$opnameQty = 50;
InventoryService::adjustStock('PRODUCT', $prodId, $opnameQty, "Hasil opname fisik berkala", 1);
$afterAdjust = ProductRepository::findById($prodId);
assertTest((float)$afterAdjust['stock'] === (float)$opnameQty, "Stock Adjustment sets physical stock accurately ({$afterOut['stock']} -> {$opnameQty})");

// 5. Test Stock Movement History Ordering (Newest First)
$movements = StockRepository::getMovements('PRODUCT', $prodId, null, null, null, 10);
assertTest(!empty($movements), "Stock movements recorded in ledger");
assertTest($movements[0]['movement_type'] === 'ADJUSTMENT', "Latest recorded movement is at index 0 (Newest First)");
if (count($movements) >= 2) {
    $time0 = strtotime($movements[0]['created_at']);
    $time1 = strtotime($movements[1]['created_at']);
    $id0 = (int)$movements[0]['id'];
    $id1 = (int)$movements[1]['id'];
    assertTest(($time0 > $time1) || ($time0 === $time1 && $id0 >= $id1), "Movements correctly ordered DESC by created_at and id");
}

echo "\n========================================================\n";
echo "RESULT: {$passCount}/{$totalTests} TESTS PASSED\n";
echo "========================================================\n";
