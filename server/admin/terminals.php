<?php
// ============================================================
// Admin — Terminals Management — /admin/terminals.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo   = db();
$admin = current_admin();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $code = trim($_POST['terminal_code'] ?? '');
        $loc  = trim($_POST['location']      ?? '');
        if ($code) {
            try {
                $pdo->prepare('INSERT INTO terminals(terminal_code,location) VALUES(:c,:l)')
                    ->execute([':c'=>$code,':l'=>$loc?:null]);
                $flash = "Terminal '{$code}' added.";
            } catch (PDOException $e) { $flash = "Terminal code '{$code}' already exists."; }
        }
    }
    if ($action === 'set_status') {
        $tid    = (int)($_POST['id']??0);
        $status = $_POST['status']??'';
        if ($tid && in_array($status,['online','offline','maintenance'],true)) {
            $pdo->prepare('UPDATE terminals SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$tid]);
            $flash = 'Terminal status updated.';
        }
    }
}

$terminals = $pdo->query(
    "SELECT t.*,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id AND s.status='active') AS active_sessions,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id) AS total_sessions
     FROM terminals t
     ORDER BY t.terminal_code"
)->fetchAll();

$page = 'terminals';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
  <div class="flash flash-success fade-in" style="margin-bottom: 32px">
    <i data-lucide="check-circle" style="width:18px"></i> <?= h($flash) ?>
  </div>
<?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:32px">
  <div>
    <h2 style="font-size: 20px; font-weight: 800; color: var(--text-primary)">Network Assets</h2>
    <p class="td-muted">Manage ecosystem access points and terminal health.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modal-add')">
    <i data-lucide="plus" style="width:18px"></i> Provision New Terminal
  </button>
</div>

<!-- Stats for Terminals -->
<div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); margin-bottom: 40px">
  <div class="stat-card" style="padding: 24px">
    <span class="stat-label">Total Assets</span>
    <span class="stat-value"><?= count($terminals) ?></span>
  </div>
  <div class="stat-card" style="padding: 24px">
    <span class="stat-label">Connected</span>
    <span class="stat-value" style="color: var(--success)">
      <?= count(array_filter($terminals, fn($t) => $t['status'] === 'online')) ?>
    </span>
  </div>
  <div class="stat-card" style="padding: 24px">
    <span class="stat-label">Under Maintenance</span>
    <span class="stat-value" style="color: var(--tertiary)">
      <?= count(array_filter($terminals, fn($t) => $t['status'] === 'maintenance')) ?>
    </span>
  </div>
</div>

<div class="terminal-grid fade-in">
  <?php foreach ($terminals as $t): ?>
  <div class="terminal-card">
    <div class="terminal-header">
      <div class="feed-icon" style="background: var(--bg-base); box-shadow: none">
        <i data-lucide="<?= $t['active_sessions'] > 0 ? 'monitor-play' : 'monitor' ?>"></i>
      </div>
      <?php
        $b=['online'=>'badge-green','offline'=>'badge-gray','maintenance'=>'badge-yellow'];
        echo '<span class="badge '.($b[$t['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($t['status']).'</span>';
      ?>
    </div>
    
    <div class="terminal-info">
      <h3><?= h($t['terminal_code']) ?></h3>
      <p><i data-lucide="map-pin" style="width:12px; height:12px; vertical-align: middle; margin-right: 4px"></i> <?= h($t['location']??'Unassigned Location') ?></p>
    </div>

    <div class="terminal-stats">
      <div class="progress-wrap">
        <div class="progress-label"><span>Session Load</span><span><?= $t['active_sessions'] > 0 ? '100%' : '0%' ?></span></div>
        <div class="progress-bar"><div class="progress-fill fill-blue" style="width: <?= $t['active_sessions'] > 0 ? '100%' : '0%' ?>"></div></div>
      </div>
      <div class="progress-wrap">
        <div class="progress-label"><span>Usage Frequency</span><span><?= min(100, $t['total_sessions'] * 5) ?>%</span></div>
        <div class="progress-bar"><div class="progress-fill fill-slate" style="width: <?= min(100, $t['total_sessions'] * 5) ?>%"></div></div>
      </div>
    </div>

    <div class="terminal-footer">
      <div style="font-size: 11px; font-weight: 700; color: var(--text-faint); text-transform: uppercase">
        Last Seen: <?= $t['last_seen'] ? date('H:i', strtotime($t['last_seen'])) : 'Never' ?>
      </div>
      <form method="POST" style="display:flex;gap:6px">
        <input type="hidden" name="action" value="set_status">
        <input type="hidden" name="id" value="<?= $t['id'] ?>">
        <select name="status" class="form-control" style="padding: 4px 8px; font-size: 11px; width: 100px" onchange="this.form.submit()">
          <option value="online"      <?= $t['status']==='online'?'selected':'' ?>>Online</option>
          <option value="offline"     <?= $t['status']==='offline'?'selected':'' ?>>Offline</option>
          <option value="maintenance" <?= $t['status']==='maintenance'?'selected':'' ?>>Maintenance</option>
        </select>
      </form>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- Add New Terminal Placeholder -->
  <div class="terminal-card" style="border: 2px dashed var(--border); background: transparent; cursor: pointer; align-items: center; justify-content: center; min-height: 240px" onclick="openModal('modal-add')">
     <div style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-faint); margin-bottom: 12px">
       <i data-lucide="plus"></i>
     </div>
     <div style="font-weight: 700; color: var(--text-muted)">Add New Node</div>
     <div style="font-size: 12px; color: var(--text-faint)">Register new hardware</div>
  </div>
</div>

<!-- Add Terminal Modal -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Provision Terminal</span>
      <button class="btn-close" onclick="closeModal('modal-add')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 24px">Assign a unique identifier and collegiate location for the new access point.</p>
        <div class="form-group">
          <label class="form-label">Terminal ID</label>
          <input name="terminal_code" class="form-control" placeholder="e.g. TERM-15-HSLIB" required>
        </div>
        <div class="form-group">
          <label class="form-label">Location</label>
          <input name="location" class="form-control" placeholder="Select or type location...">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Terminal</button>
      </div>
    </form>
  </div>
</div>


<?php include __DIR__ . '/partials/footer.php'; ?>
