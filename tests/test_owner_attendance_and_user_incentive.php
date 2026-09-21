<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => 1,
    'name' => 'Owner Kaure',
    'username' => 'owner',
    'role' => 'OWNER'
];

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Core/Controller.php';

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $rel = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $rel) . '.php';
    if (file_exists($file)) require_once $file;
});

foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) {
    require_once $f;
}

use App\Core\Database;
use App\Repositories\AttendanceRepository;
use App\Repositories\UserRepository;
use App\Controllers\AttendanceController;
use App\Controllers\UserController;

echo "======================================================================\n";
echo "  TEST: OWNER REMOVAL FROM ATTENDANCE & INTENSIVE MANAGER IN USERS   \n";
echo "======================================================================\n\n";

$pass = 0;
$fail = 0;

function check(bool $cond, string $msg): void {
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [PASS] {$msg}\n";
    } else {
        $fail++;
        echo "  [FAIL] {$msg}\n";
    }
}

// ---------------------------------------------------------------------
// 1. ATTENDANCE: ROLE OWNER REMOVED
// ---------------------------------------------------------------------
echo "1. Testing Attendance Exclusions for OWNER...\n";

// 1.1 getList should NOT return any OWNER records
$allLogs = AttendanceRepository::getList();
$ownerInLogs = array_filter($allLogs, fn($l) => $l['role'] === 'OWNER');
check(empty($ownerInLogs), "AttendanceRepository::getList() excludes role OWNER (found: " . count($ownerInLogs) . ")");

// 1.2 getSummaryToday should NOT count any OWNER records
$summary = AttendanceRepository::getSummaryToday();
check(is_array($summary) && isset($summary['total_logged']), "AttendanceRepository::getSummaryToday() executes correctly without owner");

// 1.3 AttendanceController index rendering for Owner
ob_start();
(new AttendanceController())->index();
$attHtml = ob_get_clean();

// Clock In / Clock Out card should NOT be visible to owner
check(strpos($attHtml, 'ABSEN MASUK') === false, "Owner does NOT see personal 'ABSEN MASUK' clock-in button");
check(strpos($attHtml, 'ABSEN PULANG') === false, "Owner does NOT see personal 'ABSEN PULANG' clock-out button");

// Dropdown filter should NOT have OWNER
check(strpos($attHtml, 'Owner Kaure (OWNER)') === false, "Attendance filter/modal dropdown does NOT contain Owner");

class TestAttendanceController extends AttendanceController {
    public string $lastRedirect = '';
    protected function redirect(string $path): void {
        $this->lastRedirect = $path;
    }
}

class TestUserController extends UserController {
    public string $lastRedirect = '';
    protected function redirect(string $path): void {
        $this->lastRedirect = $path;
    }
}

// 1.4 Test Clock In / Out rejection for Owner
$_POST['_token'] = csrf_token();
$controller = new TestAttendanceController();

$controller->clockIn();
check($_SESSION['flash']['type'] === 'info' && strpos($_SESSION['flash']['message'], 'Role Owner tidak memerlukan') !== false, "AttendanceController::clockIn() safely rejected for Owner");

$controller->clockOut();
check($_SESSION['flash']['type'] === 'info' && strpos($_SESSION['flash']['message'], 'Role Owner tidak memerlukan') !== false, "AttendanceController::clockOut() safely rejected for Owner");

// 1.5 Test storeManual rejection for Owner
$_POST['user_id'] = 1; // Owner
$_POST['date'] = date('Y-m-d');
$controller->storeManual();
check($_SESSION['flash']['type'] === 'danger' && strpos($_SESSION['flash']['message'], 'Role Owner tidak memerlukan') !== false, "AttendanceController::storeManual() safely rejected when selecting Owner");


// ---------------------------------------------------------------------
// 2. USER MANAGEMENT: INTENSIVE MANAGER FOR OWNER
// ---------------------------------------------------------------------
echo "\n2. Testing Intensive Manager for Owner in User Management...\n";

ob_start();
(new UserController())->index();
$usersHtml = ob_get_clean();

// Table should show Intensive Manager for owner
check(strpos($usersHtml, 'Intensive Manager') !== false, "Users table displays 'Intensive Manager'");
check(strpos($usersHtml, '% Laba Kotor') !== false, "Users table displays '% Laba Kotor' for Owner");

// Validate column count equality to prevent DataTables tn/18 warning
$dom = new DOMDocument();
@$dom->loadHTML($usersHtml);
$xpath = new DOMXPath($dom);
$thCount = $xpath->query('//table//thead//tr//th')->length;
check($thCount === 8, "Users thead has exactly 8 columns (found: {$thCount})");

$rows = $xpath->query('//table//tbody//tr');
$allRowsMatch = true;
foreach ($rows as $idx => $row) {
    $tdCount = $xpath->query('.//td', $row)->length;
    if ($tdCount !== $thCount) {
        $allRowsMatch = false;
        echo "  [ERROR] Row " . ($idx + 1) . " has {$tdCount} TDs but thead has {$thCount} THs!\n";
    }
}
check($allRowsMatch, "Every tbody row has exactly {$thCount} TDs (Zero DataTables tn/18 column count mismatch!)");

// Edit modal should contain Intensive Manager box and toggle
check(strpos($usersHtml, 'ownerIncentiveBoxEdit') !== false, "Edit user modal contains ownerIncentiveBoxEdit element");
check(strpos($usersHtml, 'toggleRateInputsEdit') !== false, "Edit user modal has toggleRateInputsEdit function");

// Add modal should contain Intensive Manager box and toggle
check(strpos($usersHtml, 'ownerIncentiveBoxAdd') !== false, "Add user modal contains ownerIncentiveBoxAdd element");
check(strpos($usersHtml, 'toggleRateInputsAdd') !== false, "Add user modal has toggleRateInputsAdd function");

// Verify Owner rate updating resets hourly_rate & overtime_rate to 0
$owner = UserRepository::findById(1);
$_POST = [
    '_token' => csrf_token(),
    'name' => $owner['name'],
    'username' => $owner['username'],
    'role' => 'OWNER',
    'phone' => '08123456789',
    'status' => 'ACTIVE',
    'hourly_rate' => '99999', // Should be overridden to 0
    'overtime_rate' => '99999' // Should be overridden to 0
];
$userCtrl = new TestUserController();
$userCtrl->update('1');

$updatedOwner = UserRepository::findById(1);
check((float)$updatedOwner['hourly_rate'] === 0.0, "Owner hourly_rate reset to 0.00 (managed via Intensive Manager)");
check((float)$updatedOwner['overtime_rate'] === 0.0, "Owner overtime_rate reset to 0.00 (managed via Intensive Manager)");

// ---------------------------------------------------------------------
// SUMMARY
// ---------------------------------------------------------------------
echo "\n======================================================================\n";
echo "RESULT: {$pass} PASSED, {$fail} FAILED\n";
echo "======================================================================\n";

if ($fail === 0) {
    echo "🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
} else {
    exit(1);
}
