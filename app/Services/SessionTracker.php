<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\AuditRepository;

class SessionTracker
{
    /**
     * Record or update an active user session in realtime
     */
    public static function touch(int $userId, string $sessionId): void
    {
        if (empty($sessionId) || $userId <= 0) {
            return;
        }

        $ip = get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $device = get_client_device($ua);
        $url = substr((string)($_SERVER['REQUEST_URI'] ?? '/'), 0, 255);

        try {
            $sql = "INSERT INTO active_sessions 
                    (id, user_id, ip_address, user_agent, device_info, current_url, last_activity_at, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW()) 
                    ON DUPLICATE KEY UPDATE 
                        ip_address = VALUES(ip_address),
                        user_agent = VALUES(user_agent),
                        device_info = VALUES(device_info),
                        current_url = VALUES(current_url),
                        last_activity_at = NOW()";
            Database::execute($sql, [$sessionId, $userId, $ip, $ua, $device, $url]);

            // Update user table fast timestamp & IP
            Database::execute("UPDATE users SET last_activity_at = NOW(), last_ip = ? WHERE id = ?", [$ip, $userId]);
        } catch (\Throwable $e) {
            error_log("SessionTracker touch error: " . $e->getMessage());
        }
    }

    /**
     * Remove session on logout
     */
    public static function destroy(string $sessionId): void
    {
        if (empty($sessionId)) return;

        try {
            Database::execute("DELETE FROM active_sessions WHERE id = ?", [$sessionId]);
        } catch (\Throwable $e) {
            error_log("SessionTracker destroy error: " . $e->getMessage());
        }
    }

    /**
     * Fetch all active realtime user sessions
     */
    public static function getActiveSessions(int $idleThresholdMinutes = 15): array
    {
        // Clean sessions inactive for more than 2 hours first
        self::cleanExpired(120);

        try {
            $sql = "SELECT s.*, u.name as user_name, u.username, u.role, u.status as user_status,
                           TIMESTAMPDIFF(SECOND, s.last_activity_at, NOW()) as seconds_ago
                    FROM active_sessions s
                    JOIN users u ON s.user_id = u.id
                    WHERE s.last_activity_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
                    ORDER BY s.last_activity_at DESC";
            $sessions = Database::fetchAll($sql, [$idleThresholdMinutes]);

            foreach ($sessions as &$s) {
                $sec = (int)($s['seconds_ago'] ?? 0);
                if ($sec <= 300) {
                    $s['online_status'] = 'ONLINE'; // Green (< 5 min)
                    $s['status_label'] = 'Aktif Sekarang';
                    $s['status_badge'] = 'success';
                } else {
                    $s['online_status'] = 'IDLE'; // Yellow (5 - 15 min)
                    $s['status_label'] = 'Idle (' . round($sec / 60) . ' mnt lalu)';
                    $s['status_badge'] = 'warning';
                }
            }

            return $sessions;
        } catch (\Throwable $e) {
            error_log("SessionTracker getActiveSessions error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Terminate / Force Logout a user session (Owner power)
     */
    public static function killSession(string $sessionId, ?int $byUserId = null): bool
    {
        try {
            $session = Database::fetch("SELECT s.*, u.name, u.username FROM active_sessions s JOIN users u ON s.user_id = u.id WHERE s.id = ? LIMIT 1", [$sessionId]);
            if ($session) {
                AuditRepository::log(
                    $byUserId,
                    'FORCE_LOGOUT',
                    'AUTH',
                    'SESSION',
                    (int)$session['user_id'],
                    ['session_id' => $sessionId, 'user' => $session['username']],
                    ['ip' => $session['ip_address'], 'killed_by' => $byUserId]
                );
            }
            Database::execute("DELETE FROM active_sessions WHERE id = ?", [$sessionId]);
            return true;
        } catch (\Throwable $e) {
            error_log("SessionTracker killSession error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Garbage collection for stale sessions
     */
    public static function cleanExpired(int $timeoutMinutes = 120): void
    {
        try {
            Database::execute("DELETE FROM active_sessions WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)", [$timeoutMinutes]);
        } catch (\Throwable $e) {
            // Ignore error
        }
    }
}
