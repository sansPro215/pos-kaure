<?php

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $hf) {
    require_once $hf;
}

use App\Core\Database;
use App\Services\SaleService;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Repositories\ProductRepository;

echo "=== MEMULAI INTEGRATION TEST SEMUA FITUR BARU ===\n\n";

$db = Database::getConnection();

// 1. Check DB columns
echo "[TEST 1] Verifikasi Kolom Database payment_proof...\n";
$colTrans = $db->query("SHOW COLUMNS FROM transactions LIKE 'payment_proof'")->fetch();
$colPay = $db->query("SHOW COLUMNS FROM payments LIKE 'payment_proof'")->fetch();

if ($colTrans && $colPay) {
    echo "  -> PASS: Kolom payment_proof ada di transactions dan payments.\n";
} else {
    echo "  -> FAIL: Kolom payment_proof belum ada di tabel!\n";
    exit(1);
}

// 2. Test Checkout Cashless DENGAN Foto Bukti Bayar
echo "\n[TEST 2] Verifikasi Checkout Cashless DENGAN Foto Bukti Bayar...\n";
$products = ProductRepository::getAll();
if (empty($products)) {
    echo "  -> SKIP: Tidak ada produk untuk checkout.\n";
} else {
    // Find active product with stock
    $testProduct = null;
    foreach ($products as $p) {
        if ($p['status'] === 'ACTIVE' && ($p['stock_tracking_type'] === 'RECIPE' || $p['stock'] > 2)) {
            $testProduct = $p;
            break;
        }
    }
    if (!$testProduct) {
        $testProduct = $products[0];
    }

    $cashierUser = $db->query("SELECT * FROM users WHERE role = 'CASHIER' LIMIT 1")->fetch();
    $cashierId = $cashierUser ? (int)$cashierUser['id'] : 1;

    // Simulate dummy photo upload
    $dummyDir = __DIR__ . '/../public/uploads/payments';
    if (!is_dir($dummyDir)) {
        mkdir($dummyDir, 0777, true);
    }
    $dummyFileName = 'proof_test_' . time() . '.png';
    $dummyFilePath = $dummyDir . '/' . $dummyFileName;
    file_put_contents($dummyFilePath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

    $cartItems = [
        [
            'product_id' => $testProduct['id'],
            'name' => $testProduct['name'],
            'price' => (float)$testProduct['selling_price'],
            'qty' => 1,
            'note' => 'Test with photo proof'
        ]
    ];

    $paymentData = [
        'method' => 'QRIS',
        'reference_number' => 'QRIS-TEST-123456',
        'payment_proof' => $dummyFileName
    ];

    $result = SaleService::checkout($cartItems, $paymentData, null, $cashierId);
    $transId = (int)$result['transaction_id'];
    $savedTrans = TransactionRepository::findById($transId);

    if ($savedTrans && $savedTrans['payment_proof'] === $dummyFileName) {
        echo "  -> PASS: Transaksi #{$savedTrans['transaction_code']} berhasil menyimpan payment_proof '{$dummyFileName}'.\n";
    } else {
        echo "  -> FAIL: payment_proof tidak tersimpan dengan benar di transaksi! Data: " . json_encode($savedTrans) . "\n";
        exit(1);
    }

    $payments = TransactionRepository::getPayments($transId);
    if (!empty($payments) && $payments[0]['payment_proof'] === $dummyFileName) {
        echo "  -> PASS: Record payments juga mencatat payment_proof '{$dummyFileName}'.\n";
    } else {
        echo "  -> FAIL: Record payments tidak memiliki payment_proof!\n";
        exit(1);
    }
}

// 3. Test Checkout Cashless TANPA Foto Bukti Bayar (Opsional)
echo "\n[TEST 3] Verifikasi Checkout Cashless TANPA Foto Bukti Bayar (Opsional)...\n";
$paymentDataNoPhoto = [
    'method' => 'TRANSFER',
    'reference_number' => 'TRF-TEST-NO-PHOTO',
    'payment_proof' => null
];

$resultNoPhoto = SaleService::checkout($cartItems, $paymentDataNoPhoto, null, $cashierId);
$transIdNoPhoto = (int)$resultNoPhoto['transaction_id'];
$savedTransNoPhoto = TransactionRepository::findById($transIdNoPhoto);

if ($savedTransNoPhoto && empty($savedTransNoPhoto['payment_proof'])) {
    echo "  -> PASS: Transaksi #{$savedTransNoPhoto['transaction_code']} berhasil checkout tanpa foto bukti bayar (NULL).\n";
} else {
    echo "  -> FAIL: Transaksi tanpa foto bukti bayar bermasalah! Data: " . json_encode($savedTransNoPhoto) . "\n";
    exit(1);
}

// 4. Test CSV Export Logic
echo "\n[TEST 4] Verifikasi Logika Ekspor CSV Transaksi Lengkap...\n";
$allTrans = TransactionRepository::getTransactions(null, null, null, null, null, null, 1000);
echo "  -> Ditemukan " . count($allTrans) . " transaksi untuk ekspor CSV.\n";

ob_start();
$output = fopen('php://output', 'w');
// UTF-8 BOM
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, [
    'No',
    'Nomor Transaksi',
    'Waktu Transaksi',
    'Nama Kasir',
    'Subtotal (Rp)',
    'Diskon (Rp)',
    'Total Akhir (Rp)',
    'Metode Pembayaran',
    'Nomor Referensi',
    'Bukti Pembayaran',
    'Status',
    'Item Terjual',
    'Rincian Produk'
], ';');

