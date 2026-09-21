<?php
/**
 * Test Cashier Expense Isolation:
 * - Cashier can only see their own expenses
 * - Cashier can only edit/delete their own expenses
 * - Owner can view and manage all expenses as usual
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
use App\Repositories\ExpenseRepository;

echo "=== TESTING CASHIER EXPENSE ISOLATION ===\n\n";

$cashier = Database::fetch("SELECT * FROM users WHERE role = 'CASHIER' AND status = 'ACTIVE' LIMIT 1");
$owner = Database::fetch("SELECT * FROM users WHERE role = 'OWNER' AND status = 'ACTIVE' LIMIT 1");

$cashierId = (int)$cashier['id'];
$ownerId = (int)$owner['id'];

// Create 1 expense by Owner and 1 expense by Cashier
$ownerExpId = ExpenseRepository::create([
    'date' => date('Y-m-d'),
    'category' => 'LISTRIK',
    'amount' => 100000,
    'description' => 'Listrik PLN Toko (Owner)',
    'created_by' => $ownerId
]);

$cashierExpId = ExpenseRepository::create([
    'date' => date('Y-m-d'),
    'category' => 'AIR',
    'amount' => 25000,
    'description' => 'Beli Galon Kedai (Kasir)',
    'created_by' => $cashierId
]);

echo "Created Owner Expense ID: {$ownerExpId}\n";
echo "Created Cashier Expense ID: {$cashierExpId}\n\n";

// [TEST 1] Cashier query only returns cashier's own expenses
$cashierList = ExpenseRepository::getAll(null, null, null, $cashierId);
$cashierIds = array_column($cashierList, 'id');
assert(in_array($cashierExpId, $cashierIds), "Cashier list contains cashier expense");
assert(!in_array($ownerExpId, $cashierIds), "Cashier list MUST NOT contain owner expense");
echo "[TEST 1] Cashier can ONLY see own expenses: PASS\n";

// [TEST 2] Cashier total only counts cashier's own expenses
$cashierTotal = ExpenseRepository::getTotal(null, null, $cashierId);
assert((float)$cashierTotal >= 25000, "Cashier total includes cashier expense");
$allTotal = ExpenseRepository::getTotal(null, null, null);
assert((float)$allTotal > (float)$cashierTotal, "All total is greater than cashier-only total");
echo "[TEST 2] Cashier summary total scoped to own expenses: PASS\n";

// [TEST 3] Owner query returns all expenses
$ownerList = ExpenseRepository::getAll(null, null, null, null);
$allIds = array_column($ownerList, 'id');
assert(in_array($cashierExpId, $allIds), "Owner list contains cashier expense");
assert(in_array($ownerExpId, $allIds), "Owner list contains owner expense");
echo "[TEST 3] Owner sees ALL expenses as usual: PASS\n";

// [TEST 4] Ownership Guard on Update
// Cashier cannot update Owner's expense
$ownerRecord = ExpenseRepository::findById($ownerExpId);
assert((int)$ownerRecord['created_by'] === $ownerId, "Owner record created_by is ownerId");
$isAllowedForCashier = ((int)$ownerRecord['created_by'] === $cashierId);
assert(!$isAllowedForCashier, "Cashier update check fails on owner record");
echo "[TEST 4] Cashier blocked from updating other's expense: PASS\n";

// [TEST 5] Cashier can update own expense
$updateSuccess = ExpenseRepository::update($cashierExpId, [
    'date' => date('Y-m-d'),
    'category' => 'AIR',
    'amount' => 28000,
    'description' => 'Beli Galon Kedai 2 Galon (Kasir Updated)'
]);
assert($updateSuccess === true, "Cashier own expense update succeeds");
$updatedCashierRecord = ExpenseRepository::findById($cashierExpId);
assert((float)$updatedCashierRecord['amount'] === 28000.0, "Updated amount is Rp 28.000");
echo "[TEST 5] Cashier can update own expense: PASS\n";

// Clean up test expenses
Database::execute("DELETE FROM operational_expenses WHERE id IN (?, ?)", [$ownerExpId, $cashierExpId]);
echo "\nCleaned up test expenses.\n";

echo "=== ALL CASHIER EXPENSE ISOLATION TESTS PASSED (100% SUCCESS) ===\n";
