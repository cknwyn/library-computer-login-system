<?php
// ============================================================
// Admin — Users Management — /admin/users.php
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

    if ($action === 'create') {
        $uid   = trim($_POST['user_id']    ?? '');
        $name  = trim($_POST['name']       ?? '');
        $pass  = trim($_POST['password']   ?? '');
        $role  = $_POST['role']            ?? 'student';
        $dept  = trim($_POST['department'] ?? '');
        $email = trim($_POST['email']      ?? '');

        if (!$uid || !$name || !$pass) {
            $flash = 'ID, name, and password are required.'; $flash_type='error';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    'INSERT INTO users (user_id, name, password_hash, role, department, email)
                     VALUES (:uid, :name, :hash, :role, :dept, :email)'
                );
                $stmt->execute([':uid'=>$uid,':name'=>$name,':hash'=>$hash,
                                ':role'=>$role,':dept'=>$dept?:null,':email'=>$email?:null]);
                log_activity('ADMIN_CREATE_USER', "Created user {$uid}", null, $admin['id']);
                $flash = "User {$uid} created successfully.";
            } catch (PDOException $e) {
                $flash = strpos($e->getMessage(),'Duplicate') !== false
                    ? "User ID '{$uid}' already exists." : 'Error creating user.';
                $flash_type = 'error';
            }
        }
    }

    if ($action === 'update_status') {
        $uid    = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($uid && in_array($status, ['active','inactive','suspended'], true)) {
            $pdo->prepare('UPDATE users SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$uid]);
            log_activity('ADMIN_UPDATE_USER', "Set user #{$uid} status to {$status}", null, $admin['id']);
            $flash = 'User status updated.';
        }
    }

    if ($action === 'reset_password') {
        $uid  = (int) ($_POST['id'] ?? 0);
        $pass = trim($_POST['new_password'] ?? '');
        if ($uid && strlen($pass) >= 6) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash=:h WHERE id=:id')->execute([':h'=>$hash,':id'=>$uid]);
            log_activity('ADMIN_RESET_PASSWORD', "Reset password for user #{$uid}", null, $admin['id']);
            $flash = 'Password reset successfully.';
        } else {
            $flash = 'Password must be at least 6 characters.'; $flash_type='error';
        }
    }
}

// ── Fetch users ───────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$role_f = $_GET['role']   ?? '';
$status_f = $_GET['status'] ?? '';

$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(u.user_id LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = "%{$search}%";
}
if ($role_f)   { $where[] = 'u.role=:role';     $params[':role'] = $role_f; }
if ($status_f) { $where[] = 'u.status=:status'; $params[':status'] = $status_f; }

$sql = "SELECT u.*,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id) AS total_sessions,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id AND s.status='active') AS active_sessions
        FROM users u
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page = 'users';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
  <div class="flash flash-<?= $flash_type === 'error' ? 'error' : 'success' ?> fade-in">
    <i data-lucide="<?= $flash_type === 'error' ? 'alert-circle' : 'check-circle' ?>" style="width:18px"></i> <?= h($flash) ?>
  </div>
<?php endif; ?>

<!-- Actions Bar -->
<div class="filter-bar">
  <form method="GET" style="display:contents">
    <div class="search-wrap">
      <span class="search-icon"><i data-lucide="search" style="width:18px"></i></span>
      <input class="form-control search-input" name="q" placeholder="Look up identity or department..." value="<?= h($search) ?>">
    </div>
    <select name="role" class="form-control" style="max-width:140px">
      <option value="">All Roles</option>
      <option value="student" <?= $role_f==='student'?'selected':'' ?>>Student</option>
      <option value="staff"   <?= $role_f==='staff'?'selected':'' ?>>Staff</option>
    </select>
    <select name="status" class="form-control" style="max-width:140px">
      <option value="">All Status</option>
      <option value="active"    <?= $status_f==='active'?'selected':'' ?>>Active</option>
      <option value="inactive"  <?= $status_f==='inactive'?'selected':'' ?>>Inactive</option>
      <option value="suspended" <?= $status_f==='suspended'?'selected':'' ?>>Suspended</option>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
  </form>
  <button class="btn btn-primary" onclick="openModal('modal-create')">
    <i data-lucide="user-plus" style="width:18px"></i> Register User
  </button>