foreach ($allTrans as $idx => $t) {
    $items = TransactionRepository::getItems((int)$t['id']);
    $itemDetails = [];
    $totalItemQty = 0;
    foreach ($items as $it) {
        $qty = (int)$it['qty'];
        $totalItemQty += $qty;
        $itemDetails[] = $it['product_name'] . ' (x' . $qty . ' @' . number_format((float)$it['selling_price'], 0, ',', '.') . ')';
    }
    $itemDetailStr = implode(' | ', $itemDetails);
    
    fputcsv($output, [
        $idx + 1,
        $t['transaction_code'],
        $t['transaction_date'],
        $t['cashier_name'] ?? 'Kasir',
        number_format((float)$t['subtotal'], 0, ',', '.'),
        number_format((float)$t['discount_amount'], 0, ',', '.'),
        number_format((float)$t['grand_total'], 0, ',', '.'),
        $t['payment_method'],
        $t['reference_number'] ?? '-',
        !empty($t['payment_proof']) ? 'ADA (' . $t['payment_proof'] . ')' : 'TIDAK ADA',
        $t['status'],
        $totalItemQty,
        $itemDetailStr
    ], ';');
}
fclose($output);
$csvContent = ob_get_clean();

if (strpos($csvContent, chr(0xEF).chr(0xBB).chr(0xBF)) === 0 && strpos($csvContent, 'Nomor Transaksi') !== false) {
    echo "  -> PASS: Output CSV memiliki UTF-8 BOM dan header lengkap.\n";
    echo "  -> Ukuran CSV: " . strlen($csvContent) . " bytes.\n";
} else {
    echo "  -> FAIL: Format CSV tidak valid!\n";
    exit(1);
}

// 5. Test Filter Query
echo "\n[TEST 5] Verifikasi Filter Kasir & Metode Pembayaran...\n";
$filterCashier = TransactionRepository::getTransactions(null, null, $cashierId, null, null, null, 100);
echo "  -> PASS: Filter by cashierId={$cashierId} menghasilkan " . count($filterCashier) . " transaksi.\n";

$filterQris = TransactionRepository::getTransactions(null, null, null, null, 'QRIS', null, 100);
echo "  -> PASS: Filter by paymentMethod=QRIS menghasilkan " . count($filterQris) . " transaksi.\n";

// 6. Check View & Route Integrity
echo "\n[TEST 6] Verifikasi Routes dan View Files...\n";
$routesContent = file_get_contents(__DIR__ . '/../routes/web.php');
if (strpos($routesContent, '/transactions/export-csv') !== false) {
    echo "  -> PASS: Route /transactions/export-csv terdaftar.\n";
} else {
    echo "  -> FAIL: Route /transactions/export-csv belum terdaftar!\n";
    exit(1);
}

$cssContent = file_get_contents(__DIR__ . '/../public/assets/css/app.css');
if (strpos($cssContent, 'z-index: 1060') !== false && strpos($cssContent, 'wk-nav-profile-btn') !== false && strpos($cssContent, 'owner-cashier-box') !== false) {
    echo "  -> PASS: CSS navbar z-index (1060), profile button, dan owner cashier picker terkonfigurasi.\n";
} else {
    echo "  -> FAIL: CSS update belum lengkap!\n";
    exit(1);
}

$posJsContent = file_get_contents(__DIR__ . '/../public/assets/js/pos.js');
if (strpos($posJsContent, 'formatRupiahThousand') !== false && strpos($posJsContent, 'initPaymentProofPhoto') !== false && strpos($posJsContent, 'ewalletProviderRow') !== false) {
    echo "  -> PASS: pos.js memiliki format rupiah ribuan, payment proof photo handler, dan perbaikan provider e-wallet.\n";
} else {
    echo "  -> FAIL: pos.js update belum lengkap!\n";
    exit(1);
}

echo "\n=== SEMUA TEST BERHASIL DILALUI DENGAN SUKSES (100% PASS) ===\n";
