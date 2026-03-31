<?php
// ============================================================
// Admin — App Requests — /admin/apps.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo   = db();
$admin = current_admin();
$flash = '';

// ── Handle actions ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'resolve') {
        $rid    = (int) ($_POST['request_id']   ?? 0);
        $status = $_POST['status']               ?? '';
        $notes  = trim($_POST['admin_notes']     ?? '');
        if ($rid && in_array($status, ['approved','denied','completed'], true)) {
            $pdo->prepare(
                "UPDATE app_requests
                 SET status=:s, admin_notes=:n, resolved_at=NOW(), resolved_by=:a
                 WHERE id=:id"
            )->execute([':s'=>$status,':n'=>$notes?:null,':a'=>$admin['id'],':id'=>$rid]);
            log_activity('ADMIN_APP_REQUEST', "Request #{$rid} marked {$status}", null, $admin['id']);
            $flash = "Request #{$rid} updated to: " . ucfirst($status);
        }
    }

    if ($action === 'add_app') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $ver  = trim($_POST['version'] ?? '');
        $cat  = trim($_POST['category'] ?? '');
        if ($name) {
            try {
                $pdo->prepare('INSERT INTO installed_apps(name,description,version,category) VALUES(:n,:d,:v,:c)')
                    ->execute([':n'=>$name,':d'=>$desc?:null,':v'=>$ver?:null,':c'=>$cat?:null]);
                $flash = "App '{$name}' added to catalog.";
            } catch (PDOException $e) {
                $flash = "App '{$name}' already exists in catalog.";
            }
        }
    }

    if ($action === 'toggle_app') {
        $aid    = (int) ($_POST['app_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($aid && in_array($status, ['active','inactive'], true)) {
            $pdo->prepare('UPDATE installed_apps SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$aid]);
            $flash = "App status updated.";
        }
    }
}

// ── Load data ─────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'pending';

$requests = $pdo->prepare(
    "SELECT r.*, u.name AS user_name, u.user_id AS user_code,
            a.name AS resolved_by_name
     FROM   app_requests r
     JOIN   users  u ON u.id = r.user_id
     LEFT JOIN admins a ON a.id = r.resolved_by
     WHERE  (:st = '' OR r.status = :st2)
     ORDER  BY r.requested_at DESC
     LIMIT  100"
);
$requests->execute([':st'=>$filter_status, ':st2'=>$filter_status]);
$requests = $requests->fetchAll();

$apps = $pdo->query("SELECT * FROM installed_apps ORDER BY category, name")->fetchAll();
$pending_count = (int) $pdo->query("SELECT COUNT(*) FROM app_requests WHERE status='pending'")->fetchColumn();

$page = 'apps';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?><div class="flash flash-success"><?= h($flash) ?></div><?php endif; ?>

<!-- Tab nav -->
<div style="display:flex;gap:8px;margin-bottom:24px">
  <a href="?status=pending"   class="btn <?= $filter_status==='pending'  ?'btn-primary':'btn-outline' ?>">
    Pending <?php if ($pending_count>0): ?><span class="badge badge-red" style="margin-left:4px"><?= $pending_count ?></span><?php endif; ?>
  </a>
  <a href="?status=approved"  class="btn <?= $filter_status==='approved' ?'btn-primary':'btn-outline' ?>">Approved</a>
  <a href="?status=denied"    class="btn <?= $filter_status==='denied'   ?'btn-primary':'btn-outline' ?>">Denied</a>
  <a href="?status=completed" class="btn <?= $filter_status==='completed'?'btn-primary':'btn-outline' ?>">Completed</a>
  <a href="?status="          class="btn <?= $filter_status===''         ?'btn-primary':'btn-outline' ?>">All</a>
  <div style="flex:1"></div>
  <button class="btn btn-outline" onclick="openModal('modal-catalog')">📋 App Catalog</button>
  <button class="btn btn-primary" onclick="openModal('modal-add-app')">+ Add App</button>
</div>

<!-- Requests Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">📦 App Requests
      <span class="badge badge-gray" style="margin-left:8px"><?= count($requests) ?></span>
    </span>
  </div>
  <?php if (empty($requests)): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><p>No <?= $filter_status ?: '' ?> requests.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>User</th><th>App Name</th><th>Type</th><th>Reason</th><th>Requested</th><th>Status</th><th>Action</th></tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $r): ?>
        <tr>
          <td>
            <div style="font-weight:600"><?= h($r['user_name']) ?></div>
            <div class="mono td-muted" style="font-size:11px"><?= h($r['user_code']) ?></div>
          </td>
          <td style="font-weight:600"><?= h($r['app_name']) ?></td>
          <td><span class="badge <?= $r['request_type']==='install'?'badge-green':'badge-red' ?>"><?= ucfirst($r['request_type']) ?></span></td>
          <td class="td-muted" style="max-width:200px;font-size:12px"><?= h(mb_strimwidth($r['reason']??'—', 0, 80, '…')) ?></td>
          <td class="td-muted" style="font-size:12px"><?= date('M d, H:i', strtotime($r['requested_at'])) ?></td>
          <td><?php
            $bs=['pending'=>'badge-yellow','approved'=>'badge-green','denied'=>'badge-red','completed'=>'badge-blue'];
            echo '<span class="badge '.($bs[$r['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($r['status']).'</span>';
            if ($r['resolved_by_name']): echo '<br><span class="td-muted" style="font-size:10px">by '.h($r['resolved_by_name']).'</span>'; endif;
          ?></td>
          <td>
            <?php if ($r['status']==='pending'): ?>
            <button class="btn btn-success btn-sm"
              onclick="resolveRequest(<?= $r['id'] ?>,'approve','<?= addslashes(h($r['app_name'])) ?>')">✔ Approve</button>
            <button class="btn btn-danger btn-sm" style="margin-top:4px"
              onclick="resolveRequest(<?= $r['id'] ?>,'deny','<?= addslashes(h($r['app_name'])) ?>')">✕ Deny</button>
            <?php else: ?>
              <span class="td-muted" style="font-size:12px"><?= $r['admin_notes'] ? h(mb_strimwidth($r['admin_notes'],0,50,'…')) : '—' ?></span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Resolve Modal -->
<div class="modal-backdrop" id="modal-resolve">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="resolve-title">Resolve Request</span>
      <button class="btn-close" onclick="closeModal('modal-resolve')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="resolve">
      <input type="hidden" name="request_id" id="resolve-rid">
      <input type="hidden" name="status" id="resolve-status">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Admin Notes (optional)</label>
          <textarea name="admin_notes" class="form-control" rows="3" placeholder="Notes for the user or IT staff…"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-resolve')">Cancel</button>
        <button type="submit" id="resolve-btn" class="btn btn-primary">Confirm</button>
      </div>
    </form>
  </div>
</div>

<!-- App Catalog Modal -->
<div class="modal-backdrop" id="modal-catalog" style="align-items:flex-start;padding-top:60px">
  <div class="modal" style="max-width:680px;max-height:80vh;overflow-y:auto">
    <div class="modal-header">
      <span class="modal-title">📋 Installed App Catalog</span>
      <button class="btn-close" onclick="closeModal('modal-catalog')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <table>
        <thead><tr><th>Name</th><th>Category</th><th>Version</th><th>Status</th><th>Toggle</th></tr></thead>
        <tbody>
          <?php foreach ($apps as $a): ?>
          <tr>
            <td><strong><?= h($a['name']) ?></strong><br><span class="td-muted" style="font-size:11px"><?= h(mb_strimwidth($a['description']??'',0,60,'…')) ?></span></td>
            <td><span class="badge badge-blue" style="font-size:10px"><?= h($a['category']??'—') ?></span></td>
            <td class="mono"><?= h($a['version']??'—') ?></td>
            <td><span class="badge <?= $a['status']==='active'?'badge-green':'badge-gray' ?>"><?= ucfirst($a['status']) ?></span></td>
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_app">
                <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="status" value="<?= $a['status']==='active'?'inactive':'active' ?>">
                <button type="submit" class="btn btn-outline btn-sm"><?= $a['status']==='active'?'Disable':'Enable' ?></button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Add App Modal -->
<div class="modal-backdrop" id="modal-add-app">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Add App to Catalog</span>
      <button class="btn-close" onclick="closeModal('modal-add-app')">✕</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="add_app">
      <div class="modal-body">
        <div class="form-group"><label class="form-label">App Name *</label><input name="name" class="form-control" required placeholder="Microsoft Word"></div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Version</label><input name="version" class="form-control" placeholder="2021"></div>
          <div class="form-group"><label class="form-label">Category</label><input name="category" class="form-control" placeholder="Productivity"></div>
        </div>
        <div class="form-group"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-add-app')">Cancel</button>
        <button type="submit" class="btn btn-primary">Add App</button>
      </div>
    </form>
  </div>
</div>

<script>
function resolveRequest(id, decision, appName) {
  document.getElementById('resolve-rid').value = id;
  document.getElementById('resolve-status').value = decision === 'approve' ? 'approved' : 'denied';
  document.getElementById('resolve-title').textContent = (decision==='approve'?'Approve':'Deny') + ': ' + appName;
  document.getElementById('resolve-btn').textContent = decision==='approve' ? '✔ Approve' : '✕ Deny';
  document.getElementById('resolve-btn').className = 'btn ' + (decision==='approve' ? 'btn-success' : 'btn-danger');
  openModal('modal-resolve');
}
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>
