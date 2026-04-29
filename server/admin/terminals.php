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

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Campus Management
    if ($action === 'add_campus') {
        $name = trim($_POST['campus_name'] ?? '');
        if ($name) {
            try {
                $pdo->prepare("INSERT INTO campuses (name) VALUES (?)")->execute([$name]);
                $flash = "Campus '{$name}' added.";
            } catch (Exception $e) { $flash = "Campus already exists."; }
        }
    }
    
    if ($action === 'delete_campus') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $pdo->prepare("DELETE FROM campuses WHERE id = ?")->execute([$id]);
                $flash = "Campus deleted.";
            } catch (Exception $e) { $flash = "Cannot delete campus (it may have rooms assigned)."; }
        }
    }

    // Room Management
    if ($action === 'add_room') {
        $cid  = (int)($_POST['campus_id'] ?? 0);
        $name = trim($_POST['room_name'] ?? '');
        if ($cid && $name) {
            $pdo->prepare("INSERT INTO rooms (campus_id, room_name) VALUES (?, ?)")->execute([$cid, $name]);
            $flash = "Room '{$name}' added.";
        }
    }
    
    if ($action === 'update_room') {
        $id   = (int)($_POST['id'] ?? 0);
        $cid  = (int)($_POST['campus_id'] ?? 0);
        $name = trim($_POST['room_name'] ?? '');
        if ($id && $cid && $name) {
            $pdo->prepare("UPDATE rooms SET campus_id = ?, room_name = ? WHERE id = ?")->execute([$cid, $name, $id]);
            $flash = "Room updated.";
        }
    }
    
    if ($action === 'delete_room') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            try {
                $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([$id]);
                $flash = "Room deleted.";
            } catch (Exception $e) { $flash = "Cannot delete room (it may have terminals assigned)."; }
        }
    }

    // Terminal Management
    if ($action === 'add') {
        $code = trim($_POST['terminal_code'] ?? '');
        $pcn  = trim($_POST['pc_name']       ?? '');
        $rid  = (int)($_POST['room_id'] ?? 0);
        if ($code) {
            try {
                $pdo->prepare('INSERT INTO terminals(terminal_code, pc_name, room_id) VALUES(:c, :p, :r)')
                    ->execute([':c'=>$code, ':p'=>$pcn ?: null, ':r'=>$rid ?: null]);
                $flash = "Terminal '{$code}' added.";
            } catch (PDOException $e) { $flash = "Terminal code '{$code}' already exists."; }
        }
    }
    
    if ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $code = trim($_POST['terminal_code'] ?? '');
        $pcn  = trim($_POST['pc_name']       ?? '');
        $rid  = (int)($_POST['room_id']      ?? 0);
        if ($id && $code) {
            $pdo->prepare("UPDATE terminals SET terminal_code = ?, pc_name = ?, room_id = ? WHERE id = ?")
                ->execute([$code, $pcn ?: null, $rid ?: null, $id]);
            $flash = "Terminal '{$code}' updated.";
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

// ── Fetch Data ───────────────────────────────────────────────
$campuses = $pdo->query("SELECT * FROM campuses ORDER BY name")->fetchAll();

$rooms = $pdo->query("
    SELECT r.*, c.name AS campus_name 
    FROM rooms r 
    JOIN campuses c ON c.id = r.campus_id 
    ORDER BY c.name, r.room_name
")->fetchAll();

$terminals = $pdo->query(
    "SELECT t.*, r.room_name, c.name AS campus_name,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id AND s.status='active') AS active_sessions,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id) AS total_sessions
     FROM terminals t
     LEFT JOIN rooms r ON r.id = t.room_id
     LEFT JOIN campuses c ON c.id = r.campus_id
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
  <div style="display:flex; gap:12px">
    <button class="btn btn-outline" onclick="openModal('modal-rooms')">
      <i data-lucide="layers" style="width:18px"></i> Manage Locations
    </button>
    <button class="btn btn-primary" onclick="openModal('modal-add')">
      <i data-lucide="plus" style="width:18px"></i> Provision New Terminal
    </button>
  </div>
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
      <div style="display:flex; gap:6px">
        <button class="btn btn-info btn-sm" onclick='editTerminal(<?= json_encode($t) ?>)' title="Edit Asset"><i data-lucide="edit-3" style="width:14px"></i></button>
        <?php
          $b=['online'=>'badge-green','offline'=>'badge-gray','maintenance'=>'badge-yellow'];
          echo '<span class="badge '.($b[$t['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($t['status']).'</span>';
        ?>
      </div>
    </div>
    
    <div class="terminal-info">
      <h3><?= h($t['terminal_code']) ?></h3>
      <?php if ($t['pc_name']): ?>
        <div class="td-muted mono" style="font-size:11px; margin-bottom: 4px"><?= h($t['pc_name']) ?></div>
      <?php endif; ?>
      <p>
        <i data-lucide="map-pin" style="width:12px; height:12px; vertical-align: middle; margin-right: 4px"></i> 
        <?= h($t['room_name'] ? "{$t['room_name']} ({$t['campus_name']})" : 'Unassigned Location') ?>
      </p>
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
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Terminal Name / Code</label>
            <input name="terminal_code" class="form-control" placeholder="e.g. PC-01" required>
          </div>
          <div class="form-group">
            <label class="form-label">Actual PC Name</label>
            <input name="pc_name" class="form-control" placeholder="e.g. LIB-WS-001">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Room / Location</label>
          <select name="room_id" class="form-control" required>
            <option value="">— Select Room —</option>
            <?php foreach ($rooms as $r): ?>
              <option value="<?= $r['id'] ?>"><?= h($r['room_name']) ?> (<?= h($r['campus_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <p style="font-size: 11px; margin-top: 8px; color: var(--text-faint)">
            Don't see the room? <a href="#" onclick="openModal('modal-rooms'); closeModal('modal-add'); return false;" style="font-weight: 700">Manage Locations</a>
          </p>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-add')">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Terminal</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Terminal Modal -->
<div class="modal-backdrop" id="modal-edit-terminal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit Asset Configuration</span>
      <button class="btn-close" onclick="closeModal('modal-edit-terminal')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-tid">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Terminal Name / Code</label>
            <input name="terminal_code" id="edit-tcode" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Actual PC Name</label>
            <input name="pc_name" id="edit-tpcn" class="form-control">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Room / Location</label>
          <select name="room_id" id="edit-trid" class="form-control" required>
            <option value="">— Select Room —</option>
            <?php foreach ($rooms as $r): ?>
              <option value="<?= $r['id'] ?>"><?= h($r['room_name']) ?> (<?= h($r['campus_name']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit-terminal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- Room & Campus Management Modal -->
<div class="modal-backdrop" id="modal-rooms">
  <div class="modal" style="max-width: 800px">
    <div class="modal-header">
      <span class="modal-title">Manage Locations</span>
      <button class="btn-close" onclick="closeModal('modal-rooms')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <div class="modal-body" style="padding-top: 0">
      <!-- Tabs -->
      <div style="display:flex; gap:24px; border-bottom:1px solid var(--border-light); margin-bottom:24px">
        <button type="button" onclick="showTab('tab-rooms')" id="btn-tab-rooms" style="padding:16px 4px; border:none; background:none; font-weight:700; color:var(--primary); border-bottom:2px solid var(--primary); cursor:pointer">Rooms</button>
        <button type="button" onclick="showTab('tab-campuses')" id="btn-tab-campuses" style="padding:16px 4px; border:none; background:none; font-weight:700; color:var(--text-muted); cursor:pointer">Campuses</button>
      </div>

      <!-- Rooms Tab -->
      <div id="tab-rooms">
        <form method="POST" id="room-form" style="background: var(--bg-base); padding: 16px; border-radius: 8px; margin-bottom: 24px">
          <input type="hidden" name="action" id="room-action" value="add_room">
          <input type="hidden" name="id" id="room-id">
          <div style="display: grid; grid-template-columns: 1fr 1fr auto auto; gap: 12px; align-items: flex-end">
            <div class="form-group" style="margin:0">
              <label class="form-label">Campus</label>
              <select name="campus_id" id="room-campus-id" class="form-control" required>
                <?php foreach ($campuses as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin:0">
              <label class="form-label">Room Name</label>
              <input name="room_name" id="room-name" class="form-control" placeholder="e.g. Reading Room A" required>
            </div>
            <button type="submit" id="room-submit" class="btn btn-primary" style="height: 44px">Add</button>
            <button type="button" id="room-cancel" class="btn btn-outline" style="height: 44px; display: none" onclick="resetRoomForm()">Cancel</button>
          </div>
        </form>

        <div class="table-wrap" style="max-height: 350px; overflow-y: auto">
          <table>
            <thead><tr><th>Campus</th><th>Room Name</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($rooms as $r): ?>
              <tr>
                <td><span class="badge badge-gray"><?= h($r['campus_name']) ?></span></td>
                <td><span style="font-weight: 700"><?= h($r['room_name']) ?></span></td>
                <td>
                  <div style="display:flex; gap:4px">
                    <button type="button" class="btn btn-info btn-sm" onclick='editRoom(<?= $r['id'] ?>, <?= $r['campus_id'] ?>, "<?= addslashes(h($r['room_name'])) ?>")'>
                      <i data-lucide="edit-2" style="width:14px"></i>
                    </button>
                    <form method="POST" onsubmit="return confirm('Delete this room?')">
                      <input type="hidden" name="action" value="delete_room">
                      <input type="hidden" name="id" value="<?= $r['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="trash-2" style="width:14px"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Campuses Tab -->
      <div id="tab-campuses" style="display: none">
        <form method="POST" style="background: var(--bg-base); padding: 16px; border-radius: 8px; margin-bottom: 24px">
          <input type="hidden" name="action" value="add_campus">
          <div style="display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: flex-end">
            <div class="form-group" style="margin:0">
              <label class="form-label">Campus Name</label>
              <input name="campus_name" class="form-control" placeholder="e.g. AUF MAIN" required>
            </div>
            <button type="submit" class="btn btn-primary" style="height: 44px">Add Campus</button>
          </div>
        </form>

        <div class="table-wrap">
          <table>
            <thead><tr><th>Campus Name</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($campuses as $c): ?>
              <tr>
                <td style="font-weight: 700"><?= h($c['name']) ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Delete this campus? This will only work if no rooms are assigned to it.')">
                    <input type="hidden" name="action" value="delete_campus">
                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm"><i data-lucide="trash-2" style="width:14px"></i></button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function showTab(tabId) {
    document.getElementById('tab-rooms').style.display = tabId === 'tab-rooms' ? 'block' : 'none';
    document.getElementById('tab-campuses').style.display = tabId === 'tab-campuses' ? 'block' : 'none';
    
    document.getElementById('btn-tab-rooms').style.color = tabId === 'tab-rooms' ? 'var(--primary)' : 'var(--text-muted)';
    document.getElementById('btn-tab-rooms').style.borderBottom = tabId === 'tab-rooms' ? '2px solid var(--primary)' : 'none';
    
    document.getElementById('btn-tab-campuses').style.color = tabId === 'tab-campuses' ? 'var(--primary)' : 'var(--text-muted)';
    document.getElementById('btn-tab-campuses').style.borderBottom = tabId === 'tab-campuses' ? '2px solid var(--primary)' : 'none';
}

function editTerminal(t) {
    document.getElementById('edit-tid').value = t.id;
    document.getElementById('edit-tcode').value = t.terminal_code;
    document.getElementById('edit-tpcn').value = t.pc_name || '';
    document.getElementById('edit-trid').value = t.room_id || '';
    openModal('modal-edit-terminal');
}

function editRoom(id, campusId, name) {
    document.getElementById('room-action').value = 'update_room';
    document.getElementById('room-id').value = id;
    document.getElementById('room-campus-id').value = campusId;
    document.getElementById('room-name').value = name;
    document.getElementById('room-submit').textContent = 'Update';
    document.getElementById('room-cancel').style.display = 'inline-flex';
}

function resetRoomForm() {
    document.getElementById('room-action').value = 'add_room';
    document.getElementById('room-id').value = '';
    document.getElementById('room-name').value = '';
    document.getElementById('room-submit').textContent = 'Add Room';
    document.getElementById('room-cancel').style.display = 'none';
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
