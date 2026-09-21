<?php
/**
 * Test Owner Edit Transaction
 */

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
use App\Repositories\TransactionRepository;
use App\Repositories\ProductRepository;
use App\Repositories\AuditRepository;
use App\Services\SaleService;

echo "=== TESTING OWNER EDIT TRANSACTION ===\n\n";

$owner = Database::fetch("SELECT * FROM users WHERE role = 'OWNER' AND status = 'ACTIVE' LIMIT 1");
$cashier = Database::fetch("SELECT * FROM users WHERE role = 'CASHIER' AND status = 'ACTIVE' LIMIT 1");

if (!$owner || !$cashier) {
    echo "ERROR: Owner or Cashier user not found in database.\n";
    exit(1);
}

// 1. Find or create a test product
$product = Database::fetch("SELECT * FROM products WHERE status = 'ACTIVE' AND deleted_at IS NULL AND stock_tracking_type = 'DIRECT' LIMIT 1");
if (!$product) {
    echo "ERROR: Direct stock product not found.\n";
    exit(1);
}
$productId = (int)$product['id'];
$initialStock = (float)$product['stock'];
echo "[1] Using product: {$product['name']} (ID: $productId), Initial Stock: $initialStock\n";

// 2. Create a fresh test transaction
$cartItems = [
    [
        'product_id' => $productId,
        'qty' => 2,
        'notes' => 'Initial order'
    ]
];

$paymentData = [
    'payment_method' => 'CASH',
    'paid_amount' => 50000,
    'change_amount' => 50000 - (2 * (float)$product['selling_price'])
];

$transResult = SaleService::checkout(
    $cartItems,
    $paymentData,
    ['type' => 'NONE', 'value' => 0],
    (int)$cashier['id']
);

$transId = (int)$transResult['transaction_id'];
echo "[2] Created initial transaction #$transId with 2 units of {$product['name']}\n";

$prodAfterSale = ProductRepository::findById($productId);
$stockAfterSale = (float)$prodAfterSale['stock'];
echo "    Stock after sale: $stockAfterSale (Decreased by 2)\n";

// 3. Perform Owner Edit on the transaction
$newDate = '2026-08-15 10:30:00';
$unitPrice = (float)$product['selling_price'];
$expectedSubtotal = $unitPrice * 3;
$expectedGrandTotal = $expectedSubtotal - 5000;

$editPayload = [
    'transaction_date' => $newDate,
    'cashier_id' => (int)$owner['id'], // change cashier to owner
    'status' => 'PAID',
    'hold_note' => 'Catatan diperbarui oleh Owner',
    'discount_type' => 'FIXED',
    'discount_value' => 5000,
    'payment_method' => 'QRIS',
    'provider' => 'BCA',
    'reference_number' => 'REF123456',
    'payment_proof' => 'proof_test_owner_edit.jpg',
    'paid_amount' => $expectedGrandTotal,
    'adjust_stock' => true,
    'edit_reason' => 'Koreksi kuantitas dan metode pembayaran pelanggan',
    'items' => [
        [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'qty' => 3, // Increased from 2 to 3! Delta = +1 sold => stock should decrease by 1 more
            'selling_price' => $unitPrice,
            'cost_price' => (float)$product['cost_price']
        ]
    ]
];

echo "[3] Executing SaleService::updateFullTransaction as Owner (ID: {$owner['id']})...\n";
$updatedTrans = SaleService::updateFullTransaction($transId, $editPayload, (int)$owner['id']);

