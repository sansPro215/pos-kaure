<?php

// Verification of rendered HTML for /inventory and /dashboard
$baseUrl = 'http://localhost/kasir';
$cookieFile = __DIR__ . '/verify_cookies.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function req($url, $post = null) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

// 1. Login as owner
$loginPage = req($baseUrl . '/login');
preg_match('/name="_token" value="([a-f0-9]+)"/', $loginPage['body'], $matches);
$token = $matches[1] ?? '';

req($baseUrl . '/login', [
    '_token' => $token,
    'username' => 'owner',
    'password' => 'owner123'
]);

echo "========================================================\n";
echo "  WARUNG KAURE - END-TO-END RENDER & HTML VERIFICATION   \n";
echo "========================================================\n\n";

// 2. Test /inventory
$inv = req($baseUrl . '/inventory');
echo "1. Testing /inventory:\n";
echo " - HTTP Status: {$inv['code']} " . ($inv['code'] === 200 ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Kelola Stok Produk & Persediaan': " . (strpos($inv['body'], 'Kelola Stok Produk & Persediaan') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Stok Produk Menu': " . (strpos($inv['body'], 'Stok Produk Menu') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Stock In' modal: " . (strpos($inv['body'], 'stockInModal') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Stock Out' modal: " . (strpos($inv['body'], 'stockOutModal') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Penyesuaian (Opname)' modal: " . (strpos($inv['body'], 'stockAdjustModal') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains quickStockAction: " . (strpos($inv['body'], 'quickStockAction') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Sidebar has 'Kelola Stok': " . (strpos($inv['body'], 'Kelola Stok') !== false ? "[OK]" : "[FAIL]") . "\n";

// 3. Test /dashboard
$dash = req($baseUrl . '/dashboard');
echo "\n2. Testing /dashboard:\n";
echo " - HTTP Status: {$dash['code']} " . ($dash['code'] === 200 ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains dynamic theme CSS variable reading: " . (strpos($dash['body'], 'getPropertyValue(\'--wk-primary\')') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains themePrimary for chart border: " . (strpos($dash['body'], 'borderColor: themePrimary') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains themePrimary for chart background: " . (strpos($dash['body'], 'rgba(${themePrimaryRgb}, 0.15)') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains themePrimary for doughnut chart: " . (strpos($dash['body'], '[themePrimary, \'#0d6efd\']') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Transaksi Terbaru': " . (strpos($dash['body'], 'Transaksi Terbaru') !== false ? "[OK]" : "[FAIL]") . "\n";

// 4. Test /products
$prod = req($baseUrl . '/products');
echo "\n3. Testing /products:\n";
echo " - HTTP Status: {$prod['code']} " . ($prod['code'] === 200 ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains 'Stok' column in table: " . (strpos($prod['body'], '>Stok</th>') !== false ? "[OK]" : "[FAIL]") . "\n";
echo " - Contains link to '/inventory': " . (strpos($prod['body'], '/kasir/inventory') !== false ? "[OK]" : "[FAIL]") . "\n";

unlink($cookieFile);
echo "\n>>> ALL END-TO-END RENDER CHECKS COMPLETED! <<<\n";
