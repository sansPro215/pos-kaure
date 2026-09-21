<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="bi bi-shield-check text-wk-primary me-2"></i>Audit Log & Keamanan Akses Realtime
            </h4>
            <p class="text-muted small mb-0">
                Monitoring IP login, perangkat pengguna, dan pantauan akun yang sedang aktif digunakan secara realtime di server/hosting
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2 d-flex align-items-center gap-2">
                <span class="spinner-grow spinner-grow-sm text-success" style="width: 8px; height: 8px;" role="status"></span>
                Live Tracker Aktif
            </span>
            <button onclick="location.reload()" class="btn btn-outline-secondary btn-sm px-3 py-2 shadow-sm" title="Refresh Halaman">
                <i class="bi bi-arrow-clockwise me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-success">
                <span class="text-muted small fw-semibold">Akun Online Realtime</span>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h3 class="fw-bold text-success mb-0" id="liveOnlineCount"><?= count($activeSessions) ?></h3>
                    <i class="bi bi-broadcast fs-3 text-success opacity-75"></i>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Sesi aktif 30 menit terakhir</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-primary">
                <span class="text-muted small fw-semibold">IP Anda Terdeteksi</span>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h5 class="fw-bold font-monospace text-primary mb-0" style="word-break: break-all;"><?= e(get_client_ip()) ?></h5>
                    <i class="bi bi-globe2 fs-3 text-primary opacity-75"></i>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">(Kompatibel Cloudflare / Proxy)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-warning">
                <span class="text-muted small fw-semibold">Perangkat Terdeteksi</span>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h6 class="fw-bold text-dark mb-0 text-truncate" title="<?= e(get_client_device()) ?>">
                        <?= e(get_client_device()) ?>
                    </h6>
                    <i class="bi bi-<?= get_device_type() === 'mobile' ? 'phone' : 'laptop' ?> fs-3 text-warning opacity-75"></i>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">User-Agent klien</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="wk-card p-3 shadow-sm border-start border-4 border-secondary">
                <span class="text-muted small fw-semibold">Total Log Aktivitas</span>
                <div class="d-flex align-items-center justify-content-between mt-1">
                    <h3 class="fw-bold text-secondary mb-0"><?= count($logs) ?></h3>
                    <i class="bi bi-journal-text fs-3 text-secondary opacity-75"></i>
                </div>
                <small class="text-muted" style="font-size: 0.75rem;">Aktivitas termutakhir</small>
            </div>
        </div>
    </div>

    <!-- Realtime Active Accounts Section -->
    <div class="wk-card p-3 p-md-4 shadow-sm mb-4 border-top border-3 border-success">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <span class="p-1 bg-success rounded-circle d-inline-block" style="width: 10px; height: 10px;"></span>
                    Akun yang Sedang Digunakan (Sesi Realtime)
                </h6>
                <small class="text-muted">
                    Daftar akun kasir & owner yang sedang login dan beraktivitas saat ini beserta alamat IP dan perangkatnya
                </small>
            </div>
            <span class="badge bg-light text-dark border small" id="livePollingTimer">
                <i class="bi bi-clock-history me-1"></i> Auto-refresh: <span id="pollCountdown">12</span>s
            </span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small" id="activeSessionsTable">
                <thead class="table-light">
                    <tr>
                        <th>Status</th>
                        <th>Akun Pengguna</th>
                        <th>Role</th>
                        <th>IP Address Login</th>
                        <th>Perangkat & Browser</th>
                        <th>Halaman Diakses</th>
                        <th>Aktivitas Terakhir</th>
                        <th class="text-center" style="width: 110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody id="activeSessionsBody">
                    <?php if (empty($activeSessions)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-person-x fs-2 d-block mb-1 opacity-50"></i>
                                Belum ada sesi akun aktif yang terdeteksi.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($activeSessions as $s): ?>
                            <?php 
                                $isCurrentSession = ($s['id'] === session_id());
                                $badgeClass = ($s['online_status'] === 'ONLINE') ? 'bg-success' : 'bg-warning text-dark';
                                $devType = get_device_type($s['user_agent'] ?? '');
                                $devIcon = $devType === 'mobile' ? 'bi-phone' : ($devType === 'tablet' ? 'bi-tablet' : 'bi-laptop');
                            ?>
                            <tr id="session-row-<?= e($s['id']) ?>">
                                <td>
                                    <span class="badge <?= $badgeClass ?> d-inline-flex align-items-center gap-1">
                                        <span class="p-1 bg-white rounded-circle d-inline-block" style="width: 6px; height: 6px;"></span>
                                        <?= e($s['status_label']) ?>
                                    </span>
                                    <?php if ($isCurrentSession): ?>
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-wk-primary"><?= e($s['user_name']) ?></div>
                                    <small class="text-muted">@<?= e($s['username']) ?></small>
                                </td>
                                <td>
                                    <span class="badge <?= $s['role'] === 'OWNER' ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-info-subtle text-info-emphasis border border-info-subtle' ?>">
                                        <?= e($s['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="font-monospace fw-semibold text-dark bg-light px-2 py-1 rounded border">
                                        <i class="bi bi-geo-alt me-1 text-muted"></i><?= e($s['ip_address']) ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="bi <?= $devIcon ?> me-1 text-muted"></i>
                                    <span><?= e($s['device_info'] ?: 'Perangkat Standar') ?></span>
                                </td>
                                <td>
                                    <code class="text-truncate d-inline-block" style="max-width: 160px;" title="<?= e($s['current_url']) ?>">
                                        <?= e($s['current_url'] ?: '/') ?>
                                    </code>
                                </td>
                                <td class="text-muted text-nowrap">
                                    <i class="bi bi-stopwatch me-1"></i><?= time_ago_id($s['last_activity_at']) ?>
                                </td>
                                <td class="text-center">
                                    <?php if (!$isCurrentSession): ?>
                                        <form action="<?= url('/audit/sessions/kill') ?>" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin memutuskan sesi akun <?= addslashes(e($s['username'])) ?> secara paksa?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="session_id" value="<?= e($s['id']) ?>">
                                            <button type="submit" class="btn btn-xs btn-outline-danger p-1 px-2" title="Putus Sesi (Force Logout)">
                                                <i class="bi bi-box-arrow-right me-1"></i> Putus
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Filter Bar for Audit Logs -->
    <div class="wk-card p-3 mb-3 shadow-sm">
        <form action="<?= url('/audit') ?>" method="GET" class="row g-2 align-items-center">
            <div class="col-12 col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari IP / Nama / Aksi..." value="<?= e($search ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">Semua Akun</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($userId == $u['id']) ? 'selected' : '' ?>>
                            <?= e($u['name']) ?> (<?= e($u['role']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <input type="text" name="module" class="form-control form-control-sm" placeholder="Modul (AUTH, POS...)" value="<?= e($module ?? '') ?>">
            </div>
            <div class="col-6 col-md-2">
                <input type="text" name="action" class="form-control form-control-sm" placeholder="Aksi (LOGIN, SALE...)" value="<?= e($action ?? '') ?>">
            </div>
            <div class="col-6 col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-wk-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> Terapkan Filter
                </button>
                <a href="<?= url('/audit') ?>" class="btn btn-outline-secondary btn-sm" title="Reset Filter">
                    <i class="bi bi-arrow-clockwise"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="wk-card p-3 p-md-4 shadow-sm">
        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
            <div>
                <h6 class="fw-bold m-0"><i class="bi bi-clock-history me-2 text-wk-primary"></i>Riwayat Kronologis Audit Trail</h6>
                <small class="text-muted">Menampilkan riwayat rekam jejak aktivitas, IP address, dan timestamp</small>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small datatable">
                <thead class="table-light">
                    <tr>
                        <th>Waktu & Tanggal</th>
                        <th>Akun Pengguna</th>
                        <th>Aksi</th>
                        <th class="text-center">Modul</th>
                        <th>IP Address</th>
                        <th class="text-center" style="width: 100px;">Detail Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                        <?php 
                            $actUpper = strtoupper($l['action']);
                            $badgeColor = 'secondary';
                            if (strpos($actUpper, 'LOGIN') !== false) $badgeColor = 'success';
                            elseif (strpos($actUpper, 'LOGOUT') !== false) $badgeColor = 'dark';
                            elseif (strpos($actUpper, 'CREATE') !== false || strpos($actUpper, 'SALE') !== false) $badgeColor = 'primary';
                            elseif (strpos($actUpper, 'UPDATE') !== false || strpos($actUpper, 'ADJUST') !== false) $badgeColor = 'info text-dark';
                            elseif (strpos($actUpper, 'DELETE') !== false || strpos($actUpper, 'VOID') !== false || strpos($actUpper, 'REFUND') !== false) $badgeColor = 'danger';
                            
                            $lDev = get_client_device($l['user_agent'] ?? '');
                            $lType = get_device_type($l['user_agent'] ?? '');
                            $lIcon = $lType === 'mobile' ? 'bi-phone' : 'bi-laptop';
                        ?>
                        <tr>
                            <td class="text-nowrap text-muted" data-order="<?= strtotime($l['created_at']) ?>">
                                <div class="fw-semibold text-dark"><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></div>
                                <small class="text-muted"><?= time_ago_id($l['created_at']) ?></small>
                            </td>
                            <td>
                                <div class="fw-bold text-wk-primary"><?= e($l['user_name'] ?? 'Sistem') ?></div>
                                <small class="text-muted">@<?= e($l['username'] ?? 'system') ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $badgeColor ?>-subtle text-<?= $badgeColor ?> border border-<?= $badgeColor ?>-subtle font-monospace px-2 py-1">
                                    <?= e($l['action']) ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-light text-secondary border"><?= e($l['module']) ?></span>
                            </td>
                            <td>
                                <div class="font-monospace fw-semibold" style="font-size: 0.85rem;">
                                    <i class="bi bi-geo-alt text-muted me-1"></i><?= e($l['ip_address']) ?>
                                </div>
                                <small class="text-muted text-truncate d-inline-block" style="max-width: 180px;" title="<?= e($l['user_agent']) ?>">
                                    <i class="bi <?= $lIcon ?> me-1"></i><?= e($lDev) ?>
                                </small>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($l['old_values_json']) || !empty($l['new_values_json'])): ?>
                                    <button class="btn btn-sm btn-outline-secondary p-1 px-2 shadow-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#auditDataModal<?= $l['id'] ?>"
                                            title="Lihat Perubahan Snapshot">
                                        <i class="bi bi-code-square me-1"></i> Detail
                                    </button>

                                    <!-- Audit JSON Modal -->
                                    <div class="modal fade" id="auditDataModal<?= $l['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                            <div class="modal-content border-0 shadow">
                                                <div class="modal-header border-bottom py-3">
                                                    <h5 class="modal-title fw-bold">
                                                        <i class="bi bi-shield-check text-wk-primary me-2"></i>Detail Audit: <?= e($l['action']) ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4 text-start">
                                                    <div class="p-3 bg-light rounded border mb-3 small">
                                                        <div class="row g-2">
                                                            <div class="col-6"><strong>Waktu:</strong> <?= date('d F Y, H:i:s', strtotime($l['created_at'])) ?> WIB</div>
                                                            <div class="col-6"><strong>Pengguna:</strong> <?= e($l['user_name'] ?? 'Sistem') ?> (@<?= e($l['username'] ?? 'system') ?>)</div>
                                                            <div class="col-6"><strong>IP Address:</strong> <span class="font-monospace"><?= e($l['ip_address']) ?></span></div>
                                                            <div class="col-6"><strong>Modul:</strong> <?= e($l['module']) ?></div>
                                                            <div class="col-12 text-break"><strong>User Agent:</strong> <?= e($l['user_agent']) ?></div>
                                                        </div>
                                                    </div>

                                                    <div class="row g-3">
                                                        <?php if (!empty($l['old_values_json'])): ?>
                                                            <div class="col-12 col-md-6">
                                                                <label class="fw-bold small text-danger mb-1"><i class="bi bi-dash-circle me-1"></i>Data Sebelum (Old):</label>
                                                                <pre class="bg-body-tertiary p-3 rounded small border overflow-auto" style="max-height: 260px; font-size: 0.8rem;"><?= e(json_encode(json_decode($l['old_values_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="col-12 <?= !empty($l['old_values_json']) ? 'col-md-6' : '' ?>">
                                                            <label class="fw-bold small text-success mb-1"><i class="bi bi-plus-circle me-1"></i>Data Sesudah (New / Snapshot):</label>
                                                            <pre class="bg-body-tertiary p-3 rounded small border overflow-auto" style="max-height: 260px; font-size: 0.8rem;"><?= e(json_encode(json_decode($l['new_values_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></pre>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-top p-3">
                                                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let countdown = 12;
    const countdownEl = document.getElementById('pollCountdown');
    const onlineCountEl = document.getElementById('liveOnlineCount');
    const tbodyEl = document.getElementById('activeSessionsBody');

    // Live polling countdown
    setInterval(function() {
        countdown--;
        if (countdown <= 0) {
            countdown = 12;
            fetchLiveSessions();
        }
        if (countdownEl) countdownEl.innerText = countdown;
    }, 1000);

    function fetchLiveSessions() {
        fetch('<?= url('/audit/live-sessions') ?>', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.status && Array.isArray(data.sessions)) {
                if (onlineCountEl) onlineCountEl.innerText = data.count;
                renderSessionsTable(data.sessions);
            }
        })
        .catch(err => console.debug('Polling error:', err));
    }

    function renderSessionsTable(sessions) {
        if (!tbodyEl) return;
        if (sessions.length === 0) {
            tbodyEl.innerHTML = `<tr><td colspan="8" class="text-center py-4 text-muted"><i class="bi bi-person-x fs-2 d-block mb-1 opacity-50"></i>Belum ada sesi akun aktif yang terdeteksi.</td></tr>`;
            return;
        }

        let html = '';
        sessions.forEach(s => {
            const isSelf = (s.id === '<?= session_id() ?>');
            const badgeClass = (s.online_status === 'ONLINE') ? 'bg-success' : 'bg-warning text-dark';
            const roleBadge = (s.role === 'OWNER') ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-info-subtle text-info-emphasis border border-info-subtle';
            
            html += `
                <tr id="session-row-${escapeHtml(s.id)}">
                    <td>
                        <span class="badge ${badgeClass} d-inline-flex align-items-center gap-1">
                            <span class="p-1 bg-white rounded-circle d-inline-block" style="width: 6px; height: 6px;"></span>
                            ${escapeHtml(s.status_label)}
                        </span>
                        ${isSelf ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">Anda</span>' : ''}
                    </td>
                    <td>
                        <div class="fw-bold text-wk-primary">${escapeHtml(s.user_name)}</div>
                        <small class="text-muted">@${escapeHtml(s.username)}</small>
                    </td>
                    <td>
                        <span class="badge ${roleBadge}">${escapeHtml(s.role)}</span>
                    </td>
                    <td>
                        <span class="font-monospace fw-semibold text-dark bg-light px-2 py-1 rounded border">
                            <i class="bi bi-geo-alt me-1 text-muted"></i>${escapeHtml(s.ip_address)}
                        </span>
                    </td>
                    <td>
                        <span>${escapeHtml(s.device_info || 'Perangkat Standar')}</span>
                    </td>
                    <td>
                        <code class="text-truncate d-inline-block" style="max-width: 160px;" title="${escapeHtml(s.current_url || '/')}">
                            ${escapeHtml(s.current_url || '/')}
                        </code>
                    </td>
                    <td class="text-muted text-nowrap">
                        <i class="bi bi-stopwatch me-1"></i>${escapeHtml(s.last_activity_at)}
                    </td>
                    <td class="text-center">
                        ${!isSelf ? `
                            <form action="<?= url('/audit/sessions/kill') ?>" method="POST" class="d-inline" onsubmit="return confirm('Putus sesi akun ${escapeHtml(s.username)}?');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="session_id" value="${escapeHtml(s.id)}">
                                <button type="submit" class="btn btn-xs btn-outline-danger p-1 px-2" title="Putus Sesi (Force Logout)">
                                    <i class="bi bi-box-arrow-right me-1"></i> Putus
                                </button>
                            </form>
                        ` : '<span class="text-muted small">—</span>'}
                    </td>
                </tr>
            `;
        });
        tbodyEl.innerHTML = html;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }
});
</script>
