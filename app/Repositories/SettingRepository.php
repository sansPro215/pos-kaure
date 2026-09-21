<?php

namespace App\Repositories;

use App\Core\Database;

class SettingRepository
{
    private static ?array $cached = null;

    public static function get(): array
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        $setting = Database::fetch("SELECT * FROM settings WHERE id = 1 LIMIT 1");
        if (!$setting) {
            return [
                'id' => 1,
                'shop_name' => 'Warung Kaure',
                'address' => '',
                'phone' => '',
                'receipt_footer' => '',
                'receipt_width' => 'auto',
                'cashier_max_discount_percent' => 20.00,
                'default_regular_rate' => 15000.00,
                'default_overtime_rate' => 5000.00,
                'regular_hours_per_day' => 8,
                'payroll_cycle_days' => 14,
                'manager_incentive_percent' => 20.00,
                'shareholder_percent' => 80.00,
                'pos_layout' => 'grid_large',
                'theme_color' => 'coffee',
                'timezone' => 'Asia/Jakarta',
            ];
        }

        // Ensure defaults for new columns
        $setting['default_regular_rate'] = isset($setting['default_regular_rate']) ? (float)$setting['default_regular_rate'] : 15000.00;
        $setting['manager_incentive_percent'] = isset($setting['manager_incentive_percent']) ? (float)$setting['manager_incentive_percent'] : 20.00;
        $setting['shareholder_percent'] = isset($setting['shareholder_percent']) ? (float)$setting['shareholder_percent'] : 80.00;
        $setting['pos_layout'] = !empty($setting['pos_layout']) ? $setting['pos_layout'] : 'grid_large';
        $setting['theme_color'] = !empty($setting['theme_color']) ? $setting['theme_color'] : 'coffee';

        self::$cached = $setting;
        return $setting;
    }

    public static function clearCache(): void
    {
        self::$cached = null;
    }

    public static function update(array $data): bool
    {
        self::clearCache();
        $sql = "UPDATE settings SET 
                shop_name = ?, 
                address = ?, 
                phone = ?, 
                receipt_footer = ?, 
                receipt_width = ?, 
                cashier_max_discount_percent = ?, 
                default_regular_rate = ?, 
                default_overtime_rate = ?, 
                regular_hours_per_day = ?, 
                payroll_cycle_days = ?, 
                manager_incentive_percent = ?, 
                shareholder_percent = ?, 
                pos_layout = ?, 
                theme_color = ?, 
                timezone = ? 
                WHERE id = 1";

        return Database::execute($sql, [
            $data['shop_name'],
            $data['address'] ?? null,
            $data['phone'] ?? null,
            $data['receipt_footer'] ?? null,
            $data['receipt_width'] ?? 'auto',
            $data['cashier_max_discount_percent'] ?? 20.00,
            $data['default_regular_rate'] ?? 15000.00,
            $data['default_overtime_rate'] ?? 5000.00,
            $data['regular_hours_per_day'] ?? 8,
            $data['payroll_cycle_days'] ?? 14,
            $data['manager_incentive_percent'] ?? 20.00,
            $data['shareholder_percent'] ?? 80.00,
            $data['pos_layout'] ?? 'grid_large',
            $data['theme_color'] ?? 'coffee',
            $data['timezone'] ?? 'Asia/Jakarta'
        ]);
    }
}
