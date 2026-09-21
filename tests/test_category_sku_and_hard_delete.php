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

use App\Core\Database;
use App\Repositories\ProductRepository;
use App\Repositories\CategoryRepository;

echo "========================================================\n";
echo "  TEST: SEQUENTIAL CATEGORY SKU & HARD DELETE           \n";
echo "========================================================\n\n";

$pass = 0;
$total = 0;

function checkTest($cond, $label) {
    global $pass, $total;
    $total++;
    if ($cond) {
        $pass++;
        echo " [PASS] {$label}\n";
    } else {
        echo " [FAIL] {$label}\n";
        exit(1);
    }
}

// 1. Verify Category-aware SKU generation
$catKopi = CategoryRepository::findById(1); // Kopi
$skuKopi = ProductRepository::generateSku(1);
echo "Category 1 ('{$catKopi['name']}') Suggested SKU: {$skuKopi}\n";
checkTest($skuKopi === 'PD-KOPI-2', "Category Kopi generates PD-KOPI-2 (because 1 and 10 are used)");

$catSnack = CategoryRepository::findById(4); // Snack & Pastry
$skuSnack = ProductRepository::generateSku(4);
echo "Category 4 ('{$catSnack['name']}') Suggested SKU: {$skuSnack}\n";
checkTest($skuSnack === 'PD-SNACKPASTR-1', "Category Snack generates PD-SNACKPASTR-1 (first available slot)");

// 2. Create product with PD-KOPI-2
$newId = ProductRepository::create([
    'category_id' => 1,
    'sku' => $skuKopi,
    'name' => 'Kopi Urut Test',
    'selling_price' => 18000,
    'cost_price' => 7000,
    'stock' => 10,
    'minimum_stock' => 2,
    'status' => 'ACTIVE'
]);
echo "Created product #{$newId} with SKU {$skuKopi}\n";

// 3. Verify next SKU in Kopi is now 3
$nextSkuKopi = ProductRepository::generateSku(1);
echo "Next SKU in Category 1 is now: {$nextSkuKopi}\n";
checkTest($nextSkuKopi === 'PD-KOPI-3', "Next SKU in Kopi advances to PD-KOPI-3");

// 4. Test HARD DELETE
echo "\nTesting Hard Delete on Product #{$newId}...\n";
$deleted = ProductRepository::delete($newId);
checkTest($deleted === true, "ProductRepository::delete returned true");

// 5. Verify product physically does NOT exist in database table
$dbCheck = Database::fetch("SELECT * FROM products WHERE id = ?", [$newId]);
checkTest($dbCheck === null, "Product #{$newId} is completely removed from products table (dbCheck is null)");

// 6. Verify number 2 is available again in Category Kopi
$recheckSku = ProductRepository::generateSku(1);
echo "After deletion, available SKU in Category 1: {$recheckSku}\n";
checkTest($recheckSku === 'PD-KOPI-2', "Slot 2 is recycled and available again in Category 1 after deletion");

echo "\n========================================================\n";
echo "RESULT: {$pass}/{$total} TESTS PASSED!\n";
echo "========================================================\n";
