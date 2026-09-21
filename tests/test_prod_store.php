<?php

$baseUrl = 'http://localhost/kasir';
$cookieFile = __DIR__ . '/test_prod_cookies.txt';
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

// 2. Get create page to get CSRF token
$createPage = req($baseUrl . '/products/create');
preg_match('/name="_token" value="([a-f0-9]+)"/', $createPage['body'], $matches);
$csrf = $matches[1] ?? '';

// 3. Post create product
$sku = 'TEST-PROD-' . rand(100, 999);
$storeRes = req($baseUrl . '/products/create', [
    '_token' => $csrf,
    'category_id' => 1,
    'sku' => $sku,
    'name' => 'Kopi Test Otomatis',
    'selling_price' => 25000,
    'cost_price' => 10000,
    'stock' => 20,
    'minimum_stock' => 5
]);

echo "POST /products/create: HTTP Code {$storeRes['code']}\n";
if ($storeRes['code'] === 302) {
    echo "SUCCESS: Product created successfully without fatal error!\n";
} else {
    echo "FAILED: Unexpected status code.\n";
    echo substr($storeRes['body'], 0, 500) . "\n";
}

unlink($cookieFile);
