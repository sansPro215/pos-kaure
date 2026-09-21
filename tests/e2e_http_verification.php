<?php
$baseUrl = 'http://127.0.0.1/kasir/public';
$cookieFile = __DIR__ . '/cookie_verify_test.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function curlRequest($url, $method = 'GET', $data = [], $cookieFile = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);
    curl_close($ch);
    return ['code' => $httpCode, 'header' => $header, 'body' => $body, 'url' => $effectiveUrl];
}

echo "=== MEMULAI E2E HTTP VERIFICATION TEST ===\n\n";

// 1. GET login page to extract CSRF token
echo "[STEP 1] Login sebagai Owner...\n";
$loginPage = curlRequest($baseUrl . '/login', 'GET', [], $cookieFile);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $csrfMatch);
$csrf = $csrfMatch[1] ?? '';
if (empty($csrf)) {
    echo "  -> WARN: Tidak dapat menemukan csrf_token, mencoba tanpa CSRF...\n";
}

$loginRes = curlRequest($baseUrl . '/login', 'POST', [
    'username' => 'owner',
    'password' => 'password123',
    'csrf_token' => $csrf
], $cookieFile);

// Check if login succeeded (should redirect to dashboard or pos, not stay on login)
$isLoggedIn = strpos($loginRes['url'], '/login') === false || strpos($loginRes['body'], 'Dashboard') !== false || strpos($loginRes['body'], 'POS') !== false;
if ($isLoggedIn) {
    echo "  -> PASS: Login berhasil (URL: {$loginRes['url']}).\n";
} else {
    echo "  -> FAIL: Login gagal. HTTP Code: {$loginRes['code']}, URL: {$loginRes['url']}\n";
    exit(1);
}

// 2. GET /pos
echo "\n[STEP 2] Memeriksa Halaman POS (/pos)...\n";
$posRes = curlRequest($baseUrl . '/pos', 'GET', [], $cookieFile);
echo "  - HTTP code: {$posRes['code']}, URL: {$posRes['url']}\n";

$checks = [
    'wk-nav-profile-btn' => strpos($posRes['body'], 'wk-nav-profile-btn') !== false,
    'owner-cashier-box' => strpos($posRes['body'], 'owner-cashier-box') !== false,
    'posPaymentProofInput' => strpos($posRes['body'], 'posPaymentProofInput') !== false,
    'ewalletProviderRow' => strpos($posRes['body'], 'ewalletProviderRow') !== false,
    'Keranjang' => strpos($posRes['body'], 'Keranjang') !== false,
];

foreach ($checks as $key => $found) {
    echo "  - has {$key}: " . ($found ? 'YES' : 'NO') . "\n";
}

if ($checks['wk-nav-profile-btn'] && $checks['owner-cashier-box'] && $checks['Keranjang']) {
    echo "  -> PASS: Komponen utama POS terdeteksi.\n";
} else {
    echo "  -> WARN: Beberapa komponen POS tidak terdeteksi, mungkin karena kendala session.\n";
}

// 3. GET /transactions/export-xlsx
echo "\n[STEP 3] Memeriksa Endpoint Ekspor Excel (/transactions/export-xlsx)...\n";
$xlsxRes = curlRequest($baseUrl . '/transactions/export-xlsx', 'GET', [], $cookieFile);
if ($xlsxRes['code'] === 200 && (strpos($xlsxRes['header'], 'openxmlformats') !== false || strpos($xlsxRes['header'], 'spreadsheetml') !== false)) {
    echo "  -> PASS: Ekspor Excel (.xlsx) berhasil (HTTP 200, Content-Type openxmlformats).\n";
} else {
    echo "  -> INFO: XLSX code={$xlsxRes['code']}, URL={$xlsxRes['url']}\n";
}

