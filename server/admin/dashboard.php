<?php
// ============================================================
// Admin Dashboard — /admin/dashboard.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
cleanup_abandoned_sessions();

$pdo = db();
$admin = current_admin();

// ── Stats ─────────────────────────────────────────────────────
$active_sessions = (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE status='active'")->fetchColumn();
$today_sessions  = (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE DATE(login_time)=CURDATE()")->fetchColumn();
$total_users     = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE status='active'")->fetchColumn();
$pending_requests= (int) $pdo->query("SELECT COUNT(*) FROM app_requests WHERE status='pending'")->fetchColumn();

$avg_duration = (int) ($pdo->query("SELECT AVG(duration_seconds) FROM sessions WHERE status='completed' AND DATE(login_time)=CURDATE()")->fetchColumn() ?? 0);

// ── Recent active sessions ────────────────────────────────────
$active_rows = $pdo->query(
    "SELECT s.id, s.login_time, s.last_heartbeat,
            u.name AS user_name, u.user_id AS user_code, u.role,
            t.terminal_code, t.location
     FROM   sessions s
     JOIN   users     u ON u.id = s.user_id
     JOIN   terminals t ON t.id = s.terminal_id
     WHERE  s.status = 'active'
     ORDER  BY s.login_time DESC
     LIMIT  10"
)->fetchAll();

// ── Recent sessions log ───────────────────────────────────────
$recent_rows = $pdo->query(
    "SELECT s.id, s.login_time, s.logout_time, s.duration_seconds, s.status,
            u.name AS user_name, u.user_id AS user_code,
            t.terminal_code
     FROM   sessions s
     JOIN   users     u ON u.id = s.user_id
     JOIN   terminals t ON t.id = s.terminal_id
     ORDER  BY s.login_time DESC
     LIMIT  8"
)->fetchAll();

$page = 'dashboard';
include __DIR__ . '/partials/header.php';
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card green">
    <span class="stat-label">Active Sessions</span>
    <span class="stat-value"><?= $active_sessions ?></span>
    <span class="stat-sub">Currently logged in</span>
  </div>
  <div class="stat-card gold">
    <span class="stat-label">Sessions Today</span>
    <span class="stat-value"><?= $today_sessions ?></span>
    <span class="stat-sub">Since midnight</span>
  </div>
  <div class="stat-card blue">
    <span class="stat-label">Registered Users</span>
    <span class="stat-value"><?= $total_users ?></span>
    <span class="stat-sub">Active accounts</span>
  </div>
  <div class="stat-card <?= $pending_requests > 0 ? 'red' : 'gray' ?>" style="<?= $pending_requests === 0 ? '--danger:var(--text-muted)' : '' ?>">
    <span class="stat-label">Pending Requests</span>
    <span class="stat-value" style="<?= $pending_requests === 0 ? 'color:var(--text-muted)' : '' ?>"><?= $pending_requests ?></span>
    <span class="stat-sub">App install/uninstall</span>
  </div>
</div>

<!-- Active Sessions -->
<div class="card">
  <div class="card-header">
    <span class="card-title">🟢 Active Sessions</span>
    <div class="live-badge"><span class="live-dot"></span> Live</div>
  </div>
  <?php if (empty($active_rows)): ?>
    <div class="empty-state">
      <div class="empty-icon">🖥️</div>
      <p>No active sessions right now.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th><th>User</th><th>Role</th><th>Terminal</th>
          <th>Location</th><th>Duration</th><th>Last Seen</th><th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($active_rows as $r): ?>
        <tr>
          <td class="td-muted mono"><?= $r['id'] ?></td>
          <td>
            <div style="font-weight:600"><?= h($r['user_name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($r['user_code']) ?></div>
          </td>
          <td>
            <span class="badge <?= $r['role']==='staff' ? 'badge-blue' : 'badge-gold' ?>">
              <?= ucfirst($r['role']) ?>
            </span>
          </td>
          <td class="mono"><?= h($r['terminal_code']) ?></td>
          <td class="td-muted"><?= h($r['location'] ?? '—') ?></td>
          <td data-login-time="<?= h($r['login_time']) ?>">
            <?= format_duration(elapsed_since($r['login_time'])) ?>
          </td>
          <td class="td-muted" style="font-size:11px">
            <?= $r['last_heartbeat'] ? date('H:i:s', strtotime($r['last_heartbeat'])) : '—' ?>
          </td>
          <td>
            <form method="POST" action="sessions.php" onsubmit="return confirm('Force-end this session?')">
              <input type="hidden" name="action" value="force_end">
              <input type="hidden" name="session_id" value="<?= $r['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Force End</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Recent Sessions -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📋 Recent Sessions</span>
    <a href="sessions.php" class="btn btn-outline btn-sm">View All</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>#</th><th>User</th><th>Terminal</th><th>Login</th><th>Logout</th><th>Duration</th><th>Status</th></tr>
      </thead>
      <tbody>
        <?php foreach ($recent_rows as $r): ?>
        <tr>
          <td class="td-muted mono"><?= $r['id'] ?></td>
          <td>
            <div style="font-weight:600"><?= h($r['user_name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($r['user_code']) ?></div>
          </td>
          <td class="mono"><?= h($r['terminal_code']) ?></td>
          <td class="td-muted"><?= date('M d, H:i', strtotime($r['login_time'])) ?></td>
          <td class="td-muted"><?= $r['logout_time'] ? date('H:i', strtotime($r['logout_time'])) : '—' ?></td>
          <td><?= $r['duration_seconds'] !== null ? format_duration((int)$r['duration_seconds']) : '<span class="td-muted">Active</span>' ?></td>
          <td><?php
            $badges = ['active'=>'badge-green','completed'=>'badge-gray','force_ended'=>'badge-red','abandoned'=>'badge-yellow'];
            $b = $badges[$r['status']] ?? 'badge-gray';
            echo '<span class="badge '.$b.'"><span class="badge-dot"></span>'.ucfirst(str_replace('_',' ',$r['status'])).'</span>';
          ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
