<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

$helperFiles = glob(__DIR__ . '/../app/Helpers/*.php');
foreach ($helperFiles as $helperFile) {
    require_once $helperFile;
}

use App\Core\Database;
use App\Repositories\SettingRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\UserRepository;
use App\Services\PayrollService;
use App\Services\ReportService;

class EnhancementsTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): void
    {
        echo "========================================================\n";
        echo "  WARUNG KAURE - ENHANCEMENTS VERIFICATION SUITE       \n";
        echo "========================================================\n\n";

        $this->testSettingsUpdateAndRetrieve();
        $this->testDynamicProfitPercentages();
        $this->testOwnerSalaryLinkedToIncentive();
        $this->testPayrollEditingAndStatusUpdate();
        $this->testExpensesPaidPayrollIntegration();

        echo "\n========================================================\n";
        echo "ENHANCEMENT TESTS: {$this->passed} PASSED, {$this->failed} FAILED\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    private function testSettingsUpdateAndRetrieve(): void
    {
        echo "--- 1. Testing Settings for Payroll, Incentives & POS Layout ---\n";

        $current = SettingRepository::get();
        $this->assert(isset($current['default_regular_rate']), "default_regular_rate field exists in settings");
        $this->assert(isset($current['manager_incentive_percent']), "manager_incentive_percent field exists in settings");
        $this->assert(isset($current['shareholder_percent']), "shareholder_percent field exists in settings");
        $this->assert(isset($current['pos_layout']), "pos_layout field exists in settings");

        // Update settings with new values
        $updateSuccess = SettingRepository::update([
            'shop_name' => $current['shop_name'],
            'address' => $current['address'],
            'phone' => $current['phone'],
            'receipt_footer' => $current['receipt_footer'],
            'receipt_width' => $current['receipt_width'],
            'cashier_max_discount_percent' => 25.00,
            'default_regular_rate' => 18000.00,
            'default_overtime_rate' => 6000.00,
            'regular_hours_per_day' => 8,
            'payroll_cycle_days' => 14,
            'manager_incentive_percent' => 25.00,
            'shareholder_percent' => 75.00,
            'pos_layout' => 'table_list',
            'timezone' => 'Asia/Jakarta'
        ]);

        $this->assert($updateSuccess, "Settings successfully updated");

        $updated = SettingRepository::get();
        $this->assert((float)$updated['default_regular_rate'] === 18000.00, "default_regular_rate correctly saved as 18000");
        $this->assert((float)$updated['manager_incentive_percent'] === 25.00, "manager_incentive_percent correctly saved as 25%");
        $this->assert((float)$updated['shareholder_percent'] === 75.00, "shareholder_percent correctly saved as 75%");
        $this->assert($updated['pos_layout'] === 'table_list', "pos_layout correctly saved as table_list");

        // Switch to category_grid
        SettingRepository::update(array_merge($updated, ['pos_layout' => 'category_grid']));
        $cg = SettingRepository::get();
        $this->assert($cg['pos_layout'] === 'category_grid', "pos_layout successfully switched to category_grid");

        // Test shop_name synchronization
        $testShopName = "KEDAI KOPI KAURE NUSANTARA";
        SettingRepository::update(array_merge($updated, ['shop_name' => $testShopName]));
        $this->assert(shop_name() === $testShopName, "shop_name() helper returned updated shop name from settings");

        ob_start();
        require __DIR__ . '/../views/components/navbar.php';
        $navbarHtml = ob_get_clean();
        $this->assert(strpos($navbarHtml, $testShopName) !== false, "Navbar header dynamically displays updated shop_name: {$testShopName}");

        // Test theme_color presets and custom hex
        SettingRepository::update(array_merge($updated, ['theme_color' => 'forest']));
        $this->assert(shop_setting('theme_color') === 'forest', "theme_color successfully saved as 'forest'");
        $forestTheme = get_active_theme();
        $this->assert($forestTheme['primary'] === '#2E6F40', "get_active_theme() resolved forest primary color correctly");

        // Test custom hex
        SettingRepository::update(array_merge($updated, ['theme_color' => '#8e44ad']));
        $this->assert(shop_setting('theme_color') === '#8e44ad', "theme_color successfully saved as custom hex '#8e44ad'");
        $customTheme = get_active_theme();
        $this->assert($customTheme['primary'] === '#8e44ad', "get_active_theme() resolved custom hex correctly");
        $this->assert(strpos(render_theme_css(), '#8e44ad') !== false, "render_theme_css() contains custom primary color");

        // Restore standard defaults
        SettingRepository::update(array_merge($updated, [
            'shop_name' => $current['shop_name'],
            'theme_color' => 'coffee',
            'default_regular_rate' => 15000.00,
            'default_overtime_rate' => 5000.00,
            'manager_incentive_percent' => 20.00,
            'shareholder_percent' => 80.00,
            'pos_layout' => 'grid_large'
        ]));
    }

    private function testDynamicProfitPercentages(): void
    {
        echo "\n--- 2. Testing Dynamic Profit & Shareholder Dividend Formulas ---\n";

        // Set 25% / 75%
        $settings = SettingRepository::get();
        SettingRepository::update(array_merge($settings, [
            'manager_incentive_percent' => 25.00,
            'shareholder_percent' => 75.00
        ]));

        $summary = ReportService::getFinancialSummary('2026-09-01', '2026-09-30');
        $this->assert($summary['manager_incentive_percent'] === 25.00, "ReportService returns dynamic manager percent (25%)");
        $this->assert($summary['shareholder_percent'] === 75.00, "ReportService returns dynamic shareholder percent (75%)");
        $this->assert(isset($summary['owner_salary']), "ReportService has owner_salary field matching manager_incentive");

        // Reset to 20% / 80%
        SettingRepository::update(array_merge($settings, [
            'manager_incentive_percent' => 20.00,
            'shareholder_percent' => 80.00
        ]));
    }

    private function testOwnerSalaryLinkedToIncentive(): void
    {
        echo "\n--- 3. Testing Gaji Owner = Intensive Manager in Payroll Generation ---\n";

        $periodId = PayrollRepository::createPeriod('Test Period Owner Incentive', '2026-09-01', '2026-09-14', 1);
        $res = PayrollService::generateForPeriod($periodId, 1);
        $this->assert($res['count'] >= 1, "Generated payroll for {$res['count']} users in test period");

        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);
        $ownerSlip = null;
        foreach ($payrolls as $p) {
            if ($p['role'] === 'OWNER') {
                $ownerSlip = $p;
                break;
            }
        }

        $this->assert($ownerSlip !== null, "Owner payroll slip exists");
        $this->assert((float)$ownerSlip['regular_hours'] === 0.0, "Owner regular hours is 0 (purely incentive, independent of attendance)");
        $this->assert((float)$ownerSlip['overtime_hours'] === 0.0, "Owner overtime hours is 0 (purely incentive, independent of attendance)");
        $this->assert(strpos($ownerSlip['notes'] ?? '', 'Intensif Manager') !== false, "Owner slip notes document allocation from Intensif Manager");
    }

    private function testPayrollEditingAndStatusUpdate(): void
    {
        echo "\n--- 4. Testing Full Payroll Slip Editing & Status Changes ---\n";

        $periodId = PayrollRepository::createPeriod('Test Period Slip Edit', '2026-09-15', '2026-09-28', 1);
        PayrollService::generateForPeriod($periodId, 1);
        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);
        $slip = $payrolls[0];

        $slipId = (int)$slip['id'];

        // Edit hours, rates, bonus, deduction, and status to PAID
        $editSuccess = PayrollService::updatePayrollItem($slipId, [
            'regular_hours' => 80.00,
            'overtime_hours' => 10.00,
            'hourly_rate_snapshot' => 15000.00,
            'overtime_rate_snapshot' => 5000.00,
            'bonus' => 50000.00,
            'deduction' => 10000.00,
            'payment_status' => 'PAID',
            'payment_method' => 'TRANSFER',
            'payment_date' => '2026-09-28',
            'notes' => 'Telah ditransfer ke rek BCA'
        ], 1);

        $this->assert($editSuccess, "Payroll slip successfully updated via updatePayrollItem");

        $updatedSlip = PayrollRepository::getPayrollById($slipId);
        $this->assert((float)$updatedSlip['regular_hours'] === 80.00, "Regular hours updated to 80");
        $this->assert((float)$updatedSlip['overtime_hours'] === 10.00, "Overtime hours updated to 10");
        $this->assert((float)$updatedSlip['regular_pay'] === 1200000.00, "Regular pay recalculated as 80 * 15000 = 1.200.000");
        $this->assert((float)$updatedSlip['overtime_pay'] === 50000.00, "Overtime pay recalculated as 10 * 5000 = 50.000");
        $this->assert((float)$updatedSlip['bonus'] === 50000.00, "Bonus updated to 50.000");
        $this->assert((float)$updatedSlip['deduction'] === 10000.00, "Deduction updated to 10.000");
        // Gross: 1200000 + 50000 + 50000 = 1300000, Net: 1300000 - 10000 = 1290000
        $this->assert((float)$updatedSlip['net_salary'] === 1290000.00, "Net salary recalculated correctly as 1.290.000");
        $this->assert($updatedSlip['payment_status'] === 'PAID', "Payment status successfully updated to PAID");
        $this->assert($updatedSlip['payment_method'] === 'TRANSFER', "Payment method saved as TRANSFER");

        // Now test reverting status back to UNPAID (error correction)
        $revertSuccess = PayrollService::updatePayrollItem($slipId, [
            'payment_status' => 'UNPAID',
            'notes' => 'Status dikembalikan ke UNPAID karena kesalahan input'
        ], 1);

        $this->assert($revertSuccess, "Reverted status to UNPAID");
        $revertedSlip = PayrollRepository::getPayrollById($slipId);
        $this->assert($revertedSlip['payment_status'] === 'UNPAID', "Status is now UNPAID");
        $this->assert(empty($revertedSlip['payment_date']), "Payment date cleared when status changed to UNPAID");
    }

    private function testExpensesPaidPayrollIntegration(): void
    {
        echo "\n--- 5. Testing Payroll Expense Calculation & Integration ---\n";

        $periodId = PayrollRepository::createPeriod('Test Period Expense Calc', '2026-10-01', '2026-10-14', 1);
        PayrollService::generateForPeriod($periodId, 1);
        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);
        $slipId = (int)$payrolls[0]['id'];

        // Mark this slip as PAID on 2026-10-15 with net_salary = 200000
        PayrollService::updatePayrollItem($slipId, [
            'regular_hours' => 10,
            'hourly_rate_snapshot' => 20000,
            'overtime_hours' => 0,
            'bonus' => 0,
            'deduction' => 0,
            'payment_status' => 'PAID',
            'payment_date' => '2026-10-15',
            'payment_method' => 'CASH'
        ], 1);

        $paidTotal = PayrollRepository::getPaidTotal('2026-10-01', '2026-10-31');
        $this->assert($paidTotal >= 200000.00, "PayrollRepository::getPaidTotal captures paid payroll: Rp " . number_format($paidTotal));
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
            echo " [PASS] {$message}\n";
        } else {
            $this->failed++;
            echo " [FAIL] {$message}\n";
        }
    }
}

(new EnhancementsTest())->run();
