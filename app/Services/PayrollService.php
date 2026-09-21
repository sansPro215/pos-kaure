<?php

namespace App\Services;

use App\Core\Database;
use App\Repositories\PayrollRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingRepository;
use App\Repositories\AuditRepository;
use App\Services\ReportService;
use Exception;

class PayrollService
{
    public static function generateForPeriod(int $periodId, ?int $createdBy = null): array
    {
        $period = PayrollRepository::getPeriodById($periodId);
        if (!$period) {
            throw new Exception("Periode payroll tidak ditemukan.");
        }

        if ($period['is_closed']) {
            throw new Exception("Periode payroll ini sudah ditutup dan tidak dapat dihitung ulang.");
        }

        $settings = SettingRepository::get();
        $defaultRegularRate = (float)($settings['default_regular_rate'] ?? 15000.00);
        $defaultOtRate = (float)($settings['default_overtime_rate'] ?? 5000.00);

        // Calculate financial summary to determine manager incentive (Owner salary)
        $finSummary = ReportService::getFinancialSummary($period['start_date'], $period['end_date']);
        $ownerIncentive = (float)($finSummary['manager_incentive'] ?? 0.0);
        $managerPercent = (float)($finSummary['manager_incentive_percent'] ?? 20.0);

        $users = UserRepository::getAll();
        $generatedCount = 0;

        Database::beginTransaction();

        try {
            foreach ($users as $u) {
                $userId = (int)$u['id'];

                // Calculate total regular and overtime minutes from attendance in this period
                $sql = "SELECT 
                            SUM(regular_minutes) as sum_regular_min, 
                            SUM(overtime_minutes) as sum_overtime_min 
                        FROM attendance 
                        WHERE user_id = ? 
                          AND date >= ? 
                          AND date <= ? 
                          AND status IN ('HADIR', 'SELESAI')";

                $att = Database::fetch($sql, [$userId, $period['start_date'], $period['end_date']]);

                $regMin = (int)($att['sum_regular_min'] ?? 0);
                $otMin = (int)($att['sum_overtime_min'] ?? 0);

                $regHours = round($regMin / 60, 2);
                $otHours = (float)floor($otMin / 60); // Pembulatan paten per 1 jam penuh

                $hourlyRate = (float)($u['hourly_rate'] > 0 ? $u['hourly_rate'] : $defaultRegularRate);
                $otRate = (float)($u['overtime_rate'] > 0 ? $u['overtime_rate'] : $defaultOtRate);

                if ($u['role'] === 'OWNER') {
                    // Gaji Owner murni dari Intensif Manager (tidak bergantung pada absensi seperti kasir)
                    PayrollRepository::upsertPayroll([
                        'period_id' => $periodId,
                        'user_id' => $userId,
                        'regular_hours' => 0.00,
                        'overtime_hours' => 0.00,
                        'hourly_rate_snapshot' => 0.00,
                        'overtime_rate_snapshot' => 0.00,
                        'regular_pay' => $ownerIncentive,
                        'overtime_pay' => 0.00,
                        'bonus' => 0.00,
                        'deduction' => 0.00,
                        'gross_salary' => $ownerIncentive,
                        'net_salary' => $ownerIncentive,
                        'notes' => "Gaji Owner murni dari Intensif Manager ({$managerPercent}% Laba Kotor). Tidak bergantung pada absensi.",
                    ]);
                } else {
                    // Staff / Kasir reguler payroll
                    $regPay = round($regHours * $hourlyRate, 2);
                    $otPay = round($otHours * $otRate, 2);
                    $grossSalary = $regPay + $otPay;

                    PayrollRepository::upsertPayroll([
                        'period_id' => $periodId,
                        'user_id' => $userId,
                        'regular_hours' => $regHours,
                        'overtime_hours' => $otHours,
                        'hourly_rate_snapshot' => $hourlyRate,
                        'overtime_rate_snapshot' => $otRate,
                        'regular_pay' => $regPay,
                        'overtime_pay' => $otPay,
                        'bonus' => 0.00,
                        'deduction' => 0.00,
                        'gross_salary' => $grossSalary,
                        'net_salary' => $grossSalary,
                    ]);
                }

                $generatedCount++;
            }

            AuditRepository::log(
                $createdBy,
                'GENERATE_PAYROLL',
                'PAYROLL',
                'PAYROLL_PERIOD',
                $periodId,
                null,
                ['employees_calculated' => $generatedCount, 'owner_incentive' => $ownerIncentive]
            );

            Database::commit();
            return ['status' => true, 'count' => $generatedCount];
        } catch (Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    public static function updatePayrollItem(int $payrollId, array $data, int $ownerId): bool
    {
        $payroll = PayrollRepository::getPayrollById($payrollId);
        if (!$payroll) {
            throw new Exception("Data slip payroll tidak ditemukan.");
        }

        $regHours = isset($data['regular_hours']) ? (float)$data['regular_hours'] : (float)$payroll['regular_hours'];
        $otHours = isset($data['overtime_hours']) ? (float)floor((float)$data['overtime_hours']) : (float)$payroll['overtime_hours'];
        $hourlyRate = isset($data['hourly_rate_snapshot']) ? (float)$data['hourly_rate_snapshot'] : (float)$payroll['hourly_rate_snapshot'];
        $otRate = isset($data['overtime_rate_snapshot']) ? (float)$data['overtime_rate_snapshot'] : (float)$payroll['overtime_rate_snapshot'];
        $bonus = isset($data['bonus']) ? (float)$data['bonus'] : (float)$payroll['bonus'];
        $deduction = isset($data['deduction']) ? (float)$data['deduction'] : (float)$payroll['deduction'];

        if (isset($data['regular_pay'])) {
            $regPay = (float)$data['regular_pay'];
        } else {
            $regPay = round($regHours * $hourlyRate, 2);
        }

        $otPay = round($otHours * $otRate, 2);
        $gross = $regPay + $otPay + $bonus;
        $net = max(0, $gross - $deduction);

        $status = !empty($data['payment_status']) ? $data['payment_status'] : $payroll['payment_status'];
        $method = isset($data['payment_method']) ? $data['payment_method'] : $payroll['payment_method'];
        $notes = isset($data['notes']) ? $data['notes'] : $payroll['notes'];
        $date = isset($data['payment_date']) ? $data['payment_date'] : $payroll['payment_date'];

        if ($status === 'PAID' && empty($date)) {
            $date = date('Y-m-d');
        } elseif ($status === 'UNPAID') {
            $date = null;
        }

        $updateData = [
            'regular_hours' => $regHours,
            'overtime_hours' => $otHours,
            'hourly_rate_snapshot' => $hourlyRate,
            'overtime_rate_snapshot' => $otRate,
            'regular_pay' => $regPay,
            'overtime_pay' => $otPay,
            'bonus' => $bonus,
            'deduction' => $deduction,
            'gross_salary' => $gross,
            'net_salary' => $net,
            'payment_status' => $status,
            'payment_method' => $method,
            'payment_date' => $date,
            'notes' => $notes,
            'paid_by' => ($status === 'PAID' ? $ownerId : null)
        ];

        $success = PayrollRepository::updatePayrollDetail($payrollId, $updateData);

        if ($success) {
            AuditRepository::log(
                $ownerId,
                'UPDATE_PAYROLL',
                'PAYROLL',
                'PAYROLL',
                $payrollId,
                ['net_salary' => $payroll['net_salary'], 'payment_status' => $payroll['payment_status']],
                ['net_salary' => $net, 'payment_status' => $status, 'bonus' => $bonus, 'deduction' => $deduction]
            );
        }

        return $success;
    }
}
