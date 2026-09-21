<?php

// HTTP Endpoint Smoke Test against Apache
$baseUrl = 'http://localhost/kasir';
$cookieFile = __DIR__ . '/test_cookies.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function httpReq($url, $postData = null, $follow = false) {
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    }
    if ($follow) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'headers' => $headers, 'body' => $body];
}

echo "Testing Apache HTTP Endpoints:\n";

// 1. GET /login
$res = httpReq($baseUrl . '/login');
echo "GET /login: Code {$res['code']}" . ($res['code'] === 200 ? " [OK]" : " [FAIL]") . "\n";

// Extract CSRF token from login HTML
preg_match('/name="_token" value="([a-f0-9]+)"/', $res['body'], $matches);
$token = $matches[1] ?? '';

// 2. POST /login with kasir
$loginRes = httpReq($baseUrl . '/login', [
    '_token' => $token,
    'username' => 'kasir',
    'password' => 'kasir123'
]);
echo "POST /login (kasir): Code {$loginRes['code']}" . ($loginRes['code'] === 302 ? " [OK - Redirect]" : " [FAIL]") . "\n";

// 3. GET /pos as kasir
$posRes = httpReq($baseUrl . '/pos');
echo "GET /pos (as kasir): Code {$posRes['code']}" . ($posRes['code'] === 200 ? " [OK]" : " [FAIL]") . "\n";

// 4. GET /dashboard as kasir (Role Protection)
$dashKasirRes = httpReq($baseUrl . '/dashboard');
echo "GET /dashboard (as kasir - Role protection): Code {$dashKasirRes['code']}" . ($dashKasirRes['code'] === 302 ? " [OK - Blocked & Redirected]" : " [FAIL]") . "\n";

// 5. Logout
httpReq($baseUrl . '/logout');
if (file_exists($cookieFile)) unlink($cookieFile);

// 6. Login as owner
$loginPage = httpReq($baseUrl . '/login');
preg_match('/name="_token" value="([a-f0-9]+)"/', $loginPage['body'], $matches);
$ownerToken = $matches[1] ?? '';

$ownerLogin = httpReq($baseUrl . '/login', [
    '_token' => $ownerToken,
    'username' => 'owner',
    'password' => 'owner123'
]);
echo "POST /login (owner): Code {$ownerLogin['code']}" . ($ownerLogin['code'] === 302 ? " [OK - Redirect]" : " [FAIL]") . "\n";

// 7. Verify all owner pages
$routes = [
    '/dashboard' => 'Dashboard',
    '/pos' => 'POS Kasir',
    '/products' => 'Produk',
    '/products/create' => 'Tambah Produk',
    '/categories' => 'Kategori',
    '/transactions' => 'Riwayat Transaksi',
    '/reports/sales' => 'Laporan Penjualan',
    '/reports/payments' => 'Laporan Pembayaran',
    '/reports/profit' => 'Laporan Laba Rugi',
    '/expenses' => 'Pengeluaran Operasional',
    '/users' => 'Manajemen Pengguna',
    '/attendance' => 'Data Absensi',
    '/payroll' => 'Manajemen Payroll',
    '/audit' => 'Audit Trail',
    '/settings' => 'Pengaturan Kedai',
];

$allOk = true;
foreach ($routes as $route => $name) {
    $r = httpReq($baseUrl . $route);
    $ok = ($r['code'] === 200);
    if (!$ok) $allOk = false;
    echo "GET {$route} ({$name}): Code {$r['code']}" . ($ok ? " [OK]" : " [FAIL]") . "\n";
}

// 8. Test Stock Removal Redirects (must redirect to /products)
$redirectRoutes = ['/inventory', '/stock', '/ingredients', '/recipes'];
foreach ($redirectRoutes as $rr) {
    $r = httpReq($baseUrl . $rr);
    $isRedirect = ($r['code'] === 302);
    if (!$isRedirect) $allOk = false;
    echo "GET {$rr} (Stock Module Removed -> Redirect): Code {$r['code']}" . ($isRedirect ? " [OK - Redirect]" : " [FAIL]") . "\n";
}

if ($allOk) {
    echo "\n>>> ALL OWNER HTTP ROUTES & LEGACY REDIRECTS VERIFIED! <<<\n";
} else {
    echo "\nSOME ROUTES FAILED!\n";
    exit(1);
}

unlink($cookieFile);