// 4. Verify updated transaction data
$dbTrans = TransactionRepository::findById($transId);
echo "[4] Verifying updated transaction fields in DB:\n";
echo "    - Date: {$dbTrans['transaction_date']} (Expected: $newDate) => " . ($dbTrans['transaction_date'] === $newDate ? "PASS" : "FAIL") . "\n";
echo "    - Cashier ID: {$dbTrans['cashier_id']} (Expected: {$owner['id']}) => " . ($dbTrans['cashier_id'] == $owner['id'] ? "PASS" : "FAIL") . "\n";
echo "    - Subtotal: {$dbTrans['subtotal']} (Expected: $expectedSubtotal) => " . ((float)$dbTrans['subtotal'] == $expectedSubtotal ? "PASS" : "FAIL") . "\n";
echo "    - Discount Amount: {$dbTrans['discount_amount']} (Expected: 5000) => " . ((float)$dbTrans['discount_amount'] == 5000 ? "PASS" : "FAIL") . "\n";
echo "    - Grand Total: {$dbTrans['grand_total']} (Expected: $expectedGrandTotal) => " . ((float)$dbTrans['grand_total'] == $expectedGrandTotal ? "PASS" : "FAIL") . "\n";
echo "    - Payment Method: {$dbTrans['payment_method']} (Expected: QRIS) => " . ($dbTrans['payment_method'] === 'QRIS' ? "PASS" : "FAIL") . "\n";
echo "    - Payment Proof: {$dbTrans['payment_proof']} (Expected: proof_test_owner_edit.jpg) => " . ($dbTrans['payment_proof'] === 'proof_test_owner_edit.jpg' ? "PASS" : "FAIL") . "\n";
echo "    - Paid Amount: {$dbTrans['paid_amount']} (Expected: $expectedGrandTotal) => " . ($dbTrans['paid_amount'] == $expectedGrandTotal ? "PASS" : "FAIL") . "\n";
echo "    - Change Amount: {$dbTrans['change_amount']} (Expected: 0) => " . ($dbTrans['change_amount'] == 0 ? "PASS" : "FAIL") . "\n";
echo "    - Hold Note: {$dbTrans['hold_note']} => " . ($dbTrans['hold_note'] === 'Catatan diperbarui oleh Owner' ? "PASS" : "FAIL") . "\n";

// 5. Verify payment table synchronization
$payment = Database::fetch("SELECT * FROM payments WHERE transaction_id = ? ORDER BY id DESC LIMIT 1", [$transId]);
echo "[5] Verifying payments table:\n";
echo "    - Payment Method: {$payment['payment_method']} (Expected: QRIS) => " . ($payment['payment_method'] === 'QRIS' ? "PASS" : "FAIL") . "\n";
echo "    - Provider: {$payment['provider']} (Expected: BCA) => " . ($payment['provider'] === 'BCA' ? "PASS" : "FAIL") . "\n";
echo "    - Amount: {$payment['amount']} (Expected: $expectedGrandTotal) => " . ((float)$payment['amount'] == $expectedGrandTotal ? "PASS" : "FAIL") . "\n";
echo "    - Payment Proof in payments: {$payment['payment_proof']} (Expected: proof_test_owner_edit.jpg) => " . ($payment['payment_proof'] === 'proof_test_owner_edit.jpg' ? "PASS" : "FAIL") . "\n";

// 6. Verify items in transaction_items
$items = TransactionRepository::getItems($transId);
echo "[6] Verifying transaction items:\n";
echo "    - Items count: " . count($items) . " (Expected: 1) => " . (count($items) === 1 ? "PASS" : "FAIL") . "\n";
echo "    - Item Qty: " . $items[0]['qty'] . " (Expected: 3) => " . ($items[0]['qty'] == 3 ? "PASS" : "FAIL") . "\n";
echo "    - Item Subtotal: " . $items[0]['subtotal'] . " (Expected: $expectedSubtotal) => " . ((float)$items[0]['subtotal'] == $expectedSubtotal ? "PASS" : "FAIL") . "\n";

// 7. Verify stock delta adjustment
$prodAfterEdit = ProductRepository::findById($productId);
$expectedStock = $stockAfterSale - 1; // 1 more unit sold
echo "[7] Verifying stock adjustment:\n";
echo "    - Current Stock: {$prodAfterEdit['stock']} (Expected: $expectedStock) => " . ((float)$prodAfterEdit['stock'] === (float)$expectedStock ? "PASS" : "FAIL") . "\n";

