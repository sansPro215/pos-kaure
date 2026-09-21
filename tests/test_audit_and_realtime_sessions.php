<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Core/Database.php';

// Load helpers
foreach (glob(__DIR__ . '/../app/Helpers/*.php') as $f) {
    require_once $f;
}

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Core\Database;
use App\Services\SessionTracker;
use App\Repositories\AuditRepository;

echo "=== TESTING AUDIT LOG & REALTIME SESSION TRACKING ===\n\n";

// 1. Test IP Detection with Cloudflare header simulation
$_SERVER['HTTP_CF_CONNECTING_IP'] = '180.252.164.55';
$_SERVER['REMOTE_ADDR'] = '172.70.142.10'; // Cloudflare proxy IP
assert(get_client_ip() === '180.252.164.55', 'Cloudflare real IP recognized');
echo "[TEST 1] Cloudflare IP Detection: PASS (180.252.164.55)\n";
unset($_SERVER['HTTP_CF_CONNECTING_IP']);

// Test IP Detection with X-Forwarded-For header simulation (multiple proxy chain)
$_SERVER['HTTP_X_FORWARDED_FOR'] = '103.247.11.20, 10.0.0.1';
assert(get_client_ip() === '103.247.11.20', 'X-Forwarded-For client IP recognized');
echo "[TEST 2] X-Forwarded-For Detection: PASS (103.247.11.20)\n";
unset($_SERVER['HTTP_X_FORWARDED_FOR']);

// 2. Test Device Parsing
$androidUa = 'Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';
$device = get_client_device($androidUa);
assert(strpos($device, 'Android') !== false && strpos($device, 'Chrome') !== false, 'Android detected');
echo "[TEST 3] Android Device Parser: PASS ({$device})\n";

$winUa = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';
$winDevice = get_client_device($winUa);
assert(strpos($winDevice, 'Windows') !== false, 'Windows detected');
echo "[TEST 4] Windows Device Parser: PASS ({$winDevice})\n";

// 3. Test SessionTracker touch
$_SERVER['HTTP_USER_AGENT'] = $winUa;
$_SERVER['REMOTE_ADDR'] = '182.253.40.10';
$_SERVER['REQUEST_URI'] = '/pos';

$testSessionId = 'sess_test_andi_' . time();
SessionTracker::touch(2, $testSessionId);

$savedSession = Database::fetch("SELECT * FROM active_sessions WHERE id = ? LIMIT 1", [$testSessionId]);
assert($savedSession !== null, 'Session was saved to active_sessions');
assert((int)$savedSession['user_id'] === 2, 'User ID matches');
assert($savedSession['ip_address'] === '182.253.40.10', 'IP matches');
echo "\n[TEST 5] SessionTracker::touch(): PASS (Session saved with real IP {$savedSession['ip_address']})\n";

// 4. Test SessionTracker getActiveSessions
$activeSessions = SessionTracker::getActiveSessions(30);
assert(count($activeSessions) >= 1, 'At least 1 active session returned');
$found = false;
foreach ($activeSessions as $s) {
    if ($s['id'] === $testSessionId) {
        $found = true;
        assert($s['online_status'] === 'ONLINE', 'Status is ONLINE');
        assert($s['user_name'] !== '', 'User name populated');
        break;
    }
}
assert($found, 'Saved session found in getActiveSessions');
echo "[TEST 6] SessionTracker::getActiveSessions(): PASS (Found active session with status ONLINE)\n";

// 5. Test AuditRepository log with accurate IP
AuditRepository::log(
    2,
    'LOGIN',
    'AUTH',
    'USER',
    2,
    null,
    ['username' => 'andi', 'role' => 'CASHIER', 'ip' => '182.253.40.10']
);

$latestLog = Database::fetch("SELECT * FROM audit_logs WHERE user_id = 2 AND action = 'LOGIN' ORDER BY id DESC LIMIT 1");
assert($latestLog !== null, 'Audit log saved');
assert($latestLog['ip_address'] === '182.253.40.10', 'Audit log IP matches client IP');
echo "[TEST 7] AuditRepository::log(): PASS (Saved audit log with IP {$latestLog['ip_address']})\n";

// 6. Test views/audit/index.php rendering
$logs = AuditRepository::filter(null, null, null, null, 10);
$users = Database::fetchAll("SELECT id, name, role FROM users WHERE deleted_at IS NULL");
$module = null;
$action = null;
$userId = null;
$search = null;

ob_start();
include __DIR__ . '/../views/audit/index.php';
$html = ob_get_clean();

assert(strpos($html, 'Akun yang Sedang Digunakan (Sesi Realtime)') !== false, 'Active sessions section rendered');
assert(strpos($html, 'IP Anda Terdeteksi') !== false, 'Detected IP rendered');
assert(strpos($html, 'Riwayat Kronologis Audit Trail') !== false, 'Audit trail table rendered');
echo "[TEST 8] views/audit/index.php Rendering: PASS (Includes Realtime Sessions & Audit Trail)\n";

// Clean up test session
SessionTracker::destroy($testSessionId);
assert(Database::fetch("SELECT id FROM active_sessions WHERE id = ? LIMIT 1", [$testSessionId]) === null, 'Session cleaned up');
echo "[TEST 9] SessionTracker::destroy(): PASS (Session terminated cleanly)\n";

echo "\n=== ALL TESTS PASSED (100% SUCCESS) ===\n";