</div>

<!-- Users Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title">Collective Users <span class="badge badge-gray" style="margin-left:8px"><?= count($users) ?></span></span>
  </div>
  <?php if (empty($users)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="users-2"></i></div>
      <p>No identities found in this directory.</p>
    </div>
  <?php else: ?>
  <div class="table-wrap">
    <table id="users-table">
      <thead>
        <tr><th>Identity</th><th>Role</th><th>Dept / Email</th><th>Engagement</th><th>Status</th><th>Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="font-weight:700"><?= h($u['name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($u['user_id']) ?></div>
          </td>
          <td><span class="badge <?= $u['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= ucfirst($u['role']) ?></span></td>
          <td>
            <div style="font-size:13px; font-weight: 500"><?= h($u['department'] ?? '—') ?></div>
            <div class="td-muted" style="font-size:11px"><?= h($u['email'] ?? 'No email') ?></div>
          </td>
          <td>
            <div style="display:flex; align-items: center; gap: 8px">
              <span style="font-weight:700"><?= $u['total_sessions'] ?></span>
              <?php if ($u['active_sessions'] > 0): ?>
                <span class="badge badge-green" style="font-size:9px">Online</span>
              <?php endif; ?>
            </div>
          </td>
          <td><?php
            $b = ['active'=>'badge-green','inactive'=>'badge-gray','suspended'=>'badge-red'];
            echo '<span class="badge '.($b[$u['status']]??'badge-gray').'"><span class="badge-dot"></span>'.ucfirst($u['status']).'</span>';
          ?></td>
          <td>
            <div style="display:flex;gap:8px;align-items:center">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <select name="status" class="form-control" style="padding:4px 8px;font-size:11px;max-width:105px;background:white;border:1px solid var(--border)" onchange="this.form.submit()">
                  <option value="active"    <?= $u['status']==='active'?'selected':'' ?>>Active</option>
                  <option value="inactive"  <?= $u['status']==='inactive'?'selected':'' ?>>Inactive</option>
                  <option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Suspend</option>
                </select>
              </form>
              <button class="btn btn-outline btn-sm" style="padding: 5px" 
                onclick="document.getElementById('rp-uid').value=<?= $u['id'] ?>;document.getElementById('rp-name').textContent='<?= addslashes(h($u['name'])) ?>';openModal('modal-reset-pw')"
                title="Reset Password">
                <i data-lucide="key" style="width:14px"></i>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- Create User Modal -->
<div class="modal-backdrop" id="modal-create">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Register Identity</span>
      <button class="btn-close" onclick="closeModal('modal-create')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">System ID</label>
            <input name="user_id" class="form-control" placeholder="2024-001" required>
          </div>
          <div class="form-group">
            <label class="form-label">Classification</label>
            <select name="role" class="form-control">
              <option value="student">Student</option>
              <option value="staff">Staff</option>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <input name="name" class="form-control" placeholder="Identity name..." required>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Department</label>
            <input name="department" class="form-control" placeholder="e.g. CAS">
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input name="email" type="email" class="form-control" placeholder="name@edu.ph">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Initial Password</label>
          <input name="password" type="password" class="form-control" placeholder="Min. 8 characters" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-create')">Cancel</button>
        <button type="submit" class="btn btn-primary">Enroll User</button>
      </div>
    </form>
  </div>
</div>

<!-- Reset Password Modal -->
<div class="modal-backdrop" id="modal-reset-pw">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Reset - <span id="rp-name"></span></span>
      <button class="btn-close" onclick="closeModal('modal-reset-pw')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="reset_password">
      <input type="hidden" name="id" id="rp-uid">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">New Credential</label>
          <input name="new_password" type="password" class="form-control" placeholder="Min. 6 characters" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-reset-pw')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Password</button>
      </div>
    </form>
  </div>
</div>


<?php include __DIR__ . '/partials/footer.php'; ?>
