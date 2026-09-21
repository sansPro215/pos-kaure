<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\ExpenseRepository;
use App\Repositories\SettingRepository;

class ReportService
{
    public static function getFinancialSummary(?string $startDate = null, ?string $endDate = null, ?int $cashierId = null): array
    {
        $where = ["t.deleted_at IS NULL", "t.status = 'PAID'"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(t.transaction_date) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(t.transaction_date) <= ?";
            $params[] = $endDate;
        }
        if (!empty($cashierId)) {
            $where[] = "t.cashier_id = ?";
            $params[] = $cashierId;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
                    COUNT(t.id) as total_transactions,
                    COALESCE(SUM(t.grand_total), 0) as total_net_sales,
                    COALESCE(SUM(t.subtotal), 0) as total_subtotal,
                    COALESCE(SUM(t.discount_amount), 0) as total_discount,
                    COALESCE(SUM(t.total_cogs), 0) as total_hpp,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'CASH' THEN t.grand_total ELSE 0 END), 0) as cash_sales,
                    COALESCE(SUM(CASE WHEN t.payment_method != 'CASH' THEN t.grand_total ELSE 0 END), 0) as cashless_sales,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'QRIS' THEN t.grand_total ELSE 0 END), 0) as qris_sales,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'TRANSFER' THEN t.grand_total ELSE 0 END), 0) as transfer_sales,
                    COALESCE(SUM(CASE WHEN t.payment_method = 'EWALLET' THEN t.grand_total ELSE 0 END), 0) as ewallet_sales
                FROM transactions t
                WHERE {$whereSql}";

        $res = Database::fetch($sql, $params);

        // Count items sold
        $itemSql = "SELECT COALESCE(SUM(ti.qty - ti.refunded_qty), 0) as items_sold 
                    FROM transaction_items ti 
                    JOIN transactions t ON ti.transaction_id = t.id 
                    WHERE {$whereSql}";
        $itemsSold = (int)Database::fetchColumn($itemSql, $params);

        $netSales = (float)$res['total_net_sales'];
        $hpp = (float)$res['total_hpp'];

        // Operational Expenses
        $expenses = ExpenseRepository::getTotal($startDate, $endDate);

        // Payroll Expenses (Hanya gaji pegawai/staf yang sudah dibayar, BUKAN gaji intensive manager)
        $payrollWhere = ["p.payment_status = 'PAID'", "u.role != 'OWNER'"];
        $payrollParams = [];
        if (!empty($startDate)) {
            $payrollWhere[] = "p.payment_date >= ?";
            $payrollParams[] = $startDate;
        }
        if (!empty($endDate)) {
            $payrollWhere[] = "p.payment_date <= ?";
            $payrollParams[] = $endDate;
        }
        $payrollWhereSql = implode(' AND ', $payrollWhere);
        $payrollExpenses = (float)Database::fetchColumn(
            "SELECT SUM(p.net_salary) FROM payrolls p JOIN users u ON p.user_id = u.id WHERE {$payrollWhereSql}",
            $payrollParams
        ) ?: 0.0;

        // Settings
        $settings = SettingRepository::get();
        $managerPercent = isset($settings['manager_incentive_percent']) ? (float)$settings['manager_incentive_percent'] : 20.00;
        $shareholderPercent = isset($settings['shareholder_percent']) ? (float)$settings['shareholder_percent'] : 80.00;

        // Total Beban = Beban Operasional + Gaji Pegawai (bukan gaji intensive manager)
        $totalExpenses = $expenses + $payrollExpenses;

        // Formula: Laba Kotor = Omzet - Operasional - Gaji Pegawai
        $grossProfit = $netSales - $totalExpenses;

        // Gaji Intensive Manager = 20% dari Laba Kotor (jika laba positif)
        $managerIncentive = $grossProfit > 0 ? ($grossProfit * ($managerPercent / 100)) : 0.0;

        // Pembagian Pemegang Saham = sisa dari pengurangan gaji intensive manager
        $shareholderDividend = $grossProfit > 0 ? max(0, $grossProfit - $managerIncentive) : $grossProfit;

        return [
            'total_transactions' => (int)$res['total_transactions'],
            'items_sold' => $itemsSold,
            'net_sales' => $netSales,
            'subtotal' => (float)$res['total_subtotal'],
            'discount' => (float)$res['total_discount'],
            'hpp' => $hpp,
            'operational_expenses' => $expenses,
            'payroll_expenses' => $payrollExpenses,
            'staff_payroll_expenses' => $payrollExpenses,
            'total_expenses' => $totalExpenses,
            'gross_profit' => $grossProfit,
            'manager_incentive_percent' => $managerPercent,
            'shareholder_percent' => $shareholderPercent,
            'manager_incentive' => $managerIncentive,
            'owner_salary' => $managerIncentive,
            'shareholder_dividend' => $shareholderDividend,
            'estimated_net_profit' => $shareholderDividend,
            'cash_sales' => (float)$res['cash_sales'],
            'cashless_sales' => (float)$res['cashless_sales'],
            'qris_sales' => (float)$res['qris_sales'],
            'transfer_sales' => (float)$res['transfer_sales'],
            'ewallet_sales' => (float)$res['ewallet_sales'],
        ];
    }

    public static function getTopProducts(?string $startDate = null, ?string $endDate = null, int $limit = 5, ?int $cashierId = null): array
    {
        $where = ["t.deleted_at IS NULL", "t.status = 'PAID'"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "DATE(t.transaction_date) >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "DATE(t.transaction_date) <= ?";
            $params[] = $endDate;
        }
        if (!empty($cashierId)) {
            $where[] = "t.cashier_id = ?";
            $params[] = $cashierId;
        }

        $whereSql = implode(' AND ', $where);

        $sql = "SELECT 
                    ti.product_name,
                    SUM(ti.qty - ti.refunded_qty) as total_qty,
                    SUM((ti.qty - ti.refunded_qty) * ti.selling_price) as total_omzet,
                    SUM((ti.qty - ti.refunded_qty) * (ti.selling_price - ti.cost_price)) as total_margin
                FROM transaction_items ti
                JOIN transactions t ON ti.transaction_id = t.id
                WHERE {$whereSql}
                GROUP BY ti.product_name
                ORDER BY total_qty DESC
                LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }

    public static function getSalesTrend(int $days = 7): array
    {
        $labels = [];
        $omzetData = [];
        $cogsData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $displayDate = date('d/m', strtotime($date));
            $labels[] = $displayDate;

            $sql = "SELECT 
                        COALESCE(SUM(grand_total), 0) as omzet,
                        COALESCE(SUM(total_cogs), 0) as cogs
                    FROM transactions 
                    WHERE DATE(transaction_date) = ? 
                      AND status = 'PAID' 
                      AND deleted_at IS NULL";
            $row = Database::fetch($sql, [$date]);
            $omzetData[] = (float)$row['omzet'];
            $cogsData[] = (float)$row['cogs'];
        }

        return [
            'labels' => $labels,
            'omzet' => $omzetData,
            'cogs' => $cogsData
        ];
    }
}
