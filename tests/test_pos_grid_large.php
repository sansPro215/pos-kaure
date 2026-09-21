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

echo "======================================================================\n";
echo "      TEST: POS LARGE GRID DISPLAY & INTERACTION INTEGRITY            \n";
echo "======================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $message): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  [PASS] $message\n";
        $passCount++;
    } else {
        echo "  [FAIL] $message\n";
        $failCount++;
    }
}

// 1. Check Product Data in DB
$products = App\Repositories\ProductRepository::getAll(null, null, true);
$categories = App\Repositories\CategoryRepository::getAll(true);
assertTest(!empty($products), "Found active products for POS: " . count($products));

// 2. Render POS view with pos_layout = 'grid_large'
$settings = App\Repositories\SettingRepository::get();
$settings['pos_layout'] = 'grid_large';
$allUsers = App\Repositories\UserRepository::getAll();
$cashiers = array_values(array_filter($allUsers, function ($u) {
    return $u['role'] === 'CASHIER';
}));
$heldList = [];

ob_start();
require __DIR__ . '/../views/pos/index.php';
$html = ob_get_clean();

assertTest(!empty($html), "POS Large Grid HTML rendered successfully");
assertTest(strpos($html, 'id="posProductGrid"') !== false, "posProductGrid element exists");
assertTest(strpos($html, 'class="pos-grid-scroll"') !== false, "pos-grid-scroll wrapper exists");

// 3. Inspect all pos-product-item elements
$dom = new DOMDocument();
libxml_use_internal_errors(true);
$dom->loadHTML($html);
libxml_clear_errors();

$xpath = new DOMXPath($dom);
$gridNodes = $xpath->query('//div[@id="posProductGrid"]//div[contains(@class, "pos-product-item")]');
assertTest($gridNodes->length > 0, "Found pos-product-item elements in grid (count: " . $gridNodes->length . ")");

$allHaveDataId = true;
$allHaveDataPrice = true;
$allHaveValidNumbers = true;
$innerCardValid = true;

foreach ($gridNodes as $node) {
    /** @var DOMElement $node */
    $dataId = $node->getAttribute('data-id');
    $dataPrice = $node->getAttribute('data-price');
    $dataName = $node->getAttribute('data-name');
    $dataCat = $node->getAttribute('data-category-id');

    if (empty($dataId) || !is_numeric($dataId) || intval($dataId) <= 0) {
        $allHaveDataId = false;
    }
    if ($dataPrice === '' || !is_numeric($dataPrice)) {
        $allHaveDataPrice = false;
    }

    $innerCard = $xpath->query('.//div[contains(@class, "product-card")]', $node)->item(0);
    if (!$innerCard || empty($innerCard->getAttribute('data-id')) || $innerCard->getAttribute('data-price') === '') {
        $innerCardValid = false;
    }
}

assertTest($allHaveDataId, "All .pos-product-item elements have valid positive numeric data-id attribute");
assertTest($allHaveDataPrice, "All .pos-product-item elements have valid numeric data-price attribute");
assertTest($innerCardValid, "All inner .product-card elements have valid data-id and data-price attributes");

// 4. Verify pos.js has safe parsing and guards
$posJs = file_get_contents(__DIR__ . '/../public/assets/js/pos.js');
assertTest(strpos($posJs, 'this.querySelector(\'.product-card\')') !== false, "pos.js safely checks inner card fallback");
assertTest(strpos($posJs, 'if (!productId || isNaN(productId))') !== false, "pos.js has NaN protection in addToCart");
assertTest(strpos($posJs, 'productId > 0 && !isNaN(productId)') !== false, "pos.js guards against invalid clicks");

// 5. Verify Table view is NOT broken
$settings['pos_layout'] = 'table_list';
ob_start();
require __DIR__ . '/../views/pos/index.php';
$tableHtml = ob_get_clean();

assertTest(strpos($tableHtml, 'id="modernPosTable"') !== false, "Table layout modernPosTable renders cleanly when pos_layout = 'table_list'");

echo "\n======================================================================\n";
echo "RESULT: $passCount PASSED, $failCount FAILED\n";
echo "======================================================================\n";

if ($failCount > 0) {
    exit(1);
}