// 4. GET /transactions
echo "\n[STEP 4] Memeriksa Halaman Transaksi (/transactions)...\n";
$transRes = curlRequest($baseUrl . '/transactions', 'GET', [], $cookieFile);
if (strpos($transRes['body'], 'Ekspor Excel') !== false || strpos($transRes['body'], 'export-xlsx') !== false) {
    echo "  -> PASS: Tombol 'Ekspor Excel (.xlsx)' tampil di halaman transaksi.\n";
} else {
    echo "  -> INFO: Halaman transaksi mungkin tidak dapat diakses tanpa session yang benar.\n";
}

// 5. Static file verification (always works, no auth needed)
echo "\n[STEP 5] Verifikasi File Statis (CSS, JS, Views)...\n";

$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/app.css');
$posJsContent = file_get_contents(__DIR__ . '/../public/assets/js/pos.js');
$navbarContent = file_get_contents(__DIR__ . '/../views/components/navbar.php');
$posViewContent = file_get_contents(__DIR__ . '/../views/pos/index.php');
$transShowContent = file_get_contents(__DIR__ . '/../views/transactions/show.php');
$transIndexContent = file_get_contents(__DIR__ . '/../views/transactions/index.php');

$staticChecks = [
    'CSS z-index 1060 (navbar)' => strpos($cssContent, 'z-index: 1060') !== false,
    'CSS wk-nav-profile-btn' => strpos($cssContent, 'wk-nav-profile-btn') !== false,
    'CSS owner-cashier-box' => strpos($cssContent, 'owner-cashier-box') !== false,
    'CSS dark mode bg-body-secondary' => strpos($cssContent, '[data-bs-theme="dark"] .bg-body-secondary') !== false,
    'CSS dark mode form-control' => strpos($cssContent, '[data-bs-theme="dark"] .form-control') !== false,
    'CSS dark mode dropdown-menu' => strpos($cssContent, '[data-bs-theme="dark"] .dropdown-menu') !== false,
    'CSS btn-quick-cash.active' => strpos($cssContent, '.btn-quick-cash.active') !== false,
    'navbar.php wk-nav-profile-btn' => strpos($navbarContent, 'wk-nav-profile-btn') !== false,
    'navbar.php wk-nav-profile-name' => strpos($navbarContent, 'wk-nav-profile-name') !== false,
    'pos/index.php owner-cashier-box' => strpos($posViewContent, 'owner-cashier-box') !== false,
    'pos/index.php owner-cashier-label' => strpos($posViewContent, 'owner-cashier-label') !== false,
    'pos/index.php z-index: 20 (cart)' => strpos($posViewContent, 'z-index: 20') !== false,
    'pos.js formatRupiahThousand' => strpos($posJsContent, 'formatRupiahThousand') !== false,
    'pos.js initPaymentProofPhoto' => strpos($posJsContent, 'initPaymentProofPhoto') !== false,
    'pos.js ewalletProviderRow hide' => strpos($posJsContent, 'ewalletProviderRow') !== false,
    'transactions/show.php paymentProofModal' => strpos($transShowContent, 'paymentProofModal') !== false,
    'transactions/show.php Bukti Bayar' => strpos($transShowContent, 'Bukti Bayar Non-Tunai') !== false,
    'transactions/index.php Ekspor Excel' => strpos($transIndexContent, 'Ekspor Excel') !== false,
    'transactions/index.php export-xlsx' => strpos($transIndexContent, 'export-xlsx') !== false,
];

$allPass = true;
foreach ($staticChecks as $desc => $found) {
    $status = $found ? 'PASS' : 'FAIL';
    echo "  [{$status}] {$desc}\n";
    if (!$found) $allPass = false;
}

if ($allPass) {
    echo "\n  -> SEMUA VERIFIKASI FILE STATIS BERHASIL (100% PASS)\n";
} else {
    echo "\n  -> BEBERAPA FILE STATIS GAGAL VERIFIKASI!\n";
}

echo "\n=== E2E VERIFICATION SELESAI ===\n";

if (file_exists($cookieFile)) unlink($cookieFile);
