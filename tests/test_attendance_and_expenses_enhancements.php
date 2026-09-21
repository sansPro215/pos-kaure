<?php
/**
 * Test Suite for Attendance Detail Modal, Finished Status, Cashier Clock-Out POS Lock,
 * Owner Attendance Corrections, and Cashier Expenses Access.
 */

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
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) require_once $f;

use App\Core\Database;
use App\Repositories\AttendanceRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\UserRepository;
use App\Services\AttendanceService;

echo "=== TESTING ATTENDANCE & EXPENSES ENHANCEMENTS ===\n\n";

// 1. Get cashier and owner accounts
$cashier = Database::fetch("SELECT * FROM users WHERE role = 'CASHIER' AND status = 'ACTIVE' LIMIT 1");
$owner = Database::fetch("SELECT * FROM users WHERE role = 'OWNER' AND status = 'ACTIVE' LIMIT 1");

if (!$cashier || !$owner) {
    die("FATAL: Test users not found in database.\n");
}

$cashierId = (int)$cashier['id'];
$ownerId = (int)$owner['id'];
$today = date('Y-m-d');

echo "Test Cashier: {$cashier['name']} (ID: {$cashierId})\n";
echo "Test Owner: {$owner['name']} (ID: {$ownerId})\n\n";

// Clean up existing attendance today for cashier
Database::execute("DELETE FROM attendance WHERE user_id = ? AND date = ?", [$cashierId, $today]);

// [TEST 1] Clock-in sets status to HADIR
$attId = AttendanceRepository::clockIn($cashierId, '08:00:00', false, 'Test clock-in');
assert($attId > 0, "Clock-in successful");
$attRecord = AttendanceRepository::findById($attId);
assert($attRecord['status'] === 'HADIR', "Initial status must be HADIR");
assert(empty($attRecord['clock_out']), "Clock-out must be empty initially");
echo "[TEST 1] Clock-In Status HADIR: PASS\n";

// [TEST 2] Clock-out sets status to SELESAI
$clockOutSuccess = AttendanceRepository::clockOut($cashierId, '17:00:00');
assert($clockOutSuccess === true, "Clock-out update query must succeed");
$completedRecord = AttendanceRepository::findById($attId);
assert($completedRecord['status'] === 'SELESAI', "Status after clock-out must be SELESAI");
assert(!empty($completedRecord['clock_out']), "Clock-out time must be recorded");
assert((int)$completedRecord['worked_minutes'] === 540, "Worked minutes must be 540 (9 hours)");
echo "[TEST 2] Clock-Out Status SELESAI & Worked Hours: PASS\n";

// [TEST 3] Summary counts finished shift correctly
$summary = AttendanceRepository::getSummaryToday();
assert((int)$summary['finished_shift'] >= 1, "Finished shift counter must count SELESAI");
echo "[TEST 3] Summary finished_shift counting: PASS\n";

// [TEST 4] Owner Correction: Batalkan Status Selesai (Re-activate cashier shift)
$cancelRes = AttendanceRepository::update($attId, [
    'clock_in' => '08:00:00',
    'clock_out' => null,
    'status' => 'HADIR',
    'note' => 'Status selesai dibatalkan oleh Owner'
]);
assert($cancelRes === true, "Attendance update must succeed");
$reopenedRecord = AttendanceRepository::findById($attId);
assert($reopenedRecord['status'] === 'HADIR', "Reopened status must be HADIR");
assert(empty($reopenedRecord['clock_out']), "Clock-out must be null after cancellation");
assert((int)$reopenedRecord['worked_minutes'] === 0, "Worked minutes must reset to 0 while shift active");
echo "[TEST 4] Owner Cancel Status SELESAI (Re-open shift): PASS\n";

// [TEST 5] Owner Correction: Tandai Selesai (Jam Sekarang / Custom)
$customTime = '16:30:00';
$finishRes = AttendanceRepository::update($attId, [
    'clock_in' => '08:00:00',
    'clock_out' => $customTime,
    'status' => 'SELESAI',
    'note' => 'Diset selesai manual'
]);
assert($finishRes === true, "Attendance finish update must succeed");
$finalRecord = AttendanceRepository::findById($attId);
assert($finalRecord['status'] === 'SELESAI', "Status must be SELESAI");
assert(substr($finalRecord['clock_out'], 0, 5) === '16:30', "Custom clock_out must match");
assert((int)$finalRecord['worked_minutes'] === 510, "Worked minutes must be 510");
echo "[TEST 5] Owner Custom Finish Setting: PASS\n";

// [TEST 6] Cashier Expense Creation & Retrieval
$testExpDesc = 'Beli Galon Aqua Uji Kasir ' . time();
$expId = ExpenseRepository::create([
    'date' => $today,
    'category' => 'AIR',
    'amount' => 20000,
    'description' => $testExpDesc,
    'created_by' => $cashierId
]);
assert($expId > 0, "Cashier expense creation must succeed");
$foundExp = ExpenseRepository::findById($expId);
assert($foundExp['description'] === $testExpDesc, "Expense record retrieved accurately");
assert((int)$foundExp['created_by'] === $cashierId, "Expense created_by must match cashier ID");
// Cleanup test expense
Database::execute("DELETE FROM operational_expenses WHERE id = ?", [$expId]);
echo "[TEST 6] Cashier Operational Expense Creation: PASS\n";

// [TEST 7] View Rendering Check
ob_start();
$_SESSION['user'] = $owner;
$attendance = AttendanceRepository::getSummaryToday();
$todayAttendanceList = AttendanceRepository::getList($today, $today);
$summary = ['total_sales' => 0, 'cash_sales' => 0, 'cashless_sales' => 0, 'total_cogs' => 0, 'gross_profit' => 0, 'total_expenses' => 0, 'net_profit' => 0, 'operational_expenses' => 0, 'staff_payroll_expenses' => 0, 'transaction_count' => 0];
$lowProducts = [];
$lowIngredients = [];
$topProducts = [];
$recentTransactions = [];
$trendData = ['labels' => [], 'omzet' => []];
include __DIR__ . '/../views/dashboard/index.php';
$dashHtml = ob_get_clean();
assert(str_contains($dashHtml, 'id="attendanceDetailModal"'), "Dashboard has attendance detail modal");
assert(str_contains($dashHtml, 'openAttendanceModalWithFilter'), "Dashboard has filter helper function");
assert(str_contains($dashHtml, 'Sedang Bekerja'), "Dashboard renders Sedang Bekerja counter");
echo "[TEST 7] Dashboard Attendance Modal Rendering: PASS\n";

// [TEST 8] POS Shift Completed View Rendering
ob_start();
$_SESSION['user'] = $cashier;
$todayAttendance = $finalRecord;
$settings = ['shop_name' => 'Warung Kaure'];
include __DIR__ . '/../views/pos/shift_completed.php';
$posHtml = ob_get_clean();
assert(str_contains($posHtml, 'Shift Selesai'), "POS completed view has Shift Selesai heading");
assert(str_contains($posHtml, 'Akun anda sudah absen pulang, tidak bisa melakukan transaksi'), "POS completed view contains required user warning text");
echo "[TEST 8] POS Shift Completed View & Warning: PASS\n";

echo "\n=== ALL 8 TESTS PASSED (100% SUCCESS) ===\n";
