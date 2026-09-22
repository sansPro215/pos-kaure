<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Repositories\ExpenseRepository;
use App\Repositories\PayrollRepository;
use App\Repositories\AuditRepository;

class ExpenseController extends Controller
{
    public function index(): void
    {
        $startDate = $this->getQuery('start_date');
        $endDate = $this->getQuery('end_date');
        $category = $this->getQuery('category');

        // Role Kasir hanya bisa melihat pengeluaran yang diinput oleh diri sendiri
        $createdBy = is_owner() ? null : auth_id();

        $expenses = ExpenseRepository::getAll($startDate, $endDate, $category, $createdBy);
        $operationalTotal = ExpenseRepository::getTotal($startDate, $endDate, $createdBy);
        $payrollTotal = is_owner() ? PayrollRepository::getPaidTotal($startDate, $endDate) : 0;
        $totalAmount = $operationalTotal + $payrollTotal;

        // Fetch paid payroll disbursements for the selected range (Owner only)
        $paidPayrolls = [];
        if (is_owner()) {
            $payrollWhere = ["p.payment_status = 'PAID'"];
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
            $sql = "SELECT p.*, u.name as user_name, u.role, pp.name as period_name 
                    FROM payrolls p 
                    JOIN users u ON p.user_id = u.id 
                    JOIN payroll_periods pp ON p.period_id = pp.id 
                    WHERE {$payrollWhereSql} 
                    ORDER BY p.payment_date DESC, p.id DESC";
            $paidPayrolls = Database::fetchAll($sql, $payrollParams);
        }

        $this->view('expenses.index', [
            'pageTitle' => is_owner() ? 'Pengeluaran & Beban Kedai' : 'Pengeluaran Saya (Kasir)',
            'expenses' => $expenses,
            'operationalTotal' => $operationalTotal,
            'payrollTotal' => $payrollTotal,
            'totalAmount' => $totalAmount,
            'paidPayrolls' => $paidPayrolls,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'category' => $category
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $date = (string)$this->getPost('date', date('Y-m-d'));
        $category = (string)$this->getPost('category', 'LAINNYA');
        
        if ($category === 'LAINNYA' && is_owner()) {
            $customCategory = trim((string)$this->getPost('custom_category'));
            if (!empty($customCategory)) {
                $category = strtoupper($customCategory);
            }
        }

        $amount = (float)$this->getPost('amount');
        $desc = trim((string)$this->getPost('description'));

        if ($amount <= 0 || empty($desc)) {
            $this->flash('danger', 'Nominal biaya dan keterangan wajib diisi.');
            $this->redirect('/expenses');
        }

        $id = ExpenseRepository::create([
            'date' => $date,
            'category' => $category,
            'amount' => $amount,
            'description' => $desc,
            'created_by' => auth_id()
        ]);

        AuditRepository::log(auth_id(), 'CREATE_EXPENSE', 'EXPENSE', 'EXPENSE', $id, null, [
            'category' => $category,
            'amount' => $amount,
            'description' => $desc
        ]);

        $this->flash('success', 'Biaya operasional berhasil dicatat.');
        $this->redirect('/expenses');
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $expenseId = (int)$id;

        $old = ExpenseRepository::findById($expenseId);
        if (!$old) {
            $this->flash('danger', 'Catatan biaya operasional tidak ditemukan.');
            $this->redirect('/expenses');
            return;
        }

        // Role Kasir hanya dapat mengedit pengeluaran milik sendiri
        if (!is_owner() && (int)($old['created_by'] ?? 0) !== (int)auth_id()) {
            $this->flash('danger', 'Akses ditolak. Anda hanya dapat mengubah pengeluaran yang Anda catat sendiri.');
            $this->redirect('/expenses');
            return;
        }

        $date = (string)$this->getPost('date');
        $category = (string)$this->getPost('category');
        
        if ($category === 'LAINNYA' && is_owner()) {
            $customCategory = trim((string)$this->getPost('custom_category'));
            if (!empty($customCategory)) {
                $category = strtoupper($customCategory);
            }
        }

        $amount = (float)$this->getPost('amount');
        $desc = trim((string)$this->getPost('description'));

        if ($amount <= 0 || empty($desc)) {
            $this->flash('danger', 'Nominal biaya dan keterangan wajib diisi.');
            $this->redirect('/expenses');
            return;
        }

        ExpenseRepository::update($expenseId, [
            'date' => $date,
            'category' => $category,
            'amount' => $amount,
            'description' => $desc
        ]);

        AuditRepository::log(auth_id(), 'UPDATE_EXPENSE', 'EXPENSE', 'EXPENSE', $expenseId, $old, [
            'category' => $category,
            'amount' => $amount,
            'description' => $desc
        ]);

        $this->flash('success', 'Biaya operasional berhasil diperbarui.');
        $this->redirect('/expenses');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $expenseId = (int)$id;

        $old = ExpenseRepository::findById($expenseId);
        if (!$old) {
            $this->flash('danger', 'Catatan biaya operasional tidak ditemukan.');
            $this->redirect('/expenses');
            return;
        }

        // Role Kasir hanya dapat menghapus pengeluaran milik sendiri; Owner bisa menghapus semua
        if (!is_owner() && (int)($old['created_by'] ?? 0) !== (int)auth_id()) {
            $this->flash('danger', 'Akses ditolak. Anda hanya dapat menghapus catatan biaya yang Anda input sendiri.');
            $this->redirect('/expenses');
            return;
        }

        ExpenseRepository::softDelete($expenseId);
        AuditRepository::log(auth_id(), 'DELETE_EXPENSE', 'EXPENSE', 'EXPENSE', $expenseId, $old, ['deleted_at' => date('Y-m-d H:i:s')]);
        $this->flash('success', 'Catatan biaya operasional berhasil dihapus.');

        $this->redirect('/expenses');
    }
}
