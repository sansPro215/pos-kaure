<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

// Autoloader for App namespace
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load all helper files
$helperFiles = glob(__DIR__ . '/../app/Helpers/*.php');
foreach ($helperFiles as $helperFile) {
    require_once $helperFile;
}

use App\Core\Database;
use App\Services\AuthService;
use App\Services\SaleService;
use App\Services\InventoryService;
use App\Services\AttendanceService;
use App\Services\PayrollService;
use App\Services\ReportService;
use App\Repositories\UserRepository;
use App\Repositories\ProductRepository;
use App\Repositories\IngredientRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\ExpenseRepository;

class SystemIntegrationTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "========================================================\n";
        echo "  WARUNG KAURE SYSTEM - COMPREHENSIVE INTEGRATION TEST  \n";
        echo "========================================================\n\n";

        $this->testDatabaseConnection();
        $this->testAuthenticationAndRoles();
        $this->testManualClockInOnly();
        $this->testInventoryStockInWithWAC();
        $this->testPosCheckoutAtomicAndBOM();
        $this->testVoidTransactionAndStockReversal();
        $this->testAttendanceAndOvertimeCalculation();
        $this->testBiWeeklyPayrollGeneration();
        $this->testFinancialReportsAndProfit();

        echo "\n========================================================\n";
        echo "TEST SUMMARY: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function assert(bool $condition, string $testName, string $details = ''): void
    {
        if ($condition) {
            echo " [PASS] {$testName}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$testName} - {$details}\n";
            $this->failed++;
        }
    }

    private function testDatabaseConnection(): void
    {
        echo "--- 1. Testing Database Connection ---\n";
        $pdo = Database::getInstance();
        $this->assert($pdo instanceof PDO, "PDO Connection established");

        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $this->assert(count($tables) >= 15, "Database contains " . count($tables) . " tables (expected >= 15)");
    }

    private function testAuthenticationAndRoles(): void
    {
        echo "\n--- 2. Testing Authentication & User Roles ---\n";

        // Test invalid login
        $res = AuthService::attempt('wrong_user', 'wrong_pass');
        $this->assert(!$res['status'], "Invalid login correctly rejected");

        // Test cashier login
        $cashierRes = AuthService::attempt('kasir', 'kasir123');
        $this->assert($cashierRes['status'], "Cashier 'kasir' login succeeded");
        $this->assert($_SESSION['user']['role'] === 'CASHIER', "Session role is CASHIER");

        // Test owner login
        $ownerRes = AuthService::attempt('owner', 'owner123');
        $this->assert($ownerRes['status'], "Owner 'owner' login succeeded");
        $this->assert($_SESSION['user']['role'] === 'OWNER', "Session role is OWNER");
    }

    private function testManualClockInOnly(): void
    {
        echo "\n--- 3. Testing Manual Clock-In (No Automatic Clock-In on Login) ---\n";
        $owner = UserRepository::findByUsername('owner');

        // Clean any today record for owner to test from fresh state
        \App\Core\Database::execute("DELETE FROM attendance WHERE user_id = ? AND date = ?", [(int)$owner['id'], date('Y-m-d')]);

        // Login as owner
        AuthService::attempt('owner', 'owner123');

        // Verify that NO automatic clock-in was created on login
        $recordAfterLogin = AttendanceRepository::getToday((int)$owner['id']);
        $this->assert(empty($recordAfterLogin), "No automatic clock-in record created on login");

        // Now perform explicit manual clock-in
        $clockInRes = \App\Services\AttendanceService::clockIn((int)$owner['id']);
        $this->assert($clockInRes['status'], "Manual clock-in succeeded: " . ($clockInRes['message'] ?? ''));

        $manualRecord = AttendanceRepository::getToday((int)$owner['id']);
        $this->assert(!empty($manualRecord), "Manual clock-in attendance record exists for today");
        $this->assert(!empty($manualRecord['clock_in']), "Owner clock-in timestamp is set: " . ($manualRecord['clock_in'] ?? ''));
    }

    private function testInventoryStockInWithWAC(): void
    {
        echo "\n--- 4. Testing Inventory Stock-In & Weighted Average Cost (WAC) ---\n";
        $ing = IngredientRepository::findById(1);
        $oldQty = (float)$ing['current_stock'];
        $oldCost = (float)$ing['average_cost'];

        // Stock in: 1000g @ Rp 250/g
        $inQty = 1000.0;
        $inCost = 250.0;
        $expectedNewAvg = (($oldQty * $oldCost) + ($inQty * $inCost)) / ($oldQty + $inQty);

        $success = InventoryService::stockIn(
            itemType: 'INGREDIENT',
            itemId: 1,
            incomingQty: $inQty,
            incomingCost: $inCost,
            note: 'Test Stock In WAC',
            userId: 1
        );

        $this->assert($success, "Stock In executed successfully");

        $updatedIng = IngredientRepository::findById(1);
        $this->assert((float)$updatedIng['current_stock'] === ($oldQty + $inQty), "Stock updated correctly: " . $updatedIng['current_stock']);
        $diff = abs((float)$updatedIng['average_cost'] - $expectedNewAvg);
        $this->assert($diff < 0.01, "WAC correctly calculated. Expected: {$expectedNewAvg}, Got: " . $updatedIng['average_cost']);
    }

    private function testPosCheckoutAtomicAndBOM(): void
    {
        echo "\n--- 5. Testing POS Checkout (Atomic Transaction & Recipe BOM Deduction) ---\n";
        // Find a DIRECT product: Air Mineral 600ml (id: 5)
        $directProduct = ProductRepository::findById(5);
        $directStockBefore = (float)$directProduct['stock'];

        // Find a RECIPE product: Es Kopi Susu Kaure (id: 1)
        // Uses: Biji Kopi Arabika (id: 1, 18g), Susu UHT Fresh (id: 2, 120ml), Gula Aren Cair (id: 3, 20ml)
        $ing1Before = (float)IngredientRepository::findById(1)['current_stock'];
        $ing2Before = (float)IngredientRepository::findById(2)['current_stock'];
        $ing3Before = (float)IngredientRepository::findById(3)['current_stock'];

        // Prepare checkout: 2x Es Kopi Susu Kaure (Rp 18.000 ea) + 1x Air Mineral (Rp 5.000)
        // Subtotal: 36.000 + 5.000 = 41.000
        // Discount: Rp 1.000
        // Grand Total: Rp 40.000
        // Cash paid: Rp 50.000 -> Change: Rp 10.000
        $cart = [
            ['product_id' => 1, 'qty' => 2],
            ['product_id' => 5, 'qty' => 1]
        ];
        $paymentData = [
            'method' => 'CASH',
            'received_amount' => 50000
        ];
        $discountData = [
            'type' => 'FIXED',
            'value' => 1000
        ];

        $checkoutResult = SaleService::checkout($cart, $paymentData, $discountData, 1);
        $this->assert($checkoutResult['status'], "POS checkout returned success");
        $this->assert($checkoutResult['change_amount'] === 10000.0, "Change amount correctly calculated as Rp 10.000");

        $trxId = (int)$checkoutResult['transaction_id'];
        $trx = TransactionRepository::findById($trxId);
        $this->assert($trx['status'] === 'PAID', "Transaction saved with status PAID");
        $this->assert((float)$trx['grand_total'] === 40000.0, "Transaction grand total is 40.000");

        // Verify DIRECT product stock reduced by 1
        $directProductAfter = ProductRepository::findById(5);
        $this->assert((float)$directProductAfter['stock'] === ($directStockBefore - 1), "DIRECT product stock decreased by 1");

        // Verify RECIPE ingredients reduced by 2x BOM
        $ing1After = (float)IngredientRepository::findById(1)['current_stock'];
        $ing2After = (float)IngredientRepository::findById(2)['current_stock'];
        $ing3After = (float)IngredientRepository::findById(3)['current_stock'];

        $this->assert($ing1After === ($ing1Before - (18 * 2)), "Biji Kopi reduced by 36g. Before: {$ing1Before}, After: {$ing1After}");
        $this->assert($ing2After === ($ing2Before - (120 * 2)), "Susu Fresh reduced by 240ml. Before: {$ing2Before}, After: {$ing2After}");
        $this->assert($ing3After === ($ing3Before - (20 * 2)), "Gula Aren reduced by 40ml. Before: {$ing3Before}, After: {$ing3After}");

        // Save transaction id for void test
        $GLOBALS['test_trx_id'] = $trxId;
    }

    private function testVoidTransactionAndStockReversal(): void
    {
        echo "\n--- 6. Testing Void Transaction & Stock Reversal ---\n";
        $trxId = $GLOBALS['test_trx_id'] ?? null;
        $this->assert(!empty($trxId), "Test transaction ID available for void");

        // Record stock before void
        $directBefore = (float)ProductRepository::findById(5)['stock'];
        $ing1Before = (float)IngredientRepository::findById(1)['current_stock'];

        // Perform void
        $voidRes = SaleService::voidTransaction($trxId, 'Salah input meja pelanggan', 1);
        $this->assert($voidRes, "Void transaction succeeded");

        // Check transaction status
        $trx = TransactionRepository::findById($trxId);
        $this->assert($trx['status'] === 'VOID', "Transaction status is now VOID");

        // Check stock restored
        $directAfter = (float)ProductRepository::findById(5)['stock'];
        $ing1After = (float)IngredientRepository::findById(1)['current_stock'];

        $this->assert($directAfter === ($directBefore + 1), "DIRECT stock restored by 1 after VOID");
        $this->assert($ing1After === ($ing1Before + 36), "Ingredient stock restored by 36g after VOID");
    }

    private function testAttendanceAndOvertimeCalculation(): void
    {
        echo "\n--- 7. Testing Cashier Attendance & Overtime Calculation ---\n";
        $cashier = UserRepository::findByUsername('kasir');
        $cashierId = (int)$cashier['id'];
        $testDate = '2026-09-01';

        // Clean any existing test record for 2026-09-01
        Database::execute("DELETE FROM attendance WHERE user_id = ? AND date = ?", [$cashierId, $testDate]);

        // Insert 10-hour shift (08:00 to 18:00) -> 600 minutes total: 480 normal, 120 overtime
        $attId = AttendanceRepository::createManual([
            'user_id' => $cashierId,
            'date' => $testDate,
            'clock_in' => '08:00:00',
            'clock_out' => '18:00:00',
            'status' => 'HADIR',
            'note' => 'Shift uji coba 10 jam'
        ]);

        $this->assert($attId > 0, "Cashier attendance record saved (ID: {$attId})");

        $att = AttendanceRepository::findById($attId);
        $this->assert(!empty($att), "Attendance record found");
        $this->assert((int)$att['worked_minutes'] === 600, "Worked minutes is 600 (10 hours)");
        $this->assert((int)$att['regular_minutes'] === 480, "Regular minutes is 480 (8 hours)");
        $this->assert((int)$att['overtime_minutes'] === 120, "Overtime minutes is 120 (2 hours)");

        // Test status LIBUR & attendance deletion
        $holidayAttId = AttendanceRepository::createManual([
            'user_id' => $cashier['id'],
            'date' => '2026-09-20',
            'clock_in' => null,
            'clock_out' => null,
            'status' => 'LIBUR',
            'note' => 'Hari Libur Nasional'
        ]);
        $this->assert($holidayAttId > 0, "Attendance record with status LIBUR created (ID: {$holidayAttId})");
        $holidayAtt = AttendanceRepository::findById($holidayAttId);
        $this->assert($holidayAtt['status'] === 'LIBUR', "Status LIBUR verified in database");

        $delSuccess = AttendanceRepository::delete($holidayAttId);
        $this->assert($delSuccess, "Attendance record successfully deleted");
        $deletedCheck = AttendanceRepository::findById($holidayAttId);
        $this->assert(empty($deletedCheck), "Deleted attendance record is no longer found");
    }

    private function testBiWeeklyPayrollGeneration(): void
    {
        echo "\n--- 8. Testing 14-Day Bi-Weekly Payroll Generation ---\n";
        $startDate = '2026-09-01';
        $endDate = '2026-09-14';

        // Check or create payroll period
        $periodId = PayrollRepository::createPeriod('Periode Uji Coba 1-14 Sep 2026', $startDate, $endDate, 1);
        $this->assert($periodId > 0, "Payroll period created (ID: {$periodId})");

        $genRes = PayrollService::generateForPeriod($periodId, 1);
        $this->assert($genRes['status'], "Bi-weekly payroll generated successfully for " . $genRes['count'] . " employees");

        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);
        $this->assert(!empty($payrolls), "Payrolls retrieved for period");

        // Find cashier's payroll
        $cashier = UserRepository::findByUsername('kasir');
        $cashierPayroll = null;
        foreach ($payrolls as $p) {
            if ((int)$p['user_id'] === (int)$cashier['id']) {
                $cashierPayroll = $p;
                break;
            }
        }

        $this->assert(!empty($cashierPayroll), "Cashier payroll record exists");
        $this->assert((float)$cashierPayroll['hourly_rate_snapshot'] > 0, "Hourly rate snapshot captured: Rp " . number_format((float)$cashierPayroll['hourly_rate_snapshot']));
        $this->assert((float)$cashierPayroll['overtime_rate_snapshot'] > 0, "Overtime rate snapshot captured: Rp " . number_format((float)$cashierPayroll['overtime_rate_snapshot']));

        // Test update payroll item (bonus & deduction)
        $updateSuccess = PayrollService::updatePayrollItem((int)$cashierPayroll['id'], [
            'regular_hours' => $cashierPayroll['regular_hours'],
            'overtime_hours' => $cashierPayroll['overtime_hours'],
            'bonus' => 25000,
            'deduction' => 5000
        ], 1);
        $this->assert($updateSuccess, "Updated payroll with bonus (Rp 25.000) and deduction (Rp 5.000)");

        $updatedPayroll = PayrollRepository::getPayrollById((int)$cashierPayroll['id']);
        $this->assert((float)$updatedPayroll['bonus'] === 25000.0, "Bonus correctly updated");
        $this->assert((float)$updatedPayroll['deduction'] === 5000.0, "Deduction correctly updated");
        $this->assert((float)$updatedPayroll['net_salary'] > 0, "Net salary recalculated correctly: Rp " . number_format((float)$updatedPayroll['net_salary']));
    }

    private function testFinancialReportsAndProfit(): void
    {
        echo "\n--- 9. Testing Financial Report & Profit Calculations ---\n";

        // Create an operational expense
        $expId = ExpenseRepository::create([
            'date' => date('Y-m-d'),
            'category' => 'LISTRIK',
            'amount' => 50000,
            'description' => 'Listrik & Token Kedai Uji Coba',
            'receipt_image' => null,
            'created_by' => 1
        ]);
        $this->assert($expId > 0, "Operational expense recorded: Rp 50.000");

        // Run summary report for current month
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        $summary = ReportService::getFinancialSummary($startDate, $endDate);

        $this->assert(isset($summary['net_sales']), "Report summary has net_sales: Rp " . number_format($summary['net_sales']));
        $this->assert(isset($summary['hpp']), "Report summary has hpp: Rp " . number_format($summary['hpp']));
        $this->assert(isset($summary['gross_profit']), "Report summary has gross_profit: Rp " . number_format($summary['gross_profit']));
        $this->assert(isset($summary['operational_expenses']), "Report summary has operational_expenses: Rp " . number_format($summary['operational_expenses']));
        $this->assert(isset($summary['estimated_net_profit']), "Report summary has estimated_net_profit: Rp " . number_format($summary['estimated_net_profit']));

        // Verifikasi Rumus Baru Laba Kotor = Omzet - Operasional - Gaji Pegawai
        $expectedGrossProfit = $summary['net_sales'] - $summary['operational_expenses'] - $summary['payroll_expenses'];
        $this->assert(abs($summary['gross_profit'] - $expectedGrossProfit) < 0.01, "Gross profit formula verified (Omzet - Operasional - Gaji)");

        // Verifikasi Gaji Intensif Manager (20%) & Pembagian Pemegang Saham (80%)
        $expectedManagerIncentive = $summary['gross_profit'] > 0 ? ($summary['gross_profit'] * 0.20) : 0.0;
        $expectedShareholderDividend = $summary['gross_profit'] > 0 ? ($summary['gross_profit'] - $expectedManagerIncentive) : $summary['gross_profit'];

        $this->assert(abs($summary['manager_incentive'] - $expectedManagerIncentive) < 0.01, "Manager incentive 20% verified: Rp " . number_format($summary['manager_incentive']));
        $this->assert(abs($summary['shareholder_dividend'] - $expectedShareholderDividend) < 0.01, "Shareholder dividend 80% verified: Rp " . number_format($summary['shareholder_dividend']));
    }
}

$test = new SystemIntegrationTest();
$test->run();
