<?php

namespace App\Services;

use App\Repositories\UserRepository;
use App\Repositories\AuditRepository;

class AuthService
{
    public static function attempt(string $username, string $password): array
    {
        $user = UserRepository::findByUsername($username);

        if (!$user) {
            return ['status' => false, 'message' => 'Username atau password salah.'];
        }

        if ($user['status'] !== 'ACTIVE') {
            return ['status' => false, 'message' => 'Akun Anda sedang dinonaktifkan. Hubungi Owner.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['status' => false, 'message' => 'Username atau password salah.'];
        }

        // Regenerate session ID to prevent session fixation
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'hourly_rate' => (float)$user['hourly_rate'],
            'overtime_rate' => (float)$user['overtime_rate'],
            'login_at' => date('Y-m-d H:i:s'),
        ];
        $_SESSION['last_activity'] = time();

        UserRepository::updateLastLogin((int)$user['id']);

        // Track active session in realtime
        \App\Services\SessionTracker::touch((int)$user['id'], session_id());

        // Log audit trail
        $clientIp = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $clientDevice = function_exists('get_client_device') ? get_client_device() : 'Unknown';

        AuditRepository::log(
            (int)$user['id'],
            'LOGIN',
            'AUTH',
            'USER',
            (int)$user['id'],
            null,
            [
                'username' => $user['username'],
                'role' => $user['role'],
                'ip_address' => $clientIp,
                'device' => $clientDevice
            ]
        );

        return ['status' => true, 'user' => $_SESSION['user']];
    }

    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['user']['id'])) {
            \App\Services\SessionTracker::destroy(session_id());

            $clientIp = function_exists('get_client_ip') ? get_client_ip() : ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $clientDevice = function_exists('get_client_device') ? get_client_device() : 'Unknown';

            AuditRepository::log(
                (int)$_SESSION['user']['id'],
                'LOGOUT',
                'AUTH',
                'USER',
                (int)$_SESSION['user']['id'],
                null,
                [
                    'username' => $_SESSION['user']['username'],
                    'ip_address' => $clientIp,
                    'device' => $clientDevice
                ]
            );
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
