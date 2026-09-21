<?php
$baseUrl = 'http://127.0.0.1/kasir/public';
$cookieFile = __DIR__ . '/cookie_debug.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$ch = curl_init($baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['username' => 'owner', 'password' => 'password123']));
$body = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

echo "Login code: $code\n";
echo "Effective URL: $url\n";
echo "Body length: " . strlen($body) . "\n";

$ch2 = curl_init($baseUrl . '/pos');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch2, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch2, CURLOPT_COOKIEFILE, $cookieFile);
$posBody = curl_exec($ch2);
$posCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$posUrl = curl_getinfo($ch2, CURLINFO_EFFECTIVE_URL);
curl_close($ch2);

echo "\nPOS code: $posCode\n";
echo "POS URL: $posUrl\n";
echo "POS body length: " . strlen($posBody) . "\n";
echo "Has navbar: " . (strpos($posBody, 'wk-navbar') !== false ? 'YES' : 'NO') . "\n";
echo "Has wk-nav-profile-btn: " . (strpos($posBody, 'wk-nav-profile-btn') !== false ? 'YES' : 'NO') . "\n";
echo "Has owner-cashier-box: " . (strpos($posBody, 'owner-cashier-box') !== false ? 'YES' : 'NO') . "\n";
echo "Has posPaymentProofInput: " . (strpos($posBody, 'posPaymentProofInput') !== false ? 'YES' : 'NO') . "\n";
echo "Has Keranjang: " . (strpos($posBody, 'Keranjang') !== false ? 'YES' : 'NO') . "\n";
echo "First 500: " . substr($posBody, 0, 500) . "\n";

if (file_exists($cookieFile)) unlink($cookieFile);
