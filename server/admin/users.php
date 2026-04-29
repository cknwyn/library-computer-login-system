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

    if ($action === 'update') {
        $id    = (int) ($_POST['id'] ?? 0);
        $sid   = trim($_POST['user_id']    ?? '');
        $name  = trim($_POST['name']       ?? '');
        $role  = $_POST['role']            ?? 'student';
        
        if (!$id || !$sid || !$name) {
            $flash = 'ID and name are required.'; $flash_type='error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE users SET 
                        user_id = :sid, name = :name, role = :role, username = :uname, 
                        email = :email, contact_number = :phone, designation = :desig, 
                        affiliation = :affil, gender = :gen, year = :yr, department = :dept, 
                        user_type = :utype, degree = :deg, speciality = :spec, 
                        ra_expiry_date = :raex, rank = :rnk, batch = :btch, 
                        cadre = :cadre, dob = :dob
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':sid'   => $sid,
                    ':name'  => $name,
                    ':role'  => $role,
                    ':uname' => trim($_POST['username'] ?? '') ?: null,
                    ':email' => trim($_POST['email'] ?? '') ?: null,
                    ':phone' => trim($_POST['contact_number'] ?? '') ?: null,
                    ':desig' => trim($_POST['designation'] ?? '') ?: null,
                    ':affil' => standardize_affiliation($_POST['affiliation'] ?? null),
                    ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null,
                    ':dept'  => standardize_department($_POST['department'] ?? null),
                    ':utype' => strtoupper($role),
                    ':deg'   => trim($_POST['degree'] ?? '') ?: null,
                    ':spec'  => trim($_POST['speciality'] ?? '') ?: null,
                    ':raex'  => trim($_POST['ra_expiry_date'] ?? '') ?: null,
                    ':rnk'   => trim($_POST['rank'] ?? '') ?: null,
                    ':btch'  => trim($_POST['batch'] ?? '') ?: null,
                    ':cadre' => trim($_POST['cadre'] ?? '') ?: null,
                    ':dob'   => trim($_POST['dob'] ?? '') ?: null,
                    ':id'    => $id
                ]);
                log_activity('ADMIN_UPDATE_USER', "Updated user {$sid}", null, $admin['id']);
                $flash = "User {$sid} updated successfully.";
            } catch (PDOException $e) {
                $flash = 'Error updating user: ' . $e->getMessage();
                $flash_type = 'error';
            }
        }
    }

    if ($action === 'create') {
        $sid   = trim($_POST['user_id']    ?? '');
        $name  = trim($_POST['name']       ?? '');
        $pass  = trim($_POST['password']   ?? '');
        $role  = $_POST['role']            ?? 'student';
        
        if (!$sid || !$name || !$pass) {
            $flash = 'ID, name, and password are required.'; $flash_type='error';
        } else {
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
                $stmt = $pdo->prepare(
                    "INSERT INTO users (user_id, name, password_hash, role, username, email, contact_number, 
                                      designation, affiliation, gender, year, department, user_type, 
                                      degree, speciality, ra_expiry_date, rank, batch, cadre, dob)
                     VALUES (:sid, :name, :hash, :role, :uname, :email, :phone, :desig, :affil, :gen, :yr, :dept, :utype, 
                             :deg, :spec, :raex, :rnk, :btch, :cadre, :dob)"
                );
                $stmt->execute([
                    ':sid'   => $sid,
                    ':name'  => $name,
                    ':hash'  => $hash,
                    ':role'  => $role,
                    ':uname' => trim($_POST['username'] ?? '') ?: null,
                    ':email' => trim($_POST['email'] ?? '') ?: null,
                    ':phone' => trim($_POST['contact_number'] ?? '') ?: null,
                    ':desig' => trim($_POST['designation'] ?? '') ?: null,
                    ':affil' => standardize_affiliation($_POST['affiliation'] ?? null),
                    ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null,
                    ':dept'  => standardize_department($_POST['department'] ?? null),
                    ':utype' => strtoupper($role),
                    ':deg'   => trim($_POST['degree'] ?? '') ?: null,
                    ':spec'  => trim($_POST['speciality'] ?? '') ?: null,
                    ':raex'  => trim($_POST['ra_expiry_date'] ?? '') ?: null,
                    ':rnk'   => trim($_POST['rank'] ?? '') ?: null,
                    ':btch'  => trim($_POST['batch'] ?? '') ?: null,
                    ':cadre' => trim($_POST['cadre'] ?? '') ?: null,
                    ':dob'   => trim($_POST['dob'] ?? '') ?: null
                ]);
                log_activity('ADMIN_CREATE_USER', "Created user {$sid}", null, $admin['id']);
                $flash = "User {$sid} created successfully.";
            } catch (PDOException $e) {
                $flash = strpos($e->getMessage(),'Duplicate') !== false ? "User ID '{$sid}' already exists." : 'Error creating user: ' . $e->getMessage();
                $flash_type = 'error';
            }
        }
    }

    if ($action === 'bulk_import' && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        if ($file && ($handle = fopen($file, 'r')) !== false) {
            $headers = fgetcsv($handle);
            $count = 0;
            $errors = 0;
            
            // Expected Header: Username,Email,Contact Number,Designation,Affiliation,Gender,Year,Department,User Type,Degree,Speciality,Staff Id,Ra Expiry Date,Rank,Batch,Cadre,Dob,Creation Date
            $pdo->beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 12) continue; // Basic validation
                    $data = array_combine($headers, $row);
                    
                    $sid   = trim($data['Staff Id'] ?? '');
                    if (!$sid) { $errors++; continue; }
                    
                    $role  = (stripos($data['User Type'] ?? '', 'STUDENT') !== false) ? 'student' : 'staff';
                    $hash  = password_hash($sid, PASSWORD_BCRYPT, ['cost' => 10]); // Default PW is ID
                    
                    // Name is not in CSV, so we use Username or ID as fallback
                    $name  = trim($data['Username'] ?? '');
                    if ($name === '-' || !$name) $name = $sid;

                    $stmt = $pdo->prepare(
                        "INSERT INTO users (user_id, name, password_hash, role, username, email, contact_number, 
                                          designation, affiliation, gender, year, department, user_type, 
                                          degree, speciality, ra_expiry_date, rank, batch, cadre, dob)
                         VALUES (:sid, :name, :hash, :role, :uname, :email, :phone, :desig, :affil, :gen, :yr, :dept, :utype, 
                                 :deg, :spec, :raex, :rnk, :btch, :cadre, :dob)"
                    );
                    
                    $stmt->execute([
                        ':sid'   => $sid,
                        ':name'  => $name,
                        ':hash'  => $hash,
                        ':role'  => $role,
                        ':uname' => ($data['Username'] !== '-' ? $data['Username'] : null),
                        ':email' => ($data['Email'] !== '-'    ? $data['Email']    : null),
                        ':phone' => trim($data['Contact Number'] ?? '') ?: null,
                        ':desig' => trim($data['Designation'] ?? '') ?: null,
                        ':affil' => standardize_affiliation($data['Affiliation'] ?? null),
                        ':gen'   => trim($data['Gender'] ?? '') ?: null,
                        ':yr'    => trim($data['Year'] ?? '') ?: null,
                        ':dept'  => standardize_department($data['Department'] ?? null),
                        ':utype' => trim($data['User Type'] ?? '') ?: null,
                        ':deg'   => trim($data['Degree'] ?? '') ?: null,
                        ':spec'  => trim($data['Speciality'] ?? '') ?: null,
                        ':raex'  => !empty($data['Ra Expiry Date']) ? date('Y-m-d', strtotime($data['Ra Expiry Date'])) : null,
                        ':rnk'   => trim($data['Rank'] ?? '') ?: null,
                        ':btch'  => trim($data['Batch'] ?? '') ?: null,
                        ':cadre' => trim($data['Cadre'] ?? '') ?: null,
                        ':dob'   => !empty($data['Dob']) ? date('Y-m-d', strtotime($data['Dob'])) : null
                    ]);
                    $count++;
                }
                $pdo->commit();
                $flash = "Successfully onboarded {$count} users. (Errors: {$errors})";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Import failed: " . $e->getMessage();
                $flash_type = 'error';
            }
            fclose($handle);
        }
    }

    if ($action === 'update_status') {
        $id    = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['active','inactive','suspended'], true)) {
            $pdo->prepare('UPDATE users SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$id]);
            log_activity('ADMIN_UPDATE_USER', "Set user #{$id} status to {$status}", null, $admin['id']);
            $flash = 'User status updated.';
        }
    }

    if ($action === 'reset_password') {
        $id   = (int) ($_POST['id'] ?? 0);
        $pass = trim($_POST['new_password'] ?? '');
        if ($id && strlen($pass) >= 6) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash=:h WHERE id=:id')->execute([':h'=>$hash,':id'=>$id]);
            log_activity('ADMIN_RESET_PASSWORD', "Reset password for user #{$id}", null, $admin['id']);
            $flash = 'Password reset successfully.';
        }
    }
}

