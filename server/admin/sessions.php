<?php
// ============================================================
// Admin — Sessions Monitor & History — /admin/sessions.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();
cleanup_abandoned_sessions();

$pdo   = db();
$admin = current_admin();
$flash = '';

// ── Force-end a session ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'force_end') {
    $sid = (int) ($_POST['session_id'] ?? 0);
    if ($sid) {
        $row = $pdo->prepare("SELECT * FROM sessions WHERE id=:id AND status='active'")->execute([':id'=>$sid]) ?
            $pdo->query("SELECT * FROM sessions WHERE id={$sid}")->fetch() : null;
        $pdo->prepare(
            "UPDATE sessions
             SET status='force_ended', logout_time=NOW(),
                 duration_seconds=TIMESTAMPDIFF(SECOND,login_time,NOW())
             WHERE id=:id AND status='active'"
        )->execute([':id'=>$sid]);
        $pdo->prepare("UPDATE terminals SET status='offline' WHERE id=(SELECT terminal_id FROM sessions WHERE id=:id)")
            ->execute([':id'=>$sid]);
        log_activity('ADMIN_FORCE_END', "Force-ended session #{$sid}", null, $admin['id']);
        $flash = "Session #{$sid} has been force-ended.";
    }
}

// ── Filters ───────────────────────────────────────────────────
$date_from  = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$date_to    = $_GET['date_to']   ?? date('Y-m-d');
$status_f   = $_GET['status']    ?? '';
$search     = trim($_GET['q']    ?? '');

$where = ["DATE(s.login_time) BETWEEN :df AND :dt"];
$params = [':df'=>$date_from, ':dt'=>$date_to];
if ($status_f) { $where[] = 's.status=:st'; $params[':st']=$status_f; }
if ($search)   { $where[] = '(u.user_id LIKE :q OR u.name LIKE :q OR t.terminal_code LIKE :q)'; $params[':q']="%{$search}%"; }

$sql = "SELECT s.*,
               u.name, u.first_name, u.middle_name, u.last_name, u.user_id AS user_code, u.role,
               t.terminal_code, r.name AS room_name, c.name AS campus_name

        FROM sessions s
        JOIN users     u ON u.id = s.user_id
        JOIN terminals t ON t.id = s.terminal_id
        LEFT JOIN rooms r ON r.id = t.room_id
        LEFT JOIN campuses c ON c.id = r.campus_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY s.login_time DESC
        LIMIT 200";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

$active_count = (int) $pdo->query("SELECT COUNT(*) FROM sessions WHERE status='active'")->fetchColumn();

$page = 'sessions';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
  <div class="flash flash-success"><?= h($flash) ?></div>
<?php endif; ?>

<!-- Stats row -->
<div class="stats-grid" style="margin-bottom:32px">
  <div class="stat-card">
    <span class="stat-label">Active Now</span>
    <span class="stat-value" style="color: var(--success)"><?= $active_count ?></span>
    <span class="stat-sub">Live sessions</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Total in Range</span>
    <span class="stat-value"><?= count($sessions) ?></span>
    <span class="stat-sub">Historical data</span>
  </div>
</div>

<!-- Filter bar -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Access Log History</span>
    <div style="display: flex; gap: 8px">
       <span class="badge badge-gray"><?= count($sessions) ?> records</span>
    </div>
  </div>
  <div class="card-body" style="padding-bottom:12px; border-bottom: 1px solid var(--border-light)">
    <form method="GET" class="filter-bar" style="margin-bottom:0">
      <div class="search-wrap">
        <span class="search-icon"><i data-lucide="search" style="width:18px"></i></span>
        <input class="form-control search-input" name="q" placeholder="Identity, name, or terminal..." value="<?= h($search) ?>">
      </div>
      <input type="date" name="date_from" class="form-control" style="max-width:150px" value="<?= h($date_from) ?>">
      <input type="date" name="date_to"   class="form-control" style="max-width:150px" value="<?= h($date_to) ?>">
      <select name="status" class="form-control" style="max-width:140px">
        <option value="">All States</option>
        <option value="active"      <?= $status_f==='active'?'selected':'' ?>>Active</option>
        <option value="completed"   <?= $status_f==='completed'?'selected':'' ?>>Completed</option>
        <option value="force_ended" <?= $status_f==='force_ended'?'selected':'' ?>>Terminated</option>
        <option value="abandoned"   <?= $status_f==='abandoned'?'selected':'' ?>>Abandoned</option>
      </select>
      <button type="submit" class="btn btn-primary">Sync View</button>
    </form>
  </div>

  <?php if (empty($sessions)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="ghost"></i></div>
      <p>No activity records match your criteria.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table id="sessions-table">
      <thead>
        <tr><th>Identity</th><th>Classification</th><th>Access Point</th><th>Timeline</th><th>Duration</th><th>Status</th><th>Operation</th></tr>
      </thead>
      <tbody>
        <?php foreach ($sessions as $s): ?>
        <?php $status_badges = ['active'=>'badge-green','completed'=>'badge-gray','force_ended'=>'badge-red','abandoned'=>'badge-yellow']; ?>
        <tr>
          <td>
            <div style="font-weight:700"><?= h(format_user_name($s)) ?></div>
            <div class="mono td-muted" style="font-size:11px"><?= h($s['user_code']) ?></div>
          </td>

          <td><span class="badge <?= $s['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= ucfirst($s['role']) ?></span></td>
          <td>
             <div style="font-weight:700"><?= h($s['terminal_code']) ?></div>
             <div class="td-muted" style="font-size:11px"><?= h($s['room_name'] ? "{$s['room_name']} ({$s['campus_name']})" : 'Unspecified') ?></div>
          </td>
          <td>
             <div style="font-size:13px; font-weight: 500"><?= date('M d, H:i', strtotime($s['login_time'])) ?></div>
             <div class="td-muted" style="font-size:11px"><?= $s['logout_time'] ? 'End: '.date('H:i', strtotime($s['logout_time'])) : 'Active Now' ?></div>
          </td>
          <td style="font-weight: 600">
            <?php if ($s['status']==='active'): ?>
              <span data-login-time="<?= h($s['login_time']) ?>"><?= format_duration(elapsed_since($s['login_time'])) ?></span>
            <?php else: ?>
              <?= $s['duration_seconds'] !== null ? format_duration((int)$s['duration_seconds']) : '—' ?>
            <?php endif; ?>
          </td>
          <td><span class="badge <?= $status_badges[$s['status']]??'badge-gray' ?>"><span class="badge-dot"></span><?= ucfirst(str_replace('_',' ',$s['status'])) ?></span></td>
          <td>
            <?php if ($s['status']==='active'): ?>
            <form method="POST" onsubmit="return confirm('Force-terminate session #<?= $s['id'] ?>?')">
              <input type="hidden" name="action" value="force_end">
              <input type="hidden" name="session_id" value="<?= $s['id'] ?>">
              <button class="btn btn-secondary btn-sm" title="Force Terminate"><i data-lucide="power" style="width:14px"></i></button>
            </form>
            <?php else: ?>
              <span class="td-muted">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>


<script>autoRefresh(30);</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
