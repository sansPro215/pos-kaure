<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\PayrollRepository;
use App\Repositories\UserRepository;
use App\Repositories\SettingRepository;
use App\Repositories\AttendanceRepository;
use App\Repositories\AuditRepository;
use App\Services\PayrollService;
use Exception;

class PayrollController extends Controller
{
    public function index(): void
    {
        $periods = PayrollRepository::getAllPeriods();
        $this->view('payroll.index', [
            'pageTitle' => 'Penggajian Pegawai (Payroll 14 Hari)',
            'periods' => $periods
        ]);
    }

    public function createPeriod(): void
    {
        $this->validateCsrf();

        $name = trim((string)$this->getPost('name'));
        $startDate = (string)$this->getPost('start_date');
        $endDate = (string)$this->getPost('end_date');

        if (empty($name) || empty($startDate) || empty($endDate)) {
            $this->flash('danger', 'Nama periode dan rentang tanggal wajib diisi.');
            $this->redirect('/payroll');
        }

        $id = PayrollRepository::createPeriod($name, $startDate, $endDate, auth_id());
        $this->flash('success', "Periode payroll '{$name}' berhasil dibuat. Silakan klik 'Hitung Otomatis' untuk memproses absensi.");
        $this->redirect('/payroll/' . $id);
    }

    public function show(string $id): void
    {
        $periodId = (int)$id;
        $period = PayrollRepository::getPeriodById($periodId);
        if (!$period) {
            $this->flash('danger', 'Periode payroll tidak ditemukan.');
            $this->redirect('/payroll');
        }

        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);

        $this->view('payroll.show', [
            'pageTitle' => 'Slip Gaji: ' . $period['name'],
            'period' => $period,
            'payrolls' => $payrolls
        ]);
    }

    public function generate(string $id): void
    {
        $this->validateCsrf();
        $periodId = (int)$id;

        try {
            $res = PayrollService::generateForPeriod($periodId, auth_id());
            $this->flash('success', "Perhitungan gaji berhasil diproses untuk {$res['count']} pegawai.");
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/payroll/' . $periodId);
    }

    public function updateItem(string $id): void
    {
        $this->validateCsrf();
        $payrollId = (int)$id;

        $periodId = (int)$this->getPost('period_id');
        $regHours = $this->getPost('regular_hours') !== null ? (float)$this->getPost('regular_hours') : null;
        $otHours = $this->getPost('overtime_hours') !== null ? (float)$this->getPost('overtime_hours') : null;
        $hourlyRate = $this->getPost('hourly_rate_snapshot') !== null ? (float)$this->getPost('hourly_rate_snapshot') : null;
        $otRate = $this->getPost('overtime_rate_snapshot') !== null ? (float)$this->getPost('overtime_rate_snapshot') : null;
        $bonus = (float)$this->getPost('bonus', 0);
        $deduction = (float)$this->getPost('deduction', 0);
        $status = (string)$this->getPost('payment_status', 'UNPAID');
        $method = (string)$this->getPost('payment_method', 'TRANSFER');
        $notes = trim((string)$this->getPost('notes'));

        $data = [
            'bonus' => $bonus,
            'deduction' => $deduction,
            'payment_status' => $status,
            'payment_method' => $method,
            'notes' => $notes
        ];

        if ($regHours !== null) $data['regular_hours'] = $regHours;
        if ($otHours !== null) $data['overtime_hours'] = (float)floor($otHours);
        if ($hourlyRate !== null) $data['hourly_rate_snapshot'] = $hourlyRate;
        if ($otRate !== null) $data['overtime_rate_snapshot'] = $otRate;

        try {
            PayrollService::updatePayrollItem($payrollId, $data, auth_id());
            $this->flash('success', 'Rincian dan status slip gaji pegawai berhasil diperbarui.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/payroll/' . $periodId);
    }

    public function markPaid(string $id): void
    {
        $this->validateCsrf();
        $payrollId = (int)$id;

        $periodId = (int)$this->getPost('period_id');
        $paymentMethod = (string)$this->getPost('payment_method', 'TRANSFER');
        $notes = (string)$this->getPost('notes');

        PayrollRepository::markPaid($payrollId, $paymentMethod, $notes, auth_id());
        $this->flash('success', 'Gaji pegawai berhasil ditandai Lunas (PAID).');

        $this->redirect('/payroll/' . $periodId);
    }

    public function close(string $id): void
    {
        $this->validateCsrf();
        $periodId = (int)$id;

        PayrollRepository::closePeriod($periodId);
        $this->flash('success', 'Periode payroll berhasil ditutup.');
        $this->redirect('/payroll');
    }

    public function deletePeriod(string $id): void
    {
        $this->validateCsrf();
        $periodId = (int)$id;

        $period = PayrollRepository::getPeriodById($periodId);
        if (!$period) {
            $this->flash('danger', 'Periode payroll tidak ditemukan.');
            $this->redirect('/payroll');
            return;
        }

        PayrollRepository::deletePeriod($periodId);
        AuditRepository::log(auth_id(), 'DELETE_PAYROLL_PERIOD', 'PAYROLL', 'PAYROLL_PERIOD', $periodId, $period, null);

        $this->flash('success', "Periode payroll '{$period['name']}' berhasil dihapus.");
        $this->redirect('/payroll');
    }

    public function deleteItem(string $id): void
    {
        $this->validateCsrf();
        $payrollId = (int)$id;

        $payroll = PayrollRepository::getPayrollById($payrollId);
        if (!$payroll) {
            $this->flash('danger', 'Data slip payroll tidak ditemukan.');
            $this->redirect('/payroll');
            return;
        }

        $periodId = (int)$payroll['period_id'];
        PayrollRepository::deletePayrollItem($payrollId);
        AuditRepository::log(auth_id(), 'DELETE_PAYROLL_ITEM', 'PAYROLL', 'PAYROLL', $payrollId, $payroll, null);

        $this->flash('success', "Slip gaji untuk pegawai '{$payroll['user_name']}' berhasil dihapus.");
        $this->redirect('/payroll/' . $periodId);
    }

    public function slip(string $id): void
    {
        $payrollId = (int)$id;
        $payroll = PayrollRepository::getPayrollById($payrollId);
        if (!$payroll) {
            $this->flash('danger', 'Data slip gaji tidak ditemukan.');
            $this->redirect('/payroll');
            return;
        }

        $period = PayrollRepository::getPeriodById((int)$payroll['period_id']);
        $settings = SettingRepository::get();
        $attendances = AttendanceRepository::getList($period['start_date'], $period['end_date'], (int)$payroll['user_id']);

        $this->view('payroll.slip', [
            'pageTitle' => 'Slip Gaji - ' . $payroll['user_name'] . ' (' . $period['name'] . ')',
            'payroll' => $payroll,
            'period' => $period,
            'settings' => $settings,
            'attendances' => $attendances
        ], null); // Render clean standalone printable template
    }

    public function printPeriodSummary(string $id): void
    {
        $periodId = (int)$id;
        $period = PayrollRepository::getPeriodById($periodId);
        if (!$period) {
            $this->flash('danger', 'Periode payroll tidak ditemukan.');
            $this->redirect('/payroll');
            return;
        }

        $payrolls = PayrollRepository::getPayrollsByPeriod($periodId);
        $settings = SettingRepository::get();

        $this->view('payroll.summary_print', [
            'pageTitle' => 'Rekapitulasi Gaji - ' . $period['name'],
            'period' => $period,
            'payrolls' => $payrolls,
            'settings' => $settings
        ], null);
    }
}
