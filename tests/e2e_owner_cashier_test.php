<?php

// Test script simulating Owner login, viewing /pos, and checking out with selected cashier

$baseUrl = 'http://127.0.0.1/kasir';
$cookieFile = __DIR__ . '/cookie_owner_test.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "========================================================\n";
echo "  E2E TEST: OWNER SELECTING CASHIER IN POS CART        \n";
echo "========================================================\n\n";

function curlReq($url, $method = 'GET', $data = [], $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $response];
}

// 1. Get CSRF token from Login page
$loginPage = curlReq("{$baseUrl}/login", 'GET', [], $cookieFile);
preg_match('/name="_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
$csrfToken = $matches[1] ?? '';
echo "1. Login CSRF token: {$csrfToken}\n";

// 2. Login as Owner (username: owner, password: password)
$loginRes = curlReq("{$baseUrl}/login", 'POST', [
    '_token' => $csrfToken,
    'username' => 'owner',
    'password' => 'owner123'
], $cookieFile);
echo "2. Login status code: {$loginRes['code']}\n";

// 3. Open /pos as Owner
$posRes = curlReq("{$baseUrl}/pos", 'GET', [], $cookieFile);
echo "3. /pos status code: {$posRes['code']}\n";

// Verify that Cashier Selector is present in Cart
$hasCartSelect = (strpos($posRes['body'], 'posCartCashierSelect') !== false);
// Extract posCartCashierSelect content
preg_match('/<select id="posCartCashierSelect"[^>]*>(.*?)<\/select>/s', $posRes['body'], $selectMatches);
$selectHtml = $selectMatches[1] ?? '';

$hasAndi = (strpos($selectHtml, 'Andi Pratama') !== false);
echo "   - Contains Andi Pratama option: " . ($hasAndi ? "YES [PASS]" : "NO [FAIL]") . "\n";

$hasSiti = (strpos($selectHtml, 'Siti Rahma') !== false);
echo "   - Contains Siti Rahma option: " . ($hasSiti ? "YES [PASS]" : "NO [FAIL]") . "\n";

$hasOwnerInDropdown = (strpos($selectHtml, 'Budi Santoso') !== false);
echo "   - Contains Owner in dropdown: " . ($hasOwnerInDropdown ? "YES [UNWANTED]" : "NO [PASS - Owner successfully excluded]") . "\n";

// Extract CSRF token from POS page
preg_match('/name="_token"\s+value="([^"]+)"/', $posRes['body'], $posMatches);
$posCsrf = $posMatches[1] ?? '';

// 4. Perform Checkout with selected_cashier_id = 2 (Andi Pratama)
// Get an active product
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

$products = App\Repositories\ProductRepository::getAll(null, null, true);
$prod = $products[0];

$cartJson = json_encode([
    [
        'product_id' => (int)$prod['id'],
        'name' => $prod['name'],
        'price' => (float)$prod['selling_price'],
        'qty' => 1
    ]
]);

$checkoutPayload = [
    '_token' => $posCsrf,
    'cart_json' => $cartJson,
    'payment_method' => 'CASH',
    'received_amount' => (float)$prod['selling_price'] + 10000,
    'selected_cashier_id' => 2 // Andi Pratama
];

$checkoutRes = curlReq("{$baseUrl}/pos/checkout", 'POST', $checkoutPayload, $cookieFile);
echo "\n4. Checkout Response: {$checkoutRes['body']}\n";
$checkoutData = json_decode($checkoutRes['body'], true);

if (!empty($checkoutData['transaction_id'])) {
    $txId = $checkoutData['transaction_id'];
    $savedTx = App\Repositories\TransactionRepository::findById($txId);
    echo "5. Saved Transaction ID: {$txId}\n";
    echo "   - cashier_id in DB: {$savedTx['cashier_id']}\n";
    
    // Check cashier name in transactions list
    $txPage = curlReq("{$baseUrl}/transactions", 'GET', [], $cookieFile);
    $hasCashierNameInTable = (strpos($txPage['body'], 'Andi Pratama') !== false);
    echo "   - Transaction Table displays 'Andi Pratama': " . ($hasCashierNameInTable ? "YES [PASS]" : "NO [FAIL]") . "\n";
    // Test 2: Checkout with Siti Rahma (ID = 3)
    $cartJson2 = json_encode([
        [
            'product_id' => (int)$prod['id'],
            'name' => $prod['name'],
            'price' => (float)$prod['selling_price'],
            'qty' => 1
        ]
    ]);
    $checkoutPayload2 = [
        '_token' => $posCsrf,
        'cart_json' => $cartJson2,
        'payment_method' => 'CASH',
        'received_amount' => (float)$prod['selling_price'] + 5000,
        'selected_cashier_id' => 3 // Siti Rahma
    ];
    $checkoutRes2 = curlReq("{$baseUrl}/pos/checkout", 'POST', $checkoutPayload2, $cookieFile);
    $checkoutData2 = json_decode($checkoutRes2['body'], true);
    if (!empty($checkoutData2['transaction_id'])) {
        $txId2 = $checkoutData2['transaction_id'];
        $savedTx2 = App\Repositories\TransactionRepository::findById($txId2);
        echo "\n6. Second Checkout (Siti Rahma) - Saved Transaction ID: {$txId2}\n";
        echo "   - cashier_id in DB: {$savedTx2['cashier_id']} (Expected: 3)\n";
        echo "   - cashier_name: {$savedTx2['cashier_name']}\n";
        $passSiti = ($savedTx2['cashier_id'] == 3 && strpos($savedTx2['cashier_name'], 'Siti Rahma') !== false);
        echo "   - Siti Rahma Transaction Match: " . ($passSiti ? "YES [PASS]" : "NO [FAIL]") . "\n";
    }
} else {
    echo "FAILED: Checkout did not return transaction_id\n";
}

if (file_exists($cookieFile)) unlink($cookieFile);
echo "\nDone!\n";
