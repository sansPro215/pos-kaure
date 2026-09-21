<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\SettingRepository;
use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;

class SettingController extends Controller
{
    public function index(): void
    {
        $settings = SettingRepository::get();
        $employees = UserRepository::getAll();
        $this->view('settings.index', [
            'pageTitle' => 'Pengaturan Warung Kaure',
            'settings' => $settings,
            'employees' => $employees
        ]);
    }

    public function update(): void
    {
        $this->validateCsrf();

        $shopName = trim((string)$this->getPost('shop_name', 'Warung Kaure'));
        $address = trim((string)$this->getPost('address'));
        $phone = trim((string)$this->getPost('phone'));
        $receiptFooter = trim((string)$this->getPost('receipt_footer'));
        $receiptWidth = (string)$this->getPost('receipt_width', 'auto');
        if (!in_array($receiptWidth, ['auto', '58', '80'])) {
            $receiptWidth = 'auto';
        }

        $maxDiscount = (float)$this->getPost('cashier_max_discount_percent', 20.00);
        $defaultRegularRate = (float)$this->getPost('default_regular_rate', 15000.00);
        $defaultOtRate = (float)$this->getPost('default_overtime_rate', 5000.00);
        $regularHours = (int)$this->getPost('regular_hours_per_day', 8);
        $payrollCycle = (int)$this->getPost('payroll_cycle_days', 14);
        $managerPercent = (float)$this->getPost('manager_incentive_percent', 20.00);
        $shareholderPercent = (float)$this->getPost('shareholder_percent', 80.00);

        // Hanya 2 pilihan layout: Grid Produk Besar atau Tabel Kasir Modern (Sesuai Foto)
        $posLayout = (string)$this->getPost('pos_layout', 'grid_large');
        if (!in_array($posLayout, ['grid_large', 'table_list'])) {
            $posLayout = 'grid_large';
        }

        $themeColor = trim((string)$this->getPost('theme_color', 'coffee'));
        $customHex = trim((string)$this->getPost('custom_theme_hex', ''));
        if ($themeColor === 'custom' && !empty($customHex) && preg_match('/^#[0-9a-fA-F]{6}$/', $customHex)) {
            $themeColor = strtolower($customHex);
        }

        $timezone = (string)$this->getPost('timezone', 'Asia/Jakarta');

        $old = SettingRepository::get();
        SettingRepository::update([
            'shop_name' => $shopName,
            'address' => $address,
            'phone' => $phone,
            'receipt_footer' => $receiptFooter,
            'receipt_width' => $receiptWidth,
            'cashier_max_discount_percent' => $maxDiscount,
            'default_regular_rate' => $defaultRegularRate,
            'default_overtime_rate' => $defaultOtRate,
            'regular_hours_per_day' => $regularHours,
            'payroll_cycle_days' => $payrollCycle,
            'manager_incentive_percent' => $managerPercent,
            'shareholder_percent' => $shareholderPercent,
            'pos_layout' => $posLayout,
            'theme_color' => $themeColor,
            'timezone' => $timezone
        ]);

        // SINKRONISASI GAJI OTOMATIS: Update langsung tarif ke seluruh data kasir (users) dan periode payroll berjalan
        Database::execute(
            "UPDATE users SET hourly_rate = ?, overtime_rate = ? WHERE role = 'CASHIER'",
            [$defaultRegularRate, $defaultOtRate]
        );

        // Sinkronkan ke slip gaji periode berjalan yang belum ditutup & belum dibayar
        Database::execute(
            "UPDATE payrolls p 
             JOIN payroll_periods pp ON p.period_id = pp.id 
             JOIN users u ON p.user_id = u.id 
             SET p.hourly_rate_snapshot = ?, 
                 p.overtime_rate_snapshot = ?,
                 p.regular_pay = ROUND(p.regular_hours * ?, 2),
                 p.overtime_pay = ROUND(p.overtime_hours * ?, 2),
                 p.gross_salary = ROUND(p.regular_hours * ?, 2) + ROUND(p.overtime_hours * ?, 2) + p.bonus,
                 p.net_salary = GREATEST(0, (ROUND(p.regular_hours * ?, 2) + ROUND(p.overtime_hours * ?, 2) + p.bonus) - p.deduction)
             WHERE pp.is_closed = 0 AND p.payment_status = 'UNPAID' AND u.role = 'CASHIER'",
            [$defaultRegularRate, $defaultOtRate, $defaultRegularRate, $defaultOtRate, $defaultRegularRate, $defaultOtRate, $defaultRegularRate, $defaultOtRate]
        );

        AuditRepository::log(auth_id(), 'UPDATE_SETTING', 'SETTING', 'SETTING', 1, $old, [
            'shop_name' => $shopName,
            'pos_layout' => $posLayout,
            'receipt_width' => $receiptWidth,
            'theme_color' => $themeColor,
            'default_regular_rate' => $defaultRegularRate,
            'default_overtime_rate' => $defaultOtRate,
            'manager_incentive_percent' => $managerPercent,
            'shareholder_percent' => $shareholderPercent,
            'synced_employees_automatically' => true
        ]);

        $this->flash('success', 'Pengaturan kedai dan sinkronisasi gaji berhasil disimpan.');
        $this->redirect('/settings');
    }
}