// ── Fetch users ───────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$role_f = $_GET['role']   ?? '';

$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = '(u.user_id LIKE :q OR u.name LIKE :q OR u.email LIKE :q)';
    $params[':q'] = "%{$search}%";
}
if ($role_f) { $where[] = 'u.role=:role'; $params[':role'] = $role_f; }

$sql = "SELECT u.*,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id) AS total_sessions,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id AND s.status='active') AS active_sessions
        FROM users u
        WHERE " . implode(' AND ', $where) . "
        ORDER BY u.creation_date DESC LIMIT 500";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$page = 'users';
include __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?>
  <div class="flash flash-<?= $flash_type ?> fade-in">
    <i data-lucide="<?= $flash_type==='error'?'alert-circle':'check-circle' ?>" style="width:18px"></i> <?= h($flash) ?>
  </div>
<?php endif; ?>

<div class="filter-bar">
  <form method="GET" style="display:contents">
    <div class="search-wrap">
      <span class="search-icon"><i data-lucide="search" style="width:18px"></i></span>
      <input class="form-control search-input" name="q" placeholder="Search ID, name, email..." value="<?= h($search) ?>">
    </div>
    <select name="role" class="form-control" style="max-width:140px">
      <option value="">All Roles</option>
      <option value="student" <?= $role_f==='student'?'selected':'' ?>>Student</option>
      <option value="staff"   <?= $role_f==='staff'?'selected':'' ?>>Staff</option>
    </select>
    <button type="submit" class="btn btn-outline">Filter</button>
  </form>
  <div style="display:flex; gap:8px">
    <button class="btn btn-secondary" onclick="openModal('modal-bulk')"><i data-lucide="upload"></i> Bulk Onboarding</button>
    <button class="btn btn-primary" onclick="openModal('modal-create')"><i data-lucide="user-plus"></i> Register User</button>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Collective Users <span class="badge badge-gray"><?= count($users) ?></span></span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Identity</th><th>Classification</th><th>Affiliation / Dept</th><th>Sessions</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="font-weight:700"><?= h($u['name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($u['user_id']) ?></div>
          </td>
          <td>
            <span class="badge <?= $u['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= strtoupper($u['role']) ?></span>
            <?php if ($u['user_type'] && strtoupper($u['user_type']) !== strtoupper($u['role'])): ?>
              <div class="td-muted" style="font-size:10px; margin-top:2px"><?= h($u['user_type']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <div style="font-size:12px; font-weight:600"><?= h($u['affiliation'] ?? $u['department'] ?? '—') ?></div>
            <div class="td-muted" style="font-size:11px"><?= h($u['email']) ?></div>
          </td>
          <td><span style="font-weight:700"><?= $u['total_sessions'] ?></span></td>
          <td><span class="badge badge-<?= $u['status']==='active'?'green':($u['status']==='suspended'?'red':'gray') ?>"><span class="badge-dot"></span><?= ucfirst($u['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:4px">
              <button class="btn btn-info btn-sm" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Profile"><i data-lucide="edit-3" style="width:14px"></i></button>
              <button class="btn btn-warning btn-sm" onclick="resetPw(<?= $u['id'] ?>,'<?= addslashes(h($u['name'])) ?>')" title="Reset Password"><i data-lucide="key" style="width:14px"></i></button>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <select name="status" class="form-control" style="font-size:10px; padding:2px" onchange="this.form.submit()">
                  <option value="active" <?= $u['status']==='active'?'selected':'' ?>>Active</option>
                  <option value="inactive" <?= $u['status']==='inactive'?'selected':'' ?>>Inactive</option>
                  <option value="suspended" <?= $u['status']==='suspended'?'selected':'' ?>>Suspend</option>
                </select>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Bulk Modal -->
<div class="modal-backdrop" id="modal-bulk">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Bulk Onboarding (CSV)</span>
      <button class="btn-close" onclick="closeModal('modal-bulk')"><i data-lucide="x"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="bulk_import">
      <div class="modal-body">
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px">Upload a CSV file with the following headers: <strong>Username,Email,Contact Number,Designation,Affiliation,Gender,Year,Department,User Type,Degree,Speciality,Staff Id,Ra Expiry Date,Rank,Batch,Cadre,Dob,Creation Date</strong></p>
        <div class="form-group">
          <label class="form-label">Select CSV File</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="alert alert-info" style="font-size:12px; margin-top:12px; padding:12px; background:var(--secondary); border-radius:8px">
          Passwords will be defaulted to the user's <strong>Staff Id</strong> (internal ID).
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-bulk')">Cancel</button>
        <button type="submit" class="btn btn-primary">Start Onboarding</button>
      </div>
    </form>
  </div>
</div>

<!-- Individual Modal -->
<div class="modal-backdrop" id="modal-create">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <span class="modal-title">Register Identity</span>
      <button class="btn-close" onclick="closeModal('modal-create')"><i data-lucide="x"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
          <div class="form-group"><label class="form-label">System/Staff ID *</label><input name="user_id" class="form-control" placeholder="24-0000-001" required></div>
          <div class="form-group"><label class="form-label">Full Name *</label><input name="name" class="form-control" placeholder="Identity Name" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
          <div class="form-group"><label class="form-label">Initial Password *</label><input name="password" type="password" class="form-control" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
          <div class="form-group"><label class="form-label">Username</label><input name="username" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Affiliation</label><input name="affiliation" class="form-control"></div>
          <div class="form-group"><label class="form-label">Department</label><input name="department" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Designation</label><input name="designation" class="form-control"></div>
          <div class="form-group"><label class="form-label">Gender</label><input name="gender" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Birth Date</label><input name="dob" type="date" class="form-control"></div>
          <div class="form-group"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-create')">Cancel</button>
        <button type="submit" class="btn btn-primary">Enroll User</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal" style="max-width:600px">
    <div class="modal-header">
      <span class="modal-title">Edit Identity</span>
      <button class="btn-close" onclick="closeModal('modal-edit')"><i data-lucide="x"></i></button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
          <div class="form-group"><label class="form-label">System/Staff ID *</label><input name="user_id" id="edit-user_id" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Full Name *</label><input name="name" id="edit-name" class="form-control" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Role</label><select name="role" id="edit-role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
          <div class="form-group"><label class="form-label">Username</label><input name="username" id="edit-username" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input name="email" id="edit-email" type="email" class="form-control"></div>
          <div class="form-group"><label class="form-label">Contact Number</label><input name="contact_number" id="edit-contact_number" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Affiliation</label><input name="affiliation" id="edit-affiliation" class="form-control"></div>
          <div class="form-group"><label class="form-label">Department</label><input name="department" id="edit-department" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Designation</label><input name="designation" id="edit-designation" class="form-control"></div>
          <div class="form-group"><label class="form-label">Gender</label><input name="gender" id="edit-gender" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Birth Date</label><input name="dob" id="edit-dob" type="date" class="form-control"></div>
          <div class="form-group"><label class="form-label">Year/Level</label><input name="year" id="edit-year" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-edit')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<div class="modal-backdrop" id="modal-reset-pw">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Reset - <span id="rp-name"></span></span><button class="btn-close" onclick="closeModal('modal-reset-pw')"><i data-lucide="x"></i></button></div>
    <form method="POST"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" id="rp-uid">
      <div class="modal-body"><div class="form-group"><label class="form-label">New Credential</label><input name="new_password" type="password" class="form-control" required></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-reset-pw')">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>

<script>
function resetPw(id, name) { document.getElementById('rp-uid').value = id; document.getElementById('rp-name').textContent = name; openModal('modal-reset-pw'); }
function editUser(u) {
    document.getElementById('edit-id').value = u.id;
    document.getElementById('edit-user_id').value = u.user_id;
    document.getElementById('edit-name').value = u.name;
    document.getElementById('edit-role').value = u.role;
    document.getElementById('edit-username').value = u.username || '';
    document.getElementById('edit-email').value = u.email || '';
    document.getElementById('edit-contact_number').value = u.contact_number || '';
    document.getElementById('edit-affiliation').value = u.affiliation || '';
    document.getElementById('edit-department').value = u.department || '';
    document.getElementById('edit-designation').value = u.designation || '';
    document.getElementById('edit-gender').value = u.gender || '';
    document.getElementById('edit-dob').value = u.dob || '';
    document.getElementById('edit-year').value = u.year || '';
    openModal('modal-edit');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
