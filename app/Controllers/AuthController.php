<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (auth_check()) {
            if (is_owner()) {
                $this->redirect('/dashboard');
            } else {
                $this->redirect('/pos');
            }
        }

        $this->view('auth.login', ['pageTitle' => 'Masuk'], null);
    }

    public function login(): void
    {
        $this->validateCsrf();

        $username = trim((string)$this->getPost('username'));
        $password = (string)$this->getPost('password');

        if (empty($username) || empty($password)) {
            $this->flash('danger', 'Username dan password wajib diisi.');
            $this->redirect('/login');
        }

        $result = AuthService::attempt($username, $password);

        if (!$result['status']) {
            $this->flash('danger', $result['message']);
            $this->redirect('/login');
        }

        $user = $result['user'];
        $this->flash('success', 'Selamat datang, ' . $user['name'] . '!');

        if ($user['role'] === 'OWNER') {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/pos');
        }
    }

    public function logout(): void
    {
        $this->validateCsrf();
        AuthService::logout();
        $this->flash('info', 'Anda telah berhasil keluar.');
        $this->redirect('/login');
    }
}
