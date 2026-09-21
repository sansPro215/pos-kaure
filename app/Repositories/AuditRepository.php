<?php

namespace App\Repositories;

use App\Core\Database;

class AuditRepository
{
    public static function log(
        ?int $userId,
        string $action,
        string $module,
        ?string $refType = null,
        ?int $refId = null,
        ?array $oldValues = null,
        ?array $newValues = null
    ): void {
        // Strip sensitive fields
        $filter = function (?array $data) {
            if (!$data) return null;
            $sensitive = ['password', 'password_hash', '_token'];
            foreach ($sensitive as $s) {
                if (isset($data[$s])) {
                    $data[$s] = '***REDACTED***';
                }
            }
            return $data;
        };

        $oldJson = $oldValues ? json_encode($filter($oldValues), JSON_UNESCAPED_UNICODE) : null;
        $newJson = $newValues ? json_encode($filter($newValues), JSON_UNESCAPED_UNICODE) : null;

        $ip = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI/Unknown';

        $sql = "INSERT INTO audit_logs 
                (user_id, action, module, reference_type, reference_id, old_values_json, new_values_json, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        try {
            Database::execute($sql, [
                $userId,
                $action,
                $module,
                $refType,
                $refId,
                $oldJson,
                $newJson,
                $ip,
                $ua
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to write audit log: " . $e->getMessage());
        }
    }

    public static function getAll(int $limit = 100): array
    {
        $sql = "SELECT a.*, u.name as user_name, u.username 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                ORDER BY a.created_at DESC, a.id DESC 
                LIMIT ?";
        return Database::fetchAll($sql, [$limit]);
    }

    public static function filter(?string $module = null, ?string $action = null, ?int $userId = null, ?string $search = null, int $limit = 200): array
    {
        $where = [];
        $params = [];

        if (!empty($module)) {
            $where[] = "a.module = ?";
            $params[] = $module;
        }
        if (!empty($action)) {
            $where[] = "a.action = ?";
            $params[] = $action;
        }
        if (!empty($userId)) {
            $where[] = "a.user_id = ?";
            $params[] = $userId;
        }
        if (!empty($search)) {
            $where[] = "(u.name LIKE ? OR u.username LIKE ? OR a.ip_address LIKE ? OR a.action LIKE ? OR a.module LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "SELECT a.*, u.name as user_name, u.username 
                FROM audit_logs a 
                LEFT JOIN users u ON a.user_id = u.id 
                {$whereClause} 
                ORDER BY a.created_at DESC, a.id DESC 
                LIMIT ?";
        $params[] = $limit;

        return Database::fetchAll($sql, $params);
    }
}
