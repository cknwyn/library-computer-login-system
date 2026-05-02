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
    
    if ($action === 'add') {
        $code = trim($_POST['terminal_code'] ?? '');
        $name = trim($_POST['terminal_name'] ?? '');
        $cid  = (int)($_POST['campus_id']     ?? 0);
        $rid  = (int)($_POST['room_id']       ?? 0);
        
        if ($code) {
            try {
                $pdo->prepare('INSERT INTO terminals(terminal_code, terminal_name, campus_id, room_id) VALUES(:c, :n, :cid, :rid)')
                    ->execute([':c'=>$code, ':n'=>$name?:null, ':cid'=>$cid?:null, ':rid'=>$rid?:null]);
                $flash = "Terminal '{$code}' added.";
            } catch (PDOException $e) { $flash = "Error: " . $e->getMessage(); }
        }
    }

    if ($action === 'update') {
        $tid  = (int)$_POST['id'];
        $code = trim($_POST['terminal_code'] ?? '');
        $name = trim($_POST['terminal_name'] ?? '');
        $cid  = (int)($_POST['campus_id']     ?? 0);
        $rid  = (int)($_POST['room_id']       ?? 0);
        if ($tid && $code) {
            $pdo->prepare('UPDATE terminals SET terminal_code=:c, terminal_name=:n, campus_id=:cid, room_id=:rid WHERE id=:id')
                ->execute([':c'=>$code, ':n'=>$name?:null, ':cid'=>$cid?:null, ':rid'=>$rid?:null, ':id'=>$tid]);
            $flash = "Terminal updated.";
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
$terminals = $pdo->query(
    "SELECT t.*, c.name as campus_name, r.name as room_name,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id AND s.status='active') AS active_sessions,
            (SELECT COUNT(*) FROM sessions s WHERE s.terminal_id=t.id) AS total_sessions
     FROM terminals t
     LEFT JOIN campuses c ON t.campus_id = c.id
     LEFT JOIN rooms r ON t.room_id = r.id
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
  <button class="btn btn-primary" onclick="document.getElementById('terminal-modal-title').textContent='Provision Terminal'; document.getElementById('terminal-action').value='add'; openModal('modal-terminal')">
    <i data-lucide="plus" style="width:18px"></i> Provision New Terminal
  </button>
</div>

<div class="terminal-grid fade-in">
  <?php foreach ($terminals as $t): ?>
  <div class="terminal-card">
    <div class="terminal-header">
      <div class="feed-icon" style="background: var(--bg-base); box-shadow: none">
        <i data-lucide="<?= $t['active_sessions'] > 0 ? 'monitor-play' : 'monitor' ?>"></i>
      </div>
      <div style="display:flex; gap:6px; align-items:center">
        <?php
          $b=['online'=>'badge-green','offline'=>'badge-gray','maintenance'=>'badge-yellow'];
          echo '<span class="badge '.($b[$t['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($t['status']).'</span>';
        ?>
      </div>
    </div>
    
    <div class="terminal-info">
      <div style="display:flex; justify-content:space-between; align-items:flex-start">
        <h3 style="margin:0"><?= h($t['terminal_name'] ?: $t['terminal_code']) ?></h3>
        <button class="btn btn-outline btn-sm" style="padding:4px" onclick="editTerminal(<?= h(json_encode($t)) ?>)">
            <i data-lucide="edit-2" style="width:14px"></i>
        </button>
      </div>
      <div class="td-muted mono" style="font-size:11px; margin-bottom:8px"><?= h($t['terminal_code']) ?></div>
      
      <p>
        <i data-lucide="map-pin" style="width:12px; height:12px; vertical-align: middle; margin-right: 4px"></i> 
        <?= $t['campus_name'] ? h($t['campus_name']) : 'Unassigned Campus' ?>
      </p>
      <p style="font-size: 11px; margin-top: 4px; color: var(--text-faint)">
        <i data-lucide="door-open" style="width:12px; height:12px; vertical-align: middle; margin-right: 4px"></i> 
        <?= $t['room_name'] ? h($t['room_name']) : 'Unassigned Room' ?>
      </p>
      <p style="font-size: 10px; margin-top: 8px; font-weight:600; color: var(--primary)">
        <i data-lucide="cpu" style="width:12px; height:12px; vertical-align: middle; margin-right: 4px"></i> 
        <?= $t['pc_hostname'] ? h($t['pc_hostname']) : 'PC Name not captured' ?>
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
  <div class="terminal-card" style="border: 2px dashed var(--border); background: transparent; cursor: pointer; align-items: center; justify-content: center; min-height: 240px" 
       onclick="document.getElementById('terminal-modal-title').textContent='Provision Terminal'; document.getElementById('terminal-action').value='add'; openModal('modal-terminal')">
     <div style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid var(--border); display: flex; align-items: center; justify-content: center; color: var(--text-faint); margin-bottom: 12px">
       <i data-lucide="plus"></i>
     </div>
     <div style="font-weight: 700; color: var(--text-muted)">Add New Node</div>
     <div style="font-size: 12px; color: var(--text-faint)">Register new hardware</div>
  </div>
</div>

<!-- Terminal Modal (Add/Edit) -->
<div class="modal-backdrop" id="modal-terminal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="terminal-modal-title">Provision Terminal</span>
      <button class="btn-close" onclick="closeModal('modal-terminal')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" id="terminal-action" value="add">
      <input type="hidden" name="id" id="terminal-id">
      <div class="modal-body" style="max-height:65vh; overflow-y:auto">
        <div class="form-group">
          <label class="form-label">Terminal ID (Code) *</label>
          <input name="terminal_code" id="terminal-code" class="form-control" placeholder="e.g. TERM-15-HSLIB" required>
        </div>
        <div class="form-group">
          <label class="form-label">Administrative Name</label>
          <input name="terminal_name" id="terminal-name" class="form-control" placeholder="e.g. Reference Desk PC">
        </div>
        <div class="form-group">
          <label class="form-label">Campus</label>
          <select name="campus_id" id="add-campus" class="form-control" onchange="loadRooms(this.value)" required>
            <option value="">Select Campus</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Room</label>
          <select name="room_id" id="add-room" class="form-control" disabled required>
            <option value="">Select Room</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-terminal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Terminal</button>
      </div>
    </form>
  </div>
</div>

<script>
function editTerminal(t) {
    document.getElementById('terminal-modal-title').textContent = 'Update Terminal';
    document.getElementById('terminal-action').value = 'update';
    document.getElementById('terminal-id').value = t.id;
    document.getElementById('terminal-code').value = t.terminal_code;
    document.getElementById('terminal-name').value = t.terminal_name || '';
    document.getElementById('add-campus').value = t.campus_id || '';
    loadRooms(t.campus_id).then(() => {
        document.getElementById('add-room').value = t.room_id || '';
    });
    openModal('modal-terminal');
}

async function loadCampuses() {
    const res = await fetch('../api/classifications.php?type=campuses');
    const json = await res.json();
    const select = document.getElementById('add-campus');
    select.innerHTML = '<option value="">Select Campus</option>';
    json.data.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        select.appendChild(opt);
    });
}

async function loadRooms(campusId) {
    const select = document.getElementById('add-room');
    select.innerHTML = '<option value="">Select Room</option>';
    if (!campusId) {
        select.disabled = true;
        return;
    }
    const res = await fetch(`../api/classifications.php?type=rooms&campus_id=${campusId}`);
    const json = await res.json();
    json.data.forEach(r => {
        const opt = document.createElement('option');
        opt.value = r.id;
        opt.textContent = r.name;
        select.appendChild(opt);
    });
    select.disabled = false;
}

loadCampuses();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
