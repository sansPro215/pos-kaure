<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ReportService;
use App\Repositories\TransactionRepository;
use App\Repositories\AttendanceRepository;

class DashboardController extends Controller
{
    public function index(): void
    {
        $today = date('Y-m-d');
        
        // 1. Today's Financial Summary
        $todaySummary = ReportService::getFinancialSummary($today, $today);

        // 2. Attendance Summary & Detail Today
        $attendanceToday = AttendanceRepository::getSummaryToday();
        $todayAttendanceList = AttendanceRepository::getList($today, $today);

        // 3. Top 5 Products Today
        $topProducts = ReportService::getTopProducts($today, $today, 5);

        // 4. Recent 5 Transactions (Most Recent First)
        $recentTransactions = TransactionRepository::getTransactions(null, null, null, null, null, null, 5);

        // 5. 7-Day Trend Chart Data
        $trendData = ReportService::getSalesTrend(7);

        $this->view('dashboard.index', [
            'pageTitle' => 'Dashboard Owner',
            'summary' => $todaySummary,
            'attendance' => $attendanceToday,
            'todayAttendanceList' => $todayAttendanceList,
            'topProducts' => $topProducts,
            'recentTransactions' => $recentTransactions,
            'trendData' => $trendData
        ]);
    }

    public function print(): void
    {
        $today = date('Y-m-d');
        
        $todaySummary = ReportService::getFinancialSummary($today, $today);
        $attendanceToday = AttendanceRepository::getSummaryToday();
        $todayAttendanceList = AttendanceRepository::getList($today, $today);
        $topProducts = ReportService::getTopProducts($today, $today, 10);
        $recentTransactions = TransactionRepository::getTransactions(null, null, null, null, null, null, 15);

        $this->view('dashboard.print', [
            'pageTitle' => 'Laporan Ringkasan Dashboard - ' . date('d/m/Y'),
            'summary' => $todaySummary,
            'attendance' => $attendanceToday,
            'todayAttendanceList' => $todayAttendanceList,
            'topProducts' => $topProducts,
            'recentTransactions' => $recentTransactions
        ]);
    }
}
