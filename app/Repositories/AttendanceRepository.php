<?php

namespace App\Repositories;

use App\Core\Database;

class AttendanceRepository
{
    public static function getToday(int $userId): ?array
    {
        $today = date('Y-m-d');
        return Database::fetch(
            "SELECT * FROM attendance WHERE user_id = ? AND date = ? LIMIT 1",
            [$userId, $today]
        );
    }

    public static function findById(int $id): ?array
    {
        $sql = "SELECT a.*, u.name as user_name, u.role, u.username 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                WHERE a.id = ? LIMIT 1";
        return Database::fetch($sql, [$id]);
    }

    public static function clockIn(int $userId, ?string $time = null, bool $auto = false, ?string $note = null): ?int
    {
        $today = date('Y-m-d');
        $existing = self::getToday($userId);
        if ($existing) {
            return null; // Already clocked in today
        }

        $clockInTime = $time ?: date('H:i:s');
        $sql = "INSERT INTO attendance (user_id, date, clock_in, status, auto_clock_in, note) 
                VALUES (?, ?, ?, 'HADIR', ?, ?)";
        Database::execute($sql, [$userId, $today, $clockInTime, $auto ? 1 : 0, $note]);
        return (int)Database::lastInsertId();
    }

    public static function clockOut(int $userId, ?string $time = null): bool
    {
        $today = date('Y-m-d');
        $existing = self::getToday($userId);
        if (!$existing || empty($existing['clock_in'])) {
            return false;
        }

        $clockOutTime = $time ?: date('H:i:s');
        $inTs = strtotime($today . ' ' . $existing['clock_in']);
        $outTs = strtotime($today . ' ' . $clockOutTime);

        $workedSeconds = max(0, $outTs - $inTs);
        $workedMinutes = (int)floor($workedSeconds / 60);
        $regularMinutes = min($workedMinutes, 480);
        $rawOtMinutes = max(0, $workedMinutes - 480);
        $overtimeMinutes = (int)floor($rawOtMinutes / 60) * 60; // Paten dibulatkan per 1 jam penuh (60 menit)

        $sql = "UPDATE attendance SET 
                clock_out = ?, 
                worked_minutes = ?, 
                regular_minutes = ?, 
                overtime_minutes = ?, 
                status = 'SELESAI' 
                WHERE id = ?";

        return Database::execute($sql, [
            $clockOutTime,
            $workedMinutes,
            $regularMinutes,
            $overtimeMinutes,
            $existing['id']
        ]);
    }

    public static function update(int $id, array $data): bool
    {
        $existing = self::findById($id);
        $recordDate = $data['date'] ?? ($existing['date'] ?? date('Y-m-d'));
        $inTs = !empty($data['clock_in']) ? strtotime($recordDate . ' ' . $data['clock_in']) : null;
        $outTs = !empty($data['clock_out']) ? strtotime($recordDate . ' ' . $data['clock_out']) : null;

        $workedMinutes = 0;
        $regularMinutes = 0;
        $overtimeMinutes = 0;

        if ($inTs && $outTs && $outTs > $inTs) {
            $workedMinutes = (int)floor(($outTs - $inTs) / 60);
            $regularMinutes = min($workedMinutes, 480);
            $rawOtMinutes = max(0, $workedMinutes - 480);
            $overtimeMinutes = (int)floor($rawOtMinutes / 60) * 60; // Paten dibulatkan per 1 jam penuh (60 menit)
        }

        $sql = "UPDATE attendance SET 
                clock_in = ?, 
                clock_out = ?, 
                worked_minutes = ?, 
                regular_minutes = ?, 
                overtime_minutes = ?, 
                status = ?, 
                note = ? 
                WHERE id = ?";

        return Database::execute($sql, [
            $data['clock_in'] ?: null,
            $data['clock_out'] ?: null,
            $workedMinutes,
            $regularMinutes,
            $overtimeMinutes,
            $data['status'] ?? 'HADIR',
            $data['note'] ?? null,
            $id
        ]);
    }

