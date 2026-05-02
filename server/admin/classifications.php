<?php
// ============================================================
// Admin — Classification Management — /admin/classifications.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo   = db();
$admin = current_admin();
$flash = '';
$flash_type = 'success';

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // -- College CRUD --
    if ($action === 'create_college') {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '') ?: null;
        if ($name) {
            try {
                $pdo->prepare("INSERT INTO colleges (name, code) VALUES (?, ?)")->execute([$name, $code]);
                $flash = "College '{$name}' created.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'update_college') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '') ?: null;
        if ($id && $name) {
            try {
                $pdo->prepare("UPDATE colleges SET name=?, code=? WHERE id=?")->execute([$name, $code, $id]);
                $flash = "College updated.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'delete_college') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM colleges WHERE id=?")->execute([$id]);
            $flash = "College deleted.";
        }
    }

    // -- Department CRUD --
    if ($action === 'create_dept') {
        $cid  = (int)$_POST['college_id'];
        $name = trim($_POST['name'] ?? '');
        if ($cid && $name) {
            try {
                $pdo->prepare("INSERT INTO departments (college_id, name) VALUES (?, ?)")->execute([$cid, $name]);
                $flash = "Department '{$name}' created.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'update_dept') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if ($id && $name) {
            $pdo->prepare("UPDATE departments SET name=? WHERE id=?")->execute([$name, $id]);
            $flash = "Department updated.";
        }
    }
    if ($action === 'delete_dept') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM departments WHERE id=?")->execute([$id]);
            $flash = "Department deleted.";
        }
    }

    // -- Degree CRUD --
    if ($action === 'create_degree') {
        $did  = (int)$_POST['department_id'];
        $name = trim($_POST['name'] ?? '');
        if ($did && $name) {
            try {
                $pdo->prepare("INSERT INTO degrees (department_id, name) VALUES (?, ?)")->execute([$did, $name]);
                $flash = "Degree '{$name}' created.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'update_degree') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if ($id && $name) {
            $pdo->prepare("UPDATE degrees SET name=? WHERE id=?")->execute([$name, $id]);
            $flash = "Degree updated.";
        }
    }
    if ($action === 'delete_degree') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM degrees WHERE id=?")->execute([$id]);
            $flash = "Degree deleted.";
        }
    }

    // -- Campus CRUD --
    if ($action === 'create_campus') {
        $name = trim($_POST['name'] ?? '');
        if ($name) {
            try {
                $pdo->prepare("INSERT INTO campuses (name) VALUES (?)")->execute([$name]);
                $flash = "Campus '{$name}' created.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'update_campus') {
        $id = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if ($id && $name) {
            try {
                $pdo->prepare("UPDATE campuses SET name=? WHERE id=?")->execute([$name, $id]);
                $flash = "Campus updated.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'delete_campus') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM campuses WHERE id=?")->execute([$id]);
            $flash = "Campus deleted.";
        }
    }

    // -- Room CRUD --
    if ($action === 'create_room') {
        $cid  = (int)$_POST['campus_id'];
        $name = trim($_POST['name'] ?? '');
        if ($cid && $name) {
            try {
                $pdo->prepare("INSERT INTO rooms (campus_id, name) VALUES (?, ?)")->execute([$cid, $name]);
                $flash = "Room '{$name}' created.";
            } catch (Exception $e) { $flash = "Error: " . $e->getMessage(); $flash_type = 'error'; }
        }
    }
    if ($action === 'update_room') {
        $id   = (int)$_POST['id'];
        $name = trim($_POST['name'] ?? '');
        if ($id && $name) {
            $pdo->prepare("UPDATE rooms SET name=? WHERE id=?")->execute([$name, $id]);
            $flash = "Room updated.";
        }
    }
    if ($action === 'delete_room') {
        $id = (int)$_POST['id'];
        if ($id) {
            $pdo->prepare("DELETE FROM rooms WHERE id=?")->execute([$id]);
            $flash = "Room deleted.";
        }
    }
}

// ── Fetch Data ────────────────────────────────────────────────
$colleges = $pdo->query("SELECT * FROM colleges ORDER BY name ASC")->fetchAll();
$depts    = $pdo->query("SELECT d.*, c.name as college_name FROM departments d JOIN colleges c ON d.college_id = c.id ORDER BY c.name, d.name")->fetchAll();
$degrees  = $pdo->query("SELECT deg.*, d.name as dept_name FROM degrees deg JOIN departments d ON deg.department_id = d.id ORDER BY d.name, deg.name")->fetchAll();

$campuses = $pdo->query("SELECT * FROM campuses ORDER BY name ASC")->fetchAll();
$rooms    = $pdo->query("SELECT r.*, c.name as campus_name FROM rooms r JOIN campuses c ON r.campus_id = c.id ORDER BY c.name, r.name")->fetchAll();

$page = 'classifications';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
  <div class="flash flash-<?= $flash_type ?> fade-in" style="margin-bottom: 32px">
    <i data-lucide="<?= $flash_type==='error'?'alert-circle':'check-circle' ?>" style="width:18px"></i> <?= h($flash) ?>
  </div>
<?php endif; ?>

<!-- Tabs Navigation -->
<div class="tabs-container" style="margin-bottom: 32px; border-bottom: 1px solid var(--border-light); display: flex; gap: 32px">
    <button class="tab-link active" onclick="switchTab(event, 'academic')" style="padding: 12px 4px; border: none; background: none; font-weight: 700; color: var(--primary); border-bottom: 2px solid var(--primary); cursor: pointer; display: flex; align-items: center; gap: 8px">
        <i data-lucide="graduation-cap" style="width:18px"></i> Academic
    </button>
    <button class="tab-link" onclick="switchTab(event, 'physical')" style="padding: 12px 4px; border: none; background: none; font-weight: 700; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 8px">
        <i data-lucide="map-pin" style="width:18px"></i> Physical Locations
    </button>
</div>

<!-- Academic Tab -->
<div id="academic" class="tab-content fade-in">
    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px;">
        <!-- Colleges Section -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Colleges</span>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-college')"><i data-lucide="plus"></i> Add</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($colleges as $c): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700"><?= h($c['name']) ?></div>
                                    <?php if ($c['code']): ?><div class="td-muted mono" style="font-size: 11px"><?= h($c['code']) ?></div><?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px">
                                        <button class="btn btn-outline btn-sm" onclick="editCollege(<?= $c['id'] ?>, '<?= addslashes(h($c['name'])) ?>', '<?= addslashes(h($c['code'])) ?>')"><i data-lucide="edit-2" style="width:14px"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this college and all its departments?')">
                                            <input type="hidden" name="action" value="delete_college">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button class="btn btn-outline btn-sm" style="color:var(--error)"><i data-lucide="trash-2" style="width:14px"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Departments Section -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Departments</span>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-dept')"><i data-lucide="plus"></i> Add</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Name</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($depts as $d): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700"><?= h($d['name']) ?></div>
                                    <div class="badge badge-gray" style="font-size: 10px"><?= h($d['college_name']) ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px">
                                        <button class="btn btn-outline btn-sm" onclick="editDept(<?= $d['id'] ?>, '<?= addslashes(h($d['name'])) ?>', <?= $d['college_id'] ?>)"><i data-lucide="edit-2" style="width:14px"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this department and all its degrees?')">
                                            <input type="hidden" name="action" value="delete_dept">
                                            <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                            <button class="btn btn-outline btn-sm" style="color:var(--error)"><i data-lucide="trash-2" style="width:14px"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Degrees Section -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Degrees / Programs</span>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-degree')"><i data-lucide="plus"></i> Add</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Program Name</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($degrees as $deg): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700"><?= h($deg['name']) ?></div>
                                    <div class="td-muted" style="font-size: 10px"><?= h($deg['dept_name']) ?></div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 4px">
                                        <button class="btn btn-outline btn-sm" onclick="editDegree(<?= $deg['id'] ?>, '<?= addslashes(h($deg['name'])) ?>', <?= $deg['department_id'] ?>)"><i data-lucide="edit-2" style="width:14px"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this degree?')">
                                            <input type="hidden" name="action" value="delete_degree">
                                            <input type="hidden" name="id" value="<?= $deg['id'] ?>">
                                            <button class="btn btn-outline btn-sm" style="color:var(--error)"><i data-lucide="trash-2" style="width:14px"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Physical Tab -->
<div id="physical" class="tab-content fade-in" style="display: none">
    <div class="row" style="display:grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <!-- Campuses Section -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Campuses</span>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-campus')"><i data-lucide="plus"></i> Add</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Campus Name</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($campuses as $cp): ?>
                            <tr>
                                <td style="font-weight: 700"><?= h($cp['name']) ?></td>
                                <td>
                                    <div style="display: flex; gap: 4px">
                                        <button class="btn btn-outline btn-sm" onclick="editCampus(<?= $cp['id'] ?>, '<?= addslashes(h($cp['name'])) ?>')"><i data-lucide="edit-2" style="width:14px"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this campus and all its rooms?')">
                                            <input type="hidden" name="action" value="delete_campus">
                                            <input type="hidden" name="id" value="<?= $cp['id'] ?>">
                                            <button class="btn btn-outline btn-sm" style="color:var(--error)"><i data-lucide="trash-2" style="width:14px"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Rooms Section -->
        <section>
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Rooms</span>
                    <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-room')"><i data-lucide="plus"></i> Add</button>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Room Name</th><th>Campus</th><th>Actions</th></tr></thead>
                        <tbody>
                            <?php foreach($rooms as $rm): ?>
                            <tr>
                                <td style="font-weight: 700"><?= h($rm['name']) ?></td>
                                <td><span class="badge badge-gray"><?= h($rm['campus_name']) ?></span></td>
                                <td>
                                    <div style="display: flex; gap: 4px">
                                        <button class="btn btn-outline btn-sm" onclick="editRoom(<?= $rm['id'] ?>, '<?= addslashes(h($rm['name'])) ?>', <?= $rm['campus_id'] ?>)"><i data-lucide="edit-2" style="width:14px"></i></button>
                                        <form method="POST" onsubmit="return confirm('Delete this room?')">
                                            <input type="hidden" name="action" value="delete_room">
                                            <input type="hidden" name="id" value="<?= $rm['id'] ?>">
                                            <button class="btn btn-outline btn-sm" style="color:var(--error)"><i data-lucide="trash-2" style="width:14px"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>

<!-- Modals -->
<!-- Modal: Add/Edit College -->
<div class="modal-backdrop" id="modal-add-college">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="college-modal-title">Add College</span><button class="btn-close" onclick="closeModal('modal-add-college')"><i data-lucide="x"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" id="college-action" value="create_college">
            <input type="hidden" name="id" id="college-id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">College Name</label><input name="name" id="college-name" class="form-control" placeholder="e.g. College of Computer Studies" required></div>
                <div class="form-group"><label class="form-label">Abbreviation / Code</label><input name="code" id="college-code" class="form-control" placeholder="e.g. CCS"></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add-college')">Cancel</button><button type="submit" class="btn btn-primary">Save College</button></div>
        </form>
    </div>
</div>

<!-- Modal: Add/Edit Dept -->
<div class="modal-backdrop" id="modal-add-dept">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="dept-modal-title">Add Department</span><button class="btn-close" onclick="closeModal('modal-add-dept')"><i data-lucide="x"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" id="dept-action" value="create_dept">
            <input type="hidden" name="id" id="dept-id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">College</label>
                    <select name="college_id" id="dept-college" class="form-control" required>
                        <?php foreach($colleges as $c): ?><option value="<?= $c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Department Name</label><input name="name" id="dept-name" class="form-control" placeholder="e.g. Information Technology" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add-dept')">Cancel</button><button type="submit" class="btn btn-primary">Save Dept</button></div>
        </form>
    </div>
</div>

<!-- Modal: Add/Edit Degree -->
<div class="modal-backdrop" id="modal-add-degree">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="degree-modal-title">Add Degree</span><button class="btn-close" onclick="closeModal('modal-add-degree')"><i data-lucide="x"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" id="degree-action" value="create_degree">
            <input type="hidden" name="id" id="degree-id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Department</label>
                    <select name="department_id" id="degree-dept" class="form-control" required>
                        <?php foreach($depts as $d): ?><option value="<?= $d['id'] ?>"><?= h($d['name']) ?> (<?= h($d['college_name']) ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Degree Name</label><input name="name" id="degree-name" class="form-control" placeholder="e.g. BS Information Technology" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add-degree')">Cancel</button><button type="submit" class="btn btn-primary">Save Degree</button></div>
        </form>
    </div>
</div>

<!-- Modal: Add/Edit Campus -->
<div class="modal-backdrop" id="modal-add-campus">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="campus-modal-title">Add Campus</span><button class="btn-close" onclick="closeModal('modal-add-campus')"><i data-lucide="x"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" id="campus-action" value="create_campus">
            <input type="hidden" name="id" id="campus-id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Campus Name</label><input name="name" id="campus-name" class="form-control" placeholder="e.g. Main Campus" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add-campus')">Cancel</button><button type="submit" class="btn btn-primary">Save Campus</button></div>
        </form>
    </div>
</div>

<!-- Modal: Add/Edit Room -->
<div class="modal-backdrop" id="modal-add-room">
    <div class="modal">
        <div class="modal-header"><span class="modal-title" id="room-modal-title">Add Room</span><button class="btn-close" onclick="closeModal('modal-add-room')"><i data-lucide="x"></i></button></div>
        <form method="POST">
            <input type="hidden" name="action" id="room-action" value="create_room">
            <input type="hidden" name="id" id="room-id">
            <div class="modal-body">
                <div class="form-group"><label class="form-label">Campus</label>
                    <select name="campus_id" id="room-campus" class="form-control" required>
                        <?php foreach($campuses as $cp): ?><option value="<?= $cp['id'] ?>"><?= h($cp['name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label class="form-label">Room Name</label><input name="name" id="room-name" class="form-control" placeholder="e.g. Reading Room A" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-add-room')">Cancel</button><button type="submit" class="btn btn-primary">Save Room</button></div>
        </form>
    </div>
</div>

<script>
function switchTab(evt, tabId) {
    const contents = document.getElementsByClassName('tab-content');
    for (let i = 0; i < contents.length; i++) contents[i].style.display = 'none';
    
    const links = document.getElementsByClassName('tab-link');
    for (let i = 0; i < links.length; i++) {
        links[i].classList.remove('active');
        links[i].style.color = 'var(--text-muted)';
        links[i].style.borderBottom = 'none';
    }
    
    document.getElementById(tabId).style.display = 'block';
    evt.currentTarget.classList.add('active');
    evt.currentTarget.style.color = 'var(--primary)';
    evt.currentTarget.style.borderBottom = '2px solid var(--primary)';
}

function editCollege(id, name, code) {
    document.getElementById('college-modal-title').textContent = 'Edit College';
    document.getElementById('college-action').value = 'update_college';
    document.getElementById('college-id').value = id;
    document.getElementById('college-name').value = name;
    document.getElementById('college-code').value = code;
    openModal('modal-add-college');
}
function editDept(id, name, cid) {
    document.getElementById('dept-modal-title').textContent = 'Edit Department';
    document.getElementById('dept-action').value = 'update_dept';
    document.getElementById('dept-id').value = id;
    document.getElementById('dept-name').value = name;
    document.getElementById('dept-college').value = cid;
    openModal('modal-add-dept');
}
function editDegree(id, name, did) {
    document.getElementById('degree-modal-title').textContent = 'Edit Degree';
    document.getElementById('degree-action').value = 'update_degree';
    document.getElementById('degree-id').value = id;
    document.getElementById('degree-name').value = name;
    document.getElementById('degree-dept').value = did;
    openModal('modal-add-degree');
}
function editCampus(id, name) {
    document.getElementById('campus-modal-title').textContent = 'Edit Campus';
    document.getElementById('campus-action').value = 'update_campus';
    document.getElementById('campus-id').value = id;
    document.getElementById('campus-name').value = name;
    openModal('modal-add-campus');
}
function editRoom(id, name, cid) {
    document.getElementById('room-modal-title').textContent = 'Edit Room';
    document.getElementById('room-action').value = 'update_room';
    document.getElementById('room-id').value = id;
    document.getElementById('room-name').value = name;
    document.getElementById('room-campus').value = cid;
    openModal('modal-add-room');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
