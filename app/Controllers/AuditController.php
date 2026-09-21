<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;
use App\Services\SessionTracker;

class AuditController extends Controller
{
    public function index(): void
    {
        $module = $this->getQuery('module');
        $action = $this->getQuery('action');
        $userId = $this->getQuery('user_id') ? (int)$this->getQuery('user_id') : null;
        $search = $this->getQuery('q');

        $logs = AuditRepository::filter($module, $action, $userId, $search, 250);
        $activeSessions = SessionTracker::getActiveSessions(30);
        $users = UserRepository::getAll();

        $this->view('audit.index', [
            'pageTitle' => 'Audit Log & Sesi Akun Realtime',
            'logs' => $logs,
            'activeSessions' => $activeSessions,
            'users' => $users,
            'module' => $module,
            'action' => $action,
            'userId' => $userId,
            'search' => $search
        ]);
    }

    /**
     * API for Live polling of active sessions without page reload
     */
    public function liveSessions(): void
    {
        $activeSessions = SessionTracker::getActiveSessions(30);
        $this->json([
            'status' => true,
            'count' => count($activeSessions),
            'sessions' => $activeSessions,
            'server_time' => date('d/m/Y H:i:s')
        ]);
    }

    /**
     * Terminate / Force Logout a user session
     */
    public function killSession(): void
    {
        $this->validateCsrf();
        $sessionId = (string)$this->getPost('session_id');

        if (empty($sessionId)) {
            $this->flash('danger', 'ID sesi tidak valid.');
            $this->redirect('/audit');
        }

        $success = SessionTracker::killSession($sessionId, auth_id());
        if ($success) {
            $this->flash('success', 'Sesi akun pengguna berhasil diputus secara paksa.');
        } else {
            $this->flash('danger', 'Gagal memutus sesi pengguna.');
        }

        $this->redirect('/audit');
    }
}
