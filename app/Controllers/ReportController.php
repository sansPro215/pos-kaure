<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportService;
use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;

class ReportController extends Controller
{
    public function sales(): void
    {
        $startDate = isset($_GET['start_date']) && $_GET['start_date'] !== '' ? trim((string)$_GET['start_date']) : (isset($_GET['start_date']) ? null : date('Y-m-01'));
        $endDate = isset($_GET['end_date']) && $_GET['end_date'] !== '' ? trim((string)$_GET['end_date']) : (isset($_GET['end_date']) ? null : date('Y-m-d'));
        $cashierId = !empty($_GET['cashier_id']) ? (int)$_GET['cashier_id'] : null;
        $hasFilter = isset($_GET['start_date']) || isset($_GET['end_date']) || !empty($_GET['cashier_id']);

        $summary = ReportService::getFinancialSummary($startDate, $endDate, $cashierId);
        $topProducts = ReportService::getTopProducts($startDate, $endDate, 10, $cashierId);
        $transactions = TransactionRepository::getTransactions($startDate, $endDate, $cashierId, 'PAID', null, null, 250);
        
        // Show active cashiers (excluding owner)
        $cashiers = \App\Core\Database::fetchAll("SELECT id, name, username, role FROM users WHERE role = 'CASHIER' AND deleted_at IS NULL ORDER BY name ASC");

        $this->view('reports.sales', [
            'pageTitle' => 'Laporan Penjualan',
            'summary' => $summary,
            'topProducts' => $topProducts,
            'transactions' => $transactions,
            'cashiers' => $cashiers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'cashierId' => $cashierId,
            'hasFilter' => $hasFilter
        ]);
    }

    public function payments(): void
    {
        $startDate = $this->getQuery('start_date') ?: date('Y-m-01');
        $endDate = $this->getQuery('end_date') ?: date('Y-m-d');

        $summary = ReportService::getFinancialSummary($startDate, $endDate);

        $this->view('reports.payments', [
            'pageTitle' => 'Laporan Pembayaran (Cash vs Cashless)',
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }

    public function profit(): void
    {
        $startDate = $this->getQuery('start_date') ?: date('Y-m-01');
        $endDate = $this->getQuery('end_date') ?: date('Y-m-d');

        $summary = ReportService::getFinancialSummary($startDate, $endDate);

        $this->view('reports.profit', [
            'pageTitle' => 'Laporan HPP & Estimasi Laba Bersih',
            'summary' => $summary,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }
}
