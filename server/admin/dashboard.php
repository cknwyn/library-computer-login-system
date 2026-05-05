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
$website_hits    = (int) $pdo->query("SELECT COUNT(*) FROM website_logs WHERE DATE(visited_at)=CURDATE()")->fetchColumn();

$avg_duration = (int) ($pdo->query("SELECT AVG(duration_seconds) FROM sessions WHERE status='completed' AND DATE(login_time)=CURDATE()")->fetchColumn() ?? 0);

// ── Recent active sessions ────────────────────────────────────
$active_rows = $pdo->query(
    "SELECT s.id, s.login_time, s.last_heartbeat,
            u.name, u.first_name, u.middle_name, u.last_name, u.user_id AS user_code, u.role,
            t.terminal_code, r.name AS room_name, c.name AS campus_name
     FROM   sessions s
     JOIN   users     u ON u.id = s.user_id
     JOIN   terminals t ON t.id = s.terminal_id
     LEFT JOIN rooms r ON r.id = t.room_id
     LEFT JOIN campuses c ON c.id = r.campus_id
     WHERE  s.status = 'active'
     ORDER  BY s.login_time DESC
     LIMIT  10"
)->fetchAll();

// ── Recent sessions log ───────────────────────────────────────
$recent_rows = $pdo->query(
    "SELECT s.id, s.login_time, s.logout_time, s.duration_seconds, s.status,
            u.name, u.first_name, u.middle_name, u.last_name, u.user_id AS user_code,
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

<!-- Quick Identity Search -->
<div class="card" style="margin-bottom: 24px; background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%); color: white; border: none;">
  <div class="card-body" style="padding: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div>
        <h2 style="margin: 0; font-size: 20px; font-weight: 800;">Quick Identity Check</h2>
        <p style="margin: 4px 0 0; opacity: 0.8; font-size: 13px;">Verify student or staff status and active sessions instantly.</p>
      </div>
      <div style="flex: 1; max-width: 400px; position: relative;">
        <form action="users.php" method="GET" style="margin: 0;">
          <i data-lucide="search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; width: 18px;"></i>
          <input type="text" name="q" class="form-control" placeholder="Type ID or Name..." style="padding-left: 48px; height: 48px; border-radius: 12px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.1); width: 100%;">
          <button type="submit" style="position: absolute; right: 8px; top: 8px; bottom: 8px; background: var(--primary); color: white; border: none; padding: 0 16px; border-radius: 8px; font-weight: 700; font-size: 13px; cursor: pointer;">Search</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <span class="stat-label">Active Sessions</span>
    <span class="stat-value"><?= $active_sessions ?></span>
    <span class="stat-sub">Currently logged in</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Sessions Today</span>
    <span class="stat-value"><?= $today_sessions ?></span>
    <span class="stat-sub">Since midnight</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Registered Users</span>
    <span class="stat-value"><?= $total_users ?></span>
    <span class="stat-sub">Active accounts</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Website Hits</span>
    <span class="stat-value"><?= $website_hits ?></span>
    <span class="stat-sub">Tracked today</span>
  </div>
</div>

<div class="dashboard-grid">
  <!-- Left Column: Active Sessions -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Live Session Feed</span>
      <div class="live-badge"><span class="live-dot"></span> Active</div>
    </div>
    <div class="card-body">
      <?php if (empty($active_rows)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i data-lucide="monitor-off"></i></div>
          <p>No active sessions right now.</p>
        </div>
      <?php else: ?>
        <div class="feed-list">
          <?php foreach ($active_rows as $r): ?>
          <div class="feed-item">
            <div class="feed-icon"><i data-lucide="<?= $r['role']==='staff' ? 'user-check' : 'user' ?>"></i></div>
            <div class="feed-content">
              <div class="feed-title"><?= h(format_user_name($r)) ?> <span class="td-muted" style="font-weight:400; font-size:12px">• <?= h($r['terminal_code']) ?></span></div>

              <div class="feed-meta"><?= ucfirst($r['role']) ?> • <?= h($r['room_name'] ?? 'Unassigned') ?> • In session for <?= format_duration(elapsed_since($r['login_time'])) ?></div>
            </div>
            <div class="feed-actions">
              <form method="POST" action="sessions.php" onsubmit="return confirm('Force-end this session?')">
                <input type="hidden" name="action" value="force_end">
                <input type="hidden" name="session_id" value="<?= $r['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm" title="Force End">
                  <i data-lucide="power" style="width:14px; height:14px"></i>
                </button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right Column: Recent Activity -->
  <div class="card">
    <div class="card-header">
      <span class="card-title">Recent Activity</span>
      <a href="sessions.php" class="btn btn-outline btn-sm">
        <i data-lucide="arrow-right" style="width:14px;vertical-align:middle;margin-right:2px"></i> View All
      </a>
    </div>

    <div class="card-body" style="padding: 0">
      <div class="table-wrap">
        <table style="font-size: 13px">
          <thead>
            <tr>
              <th style="padding-left: 32px">User</th>
              <th>Status</th>
              <th style="padding-right: 32px">Time</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent_rows as $r): ?>
            <tr style="border-bottom: 1px solid var(--border-light)">
              <td style="padding-left: 32px">
                <div style="font-weight:700"><?= h(format_user_name($r)) ?></div>
                <div class="td-muted mono" style="font-size:11px"><?= h($r['user_code']) ?></div>
              </td>

              <td>
                <?php
                  $badges = ['active'=>'badge-green','completed'=>'badge-gray','force_ended'=>'badge-red','abandoned'=>'badge-yellow'];
                  $b = $badges[$r['status']] ?? 'badge-gray';
                  echo '<span class="badge '.$b.'">'.ucfirst(str_replace('_',' ',$r['status'])).'</span>';
                ?>
              </td>
              <td class="td-muted" style="padding-right: 32px">
                <?= date('H:i', strtotime($r['login_time'])) ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>


<?php include __DIR__ . '/partials/footer.php'; ?>
