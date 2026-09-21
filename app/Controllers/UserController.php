<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;

class UserController extends Controller
{
    public function index(): void
    {
        $users = UserRepository::getAll();
        $this->view('users.index', [
            'pageTitle' => 'Manajemen Akun Pegawai',
            'users' => $users
        ]);
    }

    public function store(): void
    {
        $this->validateCsrf();

        $name = trim((string)$this->getPost('name'));
        $username = trim((string)$this->getPost('username'));
        $password = (string)$this->getPost('password');
        $role = (string)$this->getPost('role', 'CASHIER');
        $phone = trim((string)$this->getPost('phone'));
        $hourlyRate = (float)$this->getPost('hourly_rate', 15000);
        $overtimeRate = (float)$this->getPost('overtime_rate', 5000);
        $status = (string)$this->getPost('status', 'ACTIVE');

        if ($role === 'OWNER') {
            $hourlyRate = 0.00;
            $overtimeRate = 0.00;
        }

        if (empty($name) || empty($username) || empty($password)) {
            $this->flash('danger', 'Nama, username, dan password wajib diisi.');
            $this->redirect('/users');
        }

        if (!UserRepository::isUsernameUnique($username)) {
            $this->flash('danger', "Username '{$username}' sudah digunakan.");
            $this->redirect('/users');
        }

        $userId = UserRepository::create([
            'name' => $name,
            'username' => $username,
            'password' => password_hash($password, PASSWORD_BCRYPT),
            'role' => $role,
            'phone' => $phone,
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'status' => $status
        ]);

        AuditRepository::log(auth_id(), 'CREATE_USER', 'USER', 'USER', $userId, null, [
            'username' => $username,
            'role' => $role,
            'hourly_rate' => $hourlyRate
        ]);

        $this->flash('success', "Akun pegawai '{$name}' berhasil dibuat.");
        $this->redirect('/users');
    }

    public function update(string $id): void
    {
        $this->validateCsrf();
        $userId = (int)$id;

        $name = trim((string)$this->getPost('name'));
        $username = trim((string)$this->getPost('username'));
        $role = (string)$this->getPost('role', 'CASHIER');
        $phone = trim((string)$this->getPost('phone'));
        $hourlyRate = (float)$this->getPost('hourly_rate');
        $overtimeRate = (float)$this->getPost('overtime_rate', 5000);
        $status = (string)$this->getPost('status', 'ACTIVE');

        if ($role === 'OWNER') {
            $hourlyRate = 0.00;
            $overtimeRate = 0.00;
        }

        if (empty($name) || empty($username)) {
            $this->flash('danger', 'Nama dan username wajib diisi.');
            $this->redirect('/users');
        }

        if (!UserRepository::isUsernameUnique($username, $userId)) {
            $this->flash('danger', "Username '{$username}' sudah digunakan akun lain.");
            $this->redirect('/users');
        }

        $old = UserRepository::findById($userId);
        UserRepository::update($userId, [
            'name' => $name,
            'username' => $username,
            'role' => $role,
            'phone' => $phone,
            'hourly_rate' => $hourlyRate,
            'overtime_rate' => $overtimeRate,
            'status' => $status
        ]);

        AuditRepository::log(auth_id(), 'UPDATE_USER', 'USER', 'USER', $userId, $old, [
            'name' => $name,
            'role' => $role,
            'hourly_rate' => $hourlyRate,
            'status' => $status
        ]);

        $this->flash('success', "Data akun '{$name}' berhasil diperbarui.");
        $this->redirect('/users');
    }

    public function resetPassword(string $id): void
    {
        $this->validateCsrf();
        $userId = (int)$id;
        $newPassword = (string)$this->getPost('new_password');

        if (empty($newPassword) || strlen($newPassword) < 6) {
            $this->flash('danger', 'Password baru minimal 6 karakter.');
            $this->redirect('/users');
        }

        UserRepository::updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT));
        AuditRepository::log(auth_id(), 'RESET_PASSWORD', 'USER', 'USER', $userId);

        $this->flash('success', 'Password berhasil direset.');
        $this->redirect('/users');
    }

    public function delete(string $id): void
    {
        $this->validateCsrf();
        $userId = (int)$id;

        if ($userId === auth_id()) {
            $this->flash('danger', 'Anda tidak dapat menghapus akun Anda sendiri.');
            $this->redirect('/users');
        }

        $user = UserRepository::findById($userId);
        if ($user) {
            UserRepository::hardDelete($userId);
            AuditRepository::log(auth_id(), 'DELETE_USER', 'USER', 'USER', $userId, $user, ['deleted_at' => date('Y-m-d H:i:s')]);
            $this->flash('success', "Akun '{$user['name']}' berhasil dihapus dari sistem.");
        }

        $this->redirect('/users');
    }
}
