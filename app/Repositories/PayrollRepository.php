<?php

namespace App\Repositories;

use App\Core\Database;

class PayrollRepository
{
    public static function getAllPeriods(): array
    {
        $sql = "SELECT p.*, u.name as creator_name,
                       (SELECT COUNT(*) FROM payrolls py WHERE py.period_id = p.id) as employee_count,
                       (SELECT SUM(net_salary) FROM payrolls py WHERE py.period_id = p.id) as total_payroll_amount
                FROM payroll_periods p 
                LEFT JOIN users u ON p.created_by = u.id 
                ORDER BY p.start_date DESC, p.id DESC";
        return Database::fetchAll($sql);
    }

    public static function getPeriodById(int $id): ?array
    {
        return Database::fetch("SELECT * FROM payroll_periods WHERE id = ? LIMIT 1", [$id]);
    }

    public static function createPeriod(string $name, string $startDate, string $endDate, ?int $createdBy = null): int
    {
        $sql = "INSERT INTO payroll_periods (name, start_date, end_date, created_by) VALUES (?, ?, ?, ?)";
        Database::execute($sql, [$name, $startDate, $endDate, $createdBy]);
        return (int)Database::lastInsertId();
    }

    public static function closePeriod(int $id): bool
    {
        return Database::execute("UPDATE payroll_periods SET is_closed = 1, closed_at = NOW() WHERE id = ?", [$id]);
    }

    public static function getPayrollsByPeriod(int $periodId): array
    {
        $sql = "SELECT p.*, u.name as user_name, u.username, u.role, pb.name as paid_by_name 
                FROM payrolls p 
                JOIN users u ON p.user_id = u.id 
                LEFT JOIN users pb ON p.paid_by = pb.id 
                WHERE p.period_id = ? 
                ORDER BY (u.role = 'OWNER') DESC, p.id DESC";
        return Database::fetchAll($sql, [$periodId]);
    }

    public static function getPayrollById(int $id): ?array
    {
        $sql = "SELECT p.*, u.name as user_name, u.username, u.role, u.phone, 
                       pp.name as period_name, pp.start_date, pp.end_date, 
                       pb.name as paid_by_name 
                FROM payrolls p 
                JOIN users u ON p.user_id = u.id 
                JOIN payroll_periods pp ON p.period_id = pp.id 
                LEFT JOIN users pb ON p.paid_by = pb.id 
                WHERE p.id = ? LIMIT 1";
        return Database::fetch($sql, [$id]);
    }

    public static function upsertPayroll(array $data): int
    {
        $existing = Database::fetch(
            "SELECT id FROM payrolls WHERE period_id = ? AND user_id = ? LIMIT 1",
            [$data['period_id'], $data['user_id']]
        );

        $regularPay = isset($data['regular_pay']) ? (float)$data['regular_pay'] : round((float)$data['regular_hours'] * (float)$data['hourly_rate_snapshot'], 2);
        $overtimePay = isset($data['overtime_pay']) ? (float)$data['overtime_pay'] : round((float)$data['overtime_hours'] * (float)$data['overtime_rate_snapshot'], 2);
        $bonus = (float)($data['bonus'] ?? 0.00);
        $deduction = (float)($data['deduction'] ?? 0.00);
        $grossSalary = isset($data['gross_salary']) ? (float)$data['gross_salary'] : ($regularPay + $overtimePay + $bonus);
        $netSalary = isset($data['net_salary']) ? (float)$data['net_salary'] : max(0, $grossSalary - $deduction);
        $notes = $data['notes'] ?? null;

        if ($existing) {
            $sql = "UPDATE payrolls SET 
                    regular_hours = ?, 
                    overtime_hours = ?, 
                    hourly_rate_snapshot = ?, 
                    overtime_rate_snapshot = ?, 
                    regular_pay = ?, 
                    overtime_pay = ?, 
                    bonus = ?, 
                    deduction = ?, 
                    gross_salary = ?, 
                    net_salary = ?, 
                    notes = COALESCE(?, notes) 
                    WHERE id = ?";

            Database::execute($sql, [
                $data['regular_hours'],
                $data['overtime_hours'],
                $data['hourly_rate_snapshot'],
                $data['overtime_rate_snapshot'],
                $regularPay,
                $overtimePay,
                $bonus,
                $deduction,
                $grossSalary,
                $netSalary,
                $notes,
                $existing['id']
            ]);

            return (int)$existing['id'];
        } else {
            $sql = "INSERT INTO payrolls 
                    (period_id, user_id, regular_hours, overtime_hours, hourly_rate_snapshot, 
                     overtime_rate_snapshot, regular_pay, overtime_pay, bonus, deduction, 
                     gross_salary, net_salary, notes, payment_status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'UNPAID')";

            Database::execute($sql, [
                $data['period_id'],
                $data['user_id'],
                $data['regular_hours'],
                $data['overtime_hours'],
                $data['hourly_rate_snapshot'],
                $data['overtime_rate_snapshot'],
                $regularPay,
                $overtimePay,
                $bonus,
                $deduction,
                $grossSalary,
                $netSalary,
                $notes
            ]);

            return (int)Database::lastInsertId();
        }
    }

    public static function markPaid(int $id, string $paymentMethod, ?string $notes = null, ?int $paidBy = null): bool
    {
        $today = date('Y-m-d');
        $sql = "UPDATE payrolls SET 
                payment_status = 'PAID', 
                payment_date = ?, 
                payment_method = ?, 
                notes = ?, 
                paid_by = ? 
                WHERE id = ?";
        return Database::execute($sql, [$today, $paymentMethod, $notes, $paidBy, $id]);
    }

    public static function updatePayrollDetail(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        $allowed = [
            'regular_hours', 'overtime_hours', 'hourly_rate_snapshot', 'overtime_rate_snapshot',
            'regular_pay', 'overtime_pay', 'bonus', 'deduction', 'gross_salary', 'net_salary',
            'payment_status', 'payment_date', 'payment_method', 'notes', 'paid_by'
        ];

        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "{$col} = ?";
                $params[] = $data[$col];
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE payrolls SET " . implode(', ', $fields) . " WHERE id = ?";
        return Database::execute($sql, $params);
    }

    public static function getPaidTotal(?string $startDate = null, ?string $endDate = null): float
    {
        $where = ["payment_status = 'PAID'"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "payment_date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "payment_date <= ?";
            $params[] = $endDate;
        }

        $whereSql = implode(' AND ', $where);
        $total = Database::fetchColumn("SELECT SUM(net_salary) FROM payrolls WHERE {$whereSql}", $params);
        return (float)($total ?: 0.0);
    }

    public static function deletePeriod(int $id): bool
    {
        Database::execute("DELETE FROM payrolls WHERE period_id = ?", [$id]);
        return Database::execute("DELETE FROM payroll_periods WHERE id = ?", [$id]);
    }

    public static function deletePayrollItem(int $id): bool
    {
        return Database::execute("DELETE FROM payrolls WHERE id = ?", [$id]);
    }
}
