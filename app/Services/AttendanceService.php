<?php

namespace App\Services;

use App\Repositories\AttendanceRepository;
use App\Repositories\AuditRepository;
use Exception;

class AttendanceService
{
    public static function clockIn(int $userId): array
    {
        $existing = AttendanceRepository::getToday($userId);
        if ($existing) {
            return ['status' => false, 'message' => 'Anda sudah melakukan absen masuk hari ini pada pukul ' . substr($existing['clock_in'], 0, 5) . '.'];
        }

        $id = AttendanceRepository::clockIn($userId, date('H:i:s'), false, 'Absen masuk mandiri');
        if (!$id) {
            return ['status' => false, 'message' => 'Gagal mencatat absen masuk.'];
        }

        AuditRepository::log($userId, 'CLOCK_IN', 'ATTENDANCE', 'ATTENDANCE', $id, null, ['time' => date('H:i:s')]);
        return ['status' => true, 'message' => 'Absen masuk berhasil dicatat. Selamat bekerja!'];
    }

    public static function clockOut(int $userId): array
    {
        $existing = AttendanceRepository::getToday($userId);
        if (!$existing || empty($existing['clock_in'])) {
            return ['status' => false, 'message' => 'Anda belum melakukan absen masuk hari ini.'];
        }

        if (!empty($existing['clock_out'])) {
            return ['status' => false, 'message' => 'Anda sudah melakukan absen pulang hari ini pada pukul ' . substr($existing['clock_out'], 0, 5) . '.'];
        }

        $success = AttendanceRepository::clockOut($userId, date('H:i:s'));
        if (!$success) {
            return ['status' => false, 'message' => 'Gagal mencatat absen pulang.'];
        }

        AuditRepository::log($userId, 'CLOCK_OUT', 'ATTENDANCE', 'ATTENDANCE', (int)$existing['id'], null, ['time' => date('H:i:s')]);
        return ['status' => true, 'message' => 'Absen pulang berhasil dicatat. Terima kasih!'];
    }

    public static function correctAttendance(int $id, array $data, int $ownerId): bool
    {
        $old = AttendanceRepository::findById($id);
        if (!$old) {
            throw new Exception("Data absensi tidak ditemukan.");
        }

        $res = AttendanceRepository::update($id, $data);
        if ($res) {
            AuditRepository::log(
                $ownerId,
                'UPDATE_ATTENDANCE',
                'ATTENDANCE',
                'ATTENDANCE',
                $id,
                $old,
                $data
            );
        }
        return $res;
    }
}
