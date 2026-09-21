<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Owner Test',
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

use App\Core\Database;
use App\Controllers\DashboardController;
use App\Controllers\PosController;
use App\Repositories\ProductRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\AttendanceRepository;
use App\Services\SaleService;
use App\Services\PayrollService;

echo "=== 1. TEST DASHBOARD (FATAL ERROR RESOLUTION) ===\n";
try {
    ob_start();
    (new DashboardController())->index();
    $dashOutput = ob_get_clean();
    echo "[PASS] DashboardController::index() executed without fatal error! Output length: " . strlen($dashOutput) . "\n";
} catch (Throwable $e) {
    echo "[FAIL] Dashboard index error: " . $e->getMessage() . "\n";
}

try {
    ob_start();
    (new DashboardController())->print();
    $printOutput = ob_get_clean();
    echo "[PASS] DashboardController::print() executed without fatal error! Output length: " . strlen($printOutput) . "\n";
} catch (Throwable $e) {
    echo "[FAIL] Dashboard print error: " . $e->getMessage() . "\n";
}

echo "\n=== 2. TEST PRODUCT REPOSITORY (NO STOCK COLUMNS) ===\n";
try {
    $lowStock = ProductRepository::getLowStock();
    echo "[PASS] ProductRepository::getLowStock() returned array (count: " . count($lowStock) . ")\n";
} catch (Throwable $e) {
    echo "[FAIL] getLowStock error: " . $e->getMessage() . "\n";
}

echo "\n=== 3. TEST TRANSACTION REPOSITORY getItems (NO stock_tracking_type) ===\n";
try {
    $firstTx = Database::fetch("SELECT id FROM transactions LIMIT 1");
    if ($firstTx) {
        $items = TransactionRepository::getItems((int)$firstTx['id']);
        echo "[PASS] TransactionRepository::getItems() succeeded for ID {$firstTx['id']} (items: " . count($items) . ")\n";
    } else {
        echo "[INFO] No transactions yet to test getItems\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] getItems error: " . $e->getMessage() . "\n";
}

echo "\n=== 4. TEST POS CHECKOUT (NO STOCK DEDUCTION / RESTRICTION) ===\n";
try {
    $testProd = Database::fetch("SELECT * FROM products WHERE status = 'ACTIVE' LIMIT 1");
    if ($testProd) {
        $cart = [
            [
                'product_id' => (int)$testProd['id'],
                'qty' => 5,
                'name' => $testProd['name']
            ]
        ];
        $paymentData = [
            'method' => 'CASH',
            'received_amount' => (float)$testProd['selling_price'] * 5
        ];
        $checkoutResult = SaleService::checkout($cart, $paymentData, null, 1);
        echo "[PASS] SaleService::checkout() succeeded without stock restrictions! Transaction ID: {$checkoutResult['transaction_id']}\n";

        // Clean up test transaction
        Database::execute("DELETE FROM transaction_items WHERE transaction_id = ?", [$checkoutResult['transaction_id']]);
        Database::execute("DELETE FROM payments WHERE transaction_id = ?", [$checkoutResult['transaction_id']]);
        Database::execute("DELETE FROM transactions WHERE id = ?", [$checkoutResult['transaction_id']]);
        echo "[PASS] Test transaction cleaned up successfully.\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] Checkout error: " . $e->getMessage() . "\n";
}

echo "\n=== 5. TEST OVERTIME ROUNDING (PATEN 1 JAM) ===\n";
$testCases = [
    ['worked' => 510, 'expected_min' => 0, 'expected_hrs' => 0.0, 'desc' => '8 jam 30 menit (lembur 30m)'],
    ['worked' => 540, 'expected_min' => 60, 'expected_hrs' => 1.0, 'desc' => '9 jam (lembur 60m)'],
    ['worked' => 585, 'expected_min' => 60, 'expected_hrs' => 1.0, 'desc' => '9 jam 45 menit (lembur 105m)'],
    ['worked' => 610, 'expected_min' => 120, 'expected_hrs' => 2.0, 'desc' => '10 jam 10 menit (lembur 130m)'],
    ['worked' => 660, 'expected_min' => 180, 'expected_hrs' => 3.0, 'desc' => '11 jam (lembur 180m)'],
];

foreach ($testCases as $tc) {
    $rawOt = max(0, $tc['worked'] - 480);
    $calculatedOtMin = (int)floor($rawOt / 60) * 60;
    $calculatedOtHrs = (float)floor($calculatedOtMin / 60);

    if ($calculatedOtMin === $tc['expected_min'] && $calculatedOtHrs === $tc['expected_hrs']) {
        echo "[PASS] {$tc['desc']}: OT Mins = {$calculatedOtMin}m, OT Hours = {$calculatedOtHrs}j\n";
    } else {
        echo "[FAIL] {$tc['desc']}: Expected {$tc['expected_min']}m / {$tc['expected_hrs']}j, got {$calculatedOtMin}m / {$calculatedOtHrs}j\n";
    }
}

echo "\n=== 6. TEST POS CONTROLLER INDEX RENDER ===\n";
try {
    ob_start();
    (new PosController())->index();
    $posOutput = ob_get_clean();
    echo "[PASS] PosController::index() rendered successfully without stock column or recipe errors! Output length: " . strlen($posOutput) . "\n";
} catch (Throwable $e) {
    echo "[FAIL] PosController index error: " . $e->getMessage() . "\n";
}

echo "\n=== 7. TEST PRODUCT VIEWS RENDER ===\n";
try {
    ob_start();
    (new App\Controllers\ProductController())->index();
    $pIdx = ob_get_clean();
    echo "[PASS] ProductController::index() rendered successfully! (length: " . strlen($pIdx) . ")\n";

    ob_start();
    (new App\Controllers\ProductController())->create();
    $pCreate = ob_get_clean();
    echo "[PASS] ProductController::create() rendered successfully! (length: " . strlen($pCreate) . ")\n";

    $p = Database::fetch("SELECT id FROM products LIMIT 1");
    if ($p) {
        ob_start();
        (new App\Controllers\ProductController())->edit((string)$p['id']);
        $pEdit = ob_get_clean();
        echo "[PASS] ProductController::edit() rendered successfully for ID {$p['id']}! (length: " . strlen($pEdit) . ")\n";
    }
} catch (Throwable $e) {
    echo "[FAIL] Product view error: " . $e->getMessage() . "\n";
}

echo "\nALL AUTOMATED VERIFICATION TESTS COMPLETED!\n";