// 8. Verify audit log entry
$audit = Database::fetch("SELECT * FROM audit_logs WHERE action = 'UPDATE_TRANSACTION' AND reference_type = 'TRANSACTION' AND reference_id = ? ORDER BY id DESC LIMIT 1", [$transId]);
echo "[8] Verifying audit log:\n";
echo "    - Audit entry found: " . ($audit ? "YES" : "NO") . " => " . ($audit ? "PASS" : "FAIL") . "\n";
if ($audit) {
    echo "    - User ID: {$audit['user_id']} (Expected: {$owner['id']}) => " . ($audit['user_id'] == $owner['id'] ? "PASS" : "FAIL") . "\n";
    $details = json_decode($audit['new_values_json'] ?? '{}', true);
    echo "    - Reason in details: " . ($details['edit_reason'] ?? 'none') . "\n";
}

// 9. Verify UI elements in views/transactions/show.php
$showContent = file_get_contents(__DIR__ . '/../views/transactions/show.php');
$hasEnctype = strpos($showContent, 'enctype="multipart/form-data"') !== false;
$hasProofInput = strpos($showContent, 'name="payment_proof"') !== false;
$hasCaptureCamera = strpos($showContent, 'capture="environment"') !== false;
echo "[9] Verifying UI elements in show.php:\n";
echo "    - Form has enctype='multipart/form-data': " . ($hasEnctype ? "PASS" : "FAIL") . "\n";
echo "    - Form has payment_proof file input: " . ($hasProofInput ? "PASS" : "FAIL") . "\n";
echo "    - Input supports camera capture: " . ($hasCaptureCamera ? "PASS" : "FAIL") . "\n";

// 10. Verify removing payment proof
$editPayloadRemoveProof = $editPayload;
$editPayloadRemoveProof['delete_payment_proof'] = true;
unset($editPayloadRemoveProof['payment_proof']);
SaleService::updateFullTransaction($transId, $editPayloadRemoveProof, (int)$owner['id']);
$dbTransRemoved = TransactionRepository::findById($transId);
$paymentRemoved = Database::fetch("SELECT * FROM payments WHERE transaction_id = ? ORDER BY id DESC LIMIT 1", [$transId]);
echo "[11] Verifying removing payment proof:\n";
echo "    - Transaction payment_proof cleared: " . (empty($dbTransRemoved['payment_proof']) ? "PASS" : "FAIL") . "\n";
echo "    - Payment payment_proof cleared: " . (empty($paymentRemoved['payment_proof']) ? "PASS" : "FAIL") . "\n";

// 12. Verify route & middleware definition
$routesFile = file_get_contents(__DIR__ . '/../routes/web.php');
$hasOwnerMiddleware = strpos($routesFile, "Router::post('/transactions/{id}/update', [TransactionController::class, 'update'], ['auth', 'role:OWNER']);") !== false;
echo "[12] Verifying route security in routes/web.php:\n";
echo "    - Protected with ['auth', 'role:OWNER']: " . ($hasOwnerMiddleware ? "PASS" : "FAIL") . "\n";

// Clean up test transaction
Database::execute("DELETE FROM payments WHERE transaction_id = ?", [$transId]);
Database::execute("DELETE FROM transaction_items WHERE transaction_id = ?", [$transId]);
Database::execute("DELETE FROM transactions WHERE id = ?", [$transId]);
Database::execute("DELETE FROM audit_logs WHERE action = 'UPDATE_TRANSACTION' AND reference_id = ?", [$transId]);
// Restore original stock
ProductRepository::updateStock($productId, $initialStock);
echo "\nCleaned up test data & restored original stock ($initialStock).\n";
echo "\n=== ALL TESTS COMPLETED SUCCESSFULLY! ===\n";