    /**
     * Check if an attendance record already exists for a given user on a given date.
     */
    public static function existsByUserAndDate(int $userId, string $date): bool
    {
        $row = Database::fetch(
            "SELECT id FROM attendance WHERE user_id = ? AND date = ? LIMIT 1",
            [$userId, $date]
        );
        return !empty($row);
    }

    public static function createManual(array $data): int
    {
        $inTs = !empty($data['clock_in']) ? strtotime($data['date'] . ' ' . $data['clock_in']) : null;
        $outTs = !empty($data['clock_out']) ? strtotime($data['date'] . ' ' . $data['clock_out']) : null;

        $workedMinutes = 0;
        $regularMinutes = 0;
        $overtimeMinutes = 0;

        if ($inTs && $outTs && $outTs > $inTs) {
            $workedMinutes = (int)floor(($outTs - $inTs) / 60);
            $regularMinutes = min($workedMinutes, 480);
            $rawOtMinutes = max(0, $workedMinutes - 480);
            $overtimeMinutes = (int)floor($rawOtMinutes / 60) * 60; // Paten dibulatkan per 1 jam penuh (60 menit)
        }

        $sql = "INSERT INTO attendance 
                (user_id, date, clock_in, clock_out, worked_minutes, regular_minutes, overtime_minutes, status, note) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        Database::execute($sql, [
            $data['user_id'],
            $data['date'],
            $data['clock_in'] ?: null,
            $data['clock_out'] ?: null,
            $workedMinutes,
            $regularMinutes,
            $overtimeMinutes,
            $data['status'] ?? 'HADIR',
            $data['note'] ?? null
        ]);

        return (int)Database::lastInsertId();
    }

    public static function getList(?string $startDate = null, ?string $endDate = null, ?int $userId = null, ?string $status = null): array
    {
        $where = ["u.role != 'OWNER'"];
        $params = [];

        if (!empty($startDate)) {
            $where[] = "a.date >= ?";
            $params[] = $startDate;
        }
        if (!empty($endDate)) {
            $where[] = "a.date <= ?";
            $params[] = $endDate;
        }
        if (!empty($userId)) {
            $where[] = "a.user_id = ?";
            $params[] = $userId;
        }
        if (!empty($status)) {
            $where[] = "a.status = ?";
            $params[] = $status;
        }

        $whereSql = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";

        $sql = "SELECT a.*, u.name as user_name, u.role, u.username 
                FROM attendance a 
                JOIN users u ON a.user_id = u.id 
                {$whereSql} 
                ORDER BY a.date DESC, COALESCE(a.clock_in, '00:00:00') DESC, a.created_at DESC, a.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function getSummaryToday(): array
    {
        $today = date('Y-m-d');
        $sql = "SELECT 
                    COUNT(*) as total_logged,
                    SUM(CASE WHEN a.status = 'HADIR' AND a.clock_out IS NULL THEN 1 ELSE 0 END) as currently_working,
                    SUM(CASE WHEN a.status = 'SELESAI' OR (a.status = 'HADIR' AND a.clock_out IS NOT NULL) THEN 1 ELSE 0 END) as finished_shift,
                    SUM(CASE WHEN a.status = 'IZIN' THEN 1 ELSE 0 END) as izin_count,
                    SUM(CASE WHEN a.status = 'SAKIT' THEN 1 ELSE 0 END) as sakit_count,
                    SUM(CASE WHEN a.status = 'ALPHA' THEN 1 ELSE 0 END) as alpha_count,
                    SUM(CASE WHEN a.status = 'LIBUR' THEN 1 ELSE 0 END) as libur_count
                FROM attendance a
                JOIN users u ON a.user_id = u.id
                WHERE a.date = ? AND u.role != 'OWNER'";
        return Database::fetch($sql, [$today]) ?: [
            'total_logged' => 0,
            'currently_working' => 0,
            'finished_shift' => 0,
            'izin_count' => 0,
            'sakit_count' => 0,
            'alpha_count' => 0,
            'libur_count' => 0
        ];
    }

    public static function delete(int $id): bool
    {
        return Database::execute("DELETE FROM attendance WHERE id = ?", [$id]);
    }
}
