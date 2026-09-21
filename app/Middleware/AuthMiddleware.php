<?php

namespace App\Middleware;

use App\Core\Router;

class AuthMiddleware
{
    public static function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user']) || empty($_SESSION['user']['id'])) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Silakan login terlebih dahulu.']);
                exit;
            }

            header('Location: ' . Router::url('/login'));
            exit;
        }

        // Session timeout check (2 jam = 7200 detik)
        $timeout = 7200;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            session_unset();
            session_destroy();
            session_start();
            $_SESSION['flash'] = [
                'type' => 'warning',
                'message' => 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.'
            ];
            header('Location: ' . Router::url('/login'));
            exit;
        }

        $_SESSION['last_activity'] = time();

        // Realtime active session tracking
        if (!empty($_SESSION['user']['id'])) {
            \App\Services\SessionTracker::touch((int)$_SESSION['user']['id'], session_id());
        }
    }

    public static function verifyCsrf(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!verify_csrf($token)) {
                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    http_response_code(403);
                    header('Content-Type: application/json');
                    echo json_encode(['status' => false, 'message' => 'CSRF Token tidak valid atau kedaluwarsa.']);
                    exit;
                }

                $_SESSION['flash'] = [
                    'type' => 'danger',
                    'message' => 'Token keamanan (CSRF) tidak valid. Silakan coba kembali.'
                ];
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? Router::url('/')));
                exit;
            }
        }
    }
}
