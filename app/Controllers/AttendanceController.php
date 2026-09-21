<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AttendanceRepository;
use App\Repositories\UserRepository;
use App\Services\AttendanceService;
use Exception;

class AttendanceController extends Controller
{
    public function index(): void
    {
        $userId = auth_id();
        $isOwner = is_owner();

        $todayAttendance = AttendanceRepository::getToday($userId);

        $startDate = $this->getQuery('start_date') ?: date('Y-m-01');
        $endDate = $this->getQuery('end_date') ?: date('Y-m-d');
        $filterUserId = $isOwner ? ($this->getQuery('user_id') ? (int)$this->getQuery('user_id') : null) : $userId;
        $status = $this->getQuery('status');

        $logs = AttendanceRepository::getList($startDate, $endDate, $filterUserId, $status);
        $users = array_values(array_filter(UserRepository::getAll(), fn($u) => $u['role'] !== 'OWNER'));
        $summaryToday = AttendanceRepository::getSummaryToday();

        $this->view('attendance.index', [
            'pageTitle' => 'Absensi Pegawai',
            'todayAttendance' => $todayAttendance,
            'logs' => $logs,
            'users' => $users,
            'summaryToday' => $summaryToday,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterUserId' => $filterUserId,
            'status' => $status
        ]);
    }

    public function clockIn(): void
    {
        $this->validateCsrf();

        if (is_owner()) {
            $this->flash('info', 'Role Owner tidak memerlukan pencatatan absensi.');
            $this->redirect('/attendance');
            return;
        }

        $res = AttendanceService::clockIn(auth_id());

        if ($res['status']) {
            $this->flash('success', $res['message']);
        } else {
            $this->flash('warning', $res['message']);
        }

        $redirectTo = (string)$this->getPost('redirect_to');
        if ($redirectTo && str_starts_with($redirectTo, '/')) {
            $this->redirect($redirectTo);
        }

        $this->redirect('/attendance');
    }

    public function clockOut(): void
    {
        $this->validateCsrf();

        if (is_owner()) {
            $this->flash('info', 'Role Owner tidak memerlukan pencatatan absensi.');
            $this->redirect('/attendance');
            return;
        }

        $res = AttendanceService::clockOut(auth_id());

        if ($res['status']) {
            $this->flash('success', $res['message']);
        } else {
            $this->flash('warning', $res['message']);
        }

        $redirectTo = (string)$this->getPost('redirect_to');
        if ($redirectTo && str_starts_with($redirectTo, '/')) {
            $this->redirect($redirectTo);
        }

        $this->redirect('/attendance');
    }

    public function storeManual(): void
    {
        $this->validateCsrf();

        $targetUserId = (int)$this->getPost('user_id');
        $targetUser = UserRepository::findById($targetUserId);
        if ($targetUser && $targetUser['role'] === 'OWNER') {
            $this->flash('danger', 'Role Owner tidak memerlukan pencatatan absensi.');
            $this->redirect('/attendance');
            return;
        }

        $date = (string)$this->getPost('date');
        $clockIn = (string)$this->getPost('clock_in');
        $clockOut = (string)$this->getPost('clock_out');
        $status = (string)$this->getPost('status', 'HADIR');
        $note = (string)$this->getPost('note');

        // Validasi 1: Tanggal tidak boleh di masa depan
        $today = date('Y-m-d');
        if (!empty($date) && $date > $today) {
            $this->flash('danger', '⛔ Tanggal tidak valid! Absensi tidak dapat diinput untuk tanggal yang akan datang (' . date('d/m/Y', strtotime($date)) . '). Gunakan tanggal hari ini atau sebelumnya.');
            $this->redirect('/attendance');
            return;
        }

        // Validasi 2: Cek duplikat (user + tanggal sudah ada)
        if (AttendanceRepository::existsByUserAndDate($targetUserId, $date)) {
            $this->flash('warning', '⚠️ Data duplikat! Catatan absensi untuk pegawai ini pada tanggal ' . date('d/m/Y', strtotime($date)) . ' sudah ada. Gunakan fitur Edit jika ingin mengubah data.');
            $this->redirect('/attendance');
            return;
        }

        try {
            AttendanceRepository::createManual([
                'user_id' => $targetUserId,
                'date' => $date,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'status' => $status,
                'note' => $note ?: 'Input manual oleh owner'
            ]);
            $this->flash('success', 'Catatan absensi berhasil ditambahkan.');
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal mencatat absensi: ' . $e->getMessage());
        }

        $this->redirect('/attendance');
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $attId = (int)$id;

        $clockIn = (string)$this->getPost('clock_in');
        $clockOut = (string)$this->getPost('clock_out');
        $status = (string)$this->getPost('status', 'HADIR');
        $note = (string)$this->getPost('note');
        $actionType = (string)$this->getPost('action_type');

        // Pilihan aksi khusus dari modal koreksi absensi
        if ($actionType === 'cancel_finished') {
            $clockOut = null;
            $status = 'HADIR';
            if (empty($note)) {
                $note = 'Status selesai dibatalkan oleh Owner (shift diaktifkan kembali)';
            }
        } elseif ($actionType === 'finish_now') {
            $clockOut = date('H:i');
            $status = 'SELESAI';
            if (empty($note)) {
                $note = 'Ditandai selesai pada jam sekarang oleh Owner';
            }
        } else {
            // Penyesuaian status otomatis jika jam keluar diisi/dikosongkan
            if ($status === 'SELESAI' && empty($clockOut)) {
                $clockOut = date('H:i');
            } elseif ($status === 'HADIR' && empty($clockOut)) {
                $clockOut = null;
            } elseif (!empty($clockOut) && $status === 'HADIR') {
                $status = 'SELESAI';
            }
        }

        try {
            AttendanceService::correctAttendance($attId, [
                'clock_in' => $clockIn ?: null,
                'clock_out' => $clockOut ?: null,
                'status' => $status,
                'note' => $note ?: null
            ], auth_id());
            $this->flash('success', 'Koreksi absensi berhasil disimpan.');
        } catch (Exception $e) {
            $this->flash('danger', $e->getMessage());
        }

        $this->redirect('/attendance');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $attId = (int)$id;

        try {
            $record = AttendanceRepository::findById($attId);
            if ($record) {
                AttendanceRepository::delete($attId);
                \App\Repositories\AuditRepository::log(auth_id(), 'DELETE_ATTENDANCE', 'ATTENDANCE', 'ATTENDANCE', $attId, $record, null);
                $this->flash('success', 'Catatan absensi berhasil dihapus.');
            } else {
                $this->flash('warning', 'Data absensi tidak ditemukan.');
            }
        } catch (Exception $e) {
            $this->flash('danger', 'Gagal menghapus absensi: ' . $e->getMessage());
        }

        $this->redirect('/attendance');
    }
}
