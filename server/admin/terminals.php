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

<?php if ($flash): ?><div class="flash flash-success"><?= h($flash) ?></div><?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <button class="btn btn-primary" onclick="openModal('modal-add')">+ Add Terminal</button>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">💻 Terminals
      <span class="badge badge-gray" style="margin-left:8px"><?= count($terminals) ?></span>
    </span>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Code</th><th>Location</th><th>Status</th><th>Last Seen</th><th>Sessions</th><th>Change Status</th></tr></thead>
      <tbody>
        <?php foreach ($terminals as $t): ?>
        <tr>
          <td class="mono" style="font-weight:700"><?= h($t['terminal_code']) ?></td>
          <td class="td-muted"><?= h($t['location']??'—') ?></td>
          <td><?php
            $b=['online'=>'badge-green','offline'=>'badge-gray','maintenance'=>'badge-yellow'];
            echo '<span class="badge '.($b[$t['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($t['status']).'</span>';
            if ($t['active_sessions']>0) echo ' <span class="badge badge-green" style="font-size:10px">In Use</span>';
          ?></td>
          <td class="td-muted"><?= $t['last_seen'] ? date('M d H:i',strtotime($t['last_seen'])) : 'Never' ?></td>
          <td><?= $t['total_sessions'] ?></td>
          <td>
            <form method="POST" style="display:flex;gap:6px;align-items:center">
              <input type="hidden" name="action" value="set_status">
              <input type="hidden" name="id" value="<?= $t['id'] ?>">
              <select name="status" class="form-control" style="max-width:130px;padding:4px 8px;font-size:11px">
                <option value="online"      <?= $t['status']==='online'?'selected':'' ?>>Online</option>
                <option value="offline"     <?= $t['status']==='offline'?'selected':'' ?>>Offline</option>
                <option value="maintenance" <?= $t['status']==='maintenance'?'selected':'' ?>>Maintenance</option>
              </select>
              <button type="submit" class="btn btn-outline btn-sm">Set</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Terminal Modal -->
<div class="modal-backdrop" id="modal-add">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add Terminal</span>
      <button class="btn-close" onclick="closeModal('modal-add')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Terminal Code *</label>
          <input name="terminal_code" class="form-control" placeholder="PC-09" required>
          <span style="font-size:11px;color:var(--text-muted)">This must match the TERMINAL_CODE in the Electron app's .env file.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Location</label>
          <input name="location" class="form-control" placeholder="Reading Room A">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add</button>
      </div>
    </form>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
