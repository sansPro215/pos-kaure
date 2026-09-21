<?php

namespace App\Middleware;

use App\Core\Router;

class RoleMiddleware
{
    public static function handle(string $requiredRole): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $userRole = $_SESSION['user']['role'] ?? null;
        $allowedRoles = array_map('trim', explode(',', $requiredRole));

        if (!in_array($userRole, $allowedRoles, true)) {
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'message' => 'Akses ditolak. Anda tidak memiliki izin.']);
                exit;
            }

            $_SESSION['flash'] = [
                'type' => 'danger',
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk membuka halaman tersebut.'
            ];

            // If cashier tries to access owner route, redirect to POS
            if ($userRole === 'CASHIER') {
                header('Location: ' . Router::url('/pos'));
            } else {
                header('Location: ' . Router::url('/dashboard'));
            }
            exit;
        }
    }
}
