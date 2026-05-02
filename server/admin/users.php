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
        $id     = (int) ($_POST['id'] ?? 0);
        $sid    = trim($_POST['user_id']    ?? '');
        $fname  = trim($_POST['first_name'] ?? '');
        $mname  = trim($_POST['middle_name']?? '');
        $lname  = trim($_POST['last_name']  ?? '');
        $role   = $_POST['role']            ?? 'student';
        
        $nameParts = array_filter([$fname, $mname, $lname]);
        $name = implode(' ', $nameParts);
        if (!$name) $name = $sid;
        
        if (!$id || !$sid || !$fname || !$lname) {
            $flash = 'ID, first name, and last name are required.'; $flash_type='error';
        } else {
            try {
                $stmt = $pdo->prepare(
                    "UPDATE users SET 
                        user_id = :sid, first_name = :fname, middle_name = :mname, last_name = :lname,
                        name = :name, role = :role, username = :uname, 
                        email = :email, contact_number = :phone, designation = :desig, 
                        college_id = :cid, gender = :gen, year = :yr, 
                        department_id = :did, user_type = :utype, 
                        degree_id = :degid, specialization_id = :specid, 
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
                    ':cid'   => $college_id,
                    ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null,
                    ':did'   => $dept_id,
                    ':utype' => strtoupper($role),
                    ':degid' => $deg_id,
                    ':specid'=> !empty($_POST['specialization_id']) ? (int)$_POST['specialization_id'] : null,
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
        $sid    = trim($_POST['user_id']    ?? '');
        $fname  = trim($_POST['first_name'] ?? '');
        $mname  = trim($_POST['middle_name']?? '');
        $lname  = trim($_POST['last_name']  ?? '');
        $pass   = trim($_POST['password']   ?? '');
        $role   = $_POST['role']            ?? 'student';
        
        $nameParts = array_filter([$fname, $mname, $lname]);
        $name = implode(' ', $nameParts);
        if (!$name) $name = $sid;

        if (!$sid || !$fname || !$lname) {
            $flash = 'ID, first name, and last name are required.'; $flash_type='error';
        } else {
            try {
                $finalPass = !empty($pass) ? $pass : $sid;
                $hash = password_hash($finalPass, PASSWORD_BCRYPT, ['cost' => 12]);
                $college_id = !empty($_POST['college_id']) ? (int)$_POST['college_id'] : null;
                $dept_id    = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $deg_id     = !empty($_POST['degree_id']) ? (int)$_POST['degree_id'] : null;

                $stmt = $pdo->prepare(
                    "INSERT INTO users (user_id, first_name, middle_name, last_name, name, password_hash, role, username, email, contact_number, 
                                      designation, college_id, gender, year, department_id, 
                                      user_type, degree_id, specialization_id, ra_expiry_date, rank, batch, cadre, dob)
                     VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :uname, :email, :phone, :desig, :cid, :gen, :yr, :did, :utype, 
                             :degid, :specid, :raex, :rnk, :btch, :cadre, :dob)"
                );
                $stmt->execute([
                    ':sid'    => $sid, ':fname'  => $fname, ':mname'  => $mname, ':lname'  => $lname, ':name'   => $name, ':hash'   => $hash, ':role'   => $role,
                    ':uname' => trim($_POST['username'] ?? '') ?: null,
                    ':email' => trim($_POST['email'] ?? '') ?: null,
                    ':phone' => trim($_POST['contact_number'] ?? '') ?: null,
                    ':desig' => trim($_POST['designation'] ?? '') ?: null,
                    ':cid'   => $college_id, ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null, ':did'   => $dept_id, ':utype' => strtoupper($role),
                    ':degid' => $deg_id, ':specid'  => !empty($_POST['specialization_id']) ? (int)$_POST['specialization_id'] : null,
                    ':raex'  => trim($_POST['ra_expiry_date'] ?? '') ?: null, ':rnk'   => trim($_POST['rank'] ?? '') ?: null,
                    ':btch'  => trim($_POST['batch'] ?? '') ?: null, ':cadre' => trim($_POST['cadre'] ?? '') ?: null,
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
            $count = 0; $errors = 0;
            $pdo->beginTransaction();
            try {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 12) continue;
                    $data = array_combine($headers, $row);
                    $sid = trim($data['Staff Id'] ?? '');
                    if (!$sid) { $errors++; continue; }
                    
                    $role  = (stripos($data['User Type'] ?? '', 'STUDENT') !== false) ? 'student' : 'staff';
                    $hash  = password_hash($sid, PASSWORD_BCRYPT, ['cost' => 10]);
                    
                    $rawName = trim($data['Username'] ?? '');
                    if ($rawName === '-' || !$rawName) $rawName = $sid;

                    $fname = $rawName; $mname = null; $lname = null;
                    if (strpos($rawName, ',') !== false) {
                        $parts = explode(',', $rawName);
                        $lname = trim($parts[0]);
                        $rest  = trim($parts[1] ?? '');
                        $restParts = explode(' ', $rest);
                        $fname = $restParts[0];
                        $mname = $restParts[1] ?? null;
                    } elseif (strpos($rawName, ' ') !== false) {
                        $parts = explode(' ', $rawName);
                        $fname = $parts[0];
                        $lname = end($parts);
                        if (count($parts) > 2) $mname = $parts[1];
                    }
                    
                    $stmt = $pdo->prepare(
                        "INSERT INTO users (user_id, first_name, middle_name, last_name, name, password_hash, role, username, email, contact_number, 
                                          designation, gender, year, user_type, speciality, ra_expiry_date, rank, batch, cadre, dob)
                         VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :uname, :email, :phone, :desig, :gen, :yr, :utype, 
                                 :spec, :raex, :rnk, :btch, :cadre, :dob)"
                    );
                    
                    $stmt->execute([
                        ':sid'    => $sid, ':fname'  => $fname, ':mname'  => $mname, ':lname'  => $lname, ':name'   => $rawName,
                        ':hash'   => $hash, ':role'   => $role, ':uname' => ($data['Username'] !== '-' ? $data['Username'] : null),
                        ':email' => ($data['Email'] !== '-'    ? $data['Email']    : null),
                        ':phone' => trim($data['Contact Number'] ?? '') ?: null,
                        ':desig' => trim($data['Designation'] ?? '') ?: null,
                        ':gen'   => trim($data['Gender'] ?? '') ?: null,
                        ':yr'    => trim($data['Year'] ?? '') ?: null, ':utype' => strtoupper($role),
                        ':spec'  => trim($data['Speciality'] ?? '') ?: null,
                        ':raex'  => !empty($data['Ra Expiry Date']) ? date('Y-m-d', strtotime($data['Ra Expiry Date'])) : null,
                        ':rnk'   => trim($data['Rank'] ?? '') ?: null, ':btch'  => trim($data['Batch'] ?? '') ?: null,
                        ':cadre' => trim($data['Cadre'] ?? '') ?: null,
                        ':dob'   => !empty($data['Dob']) ? date('Y-m-d', strtotime($data['Dob'])) : null
                    ]);
                    $count++;
                }
                $pdo->commit();
                $flash = "Successfully onboarded {$count} users. (Note: Academic classifications must be assigned manually for CSV imports)";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Import failed: " . $e->getMessage(); $flash_type = 'error';
            }
            fclose($handle);
        }
    }

    if ($action === 'update_status') {
        $id    = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if ($id && in_array($status, ['active','inactive','suspended'], true)) {
            $pdo->prepare('UPDATE users SET status=:s WHERE id=:id')->execute([':s'=>$status,':id'=>$id]);
            $flash = 'User status updated.';
        }
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM website_logs WHERE user_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM sessions WHERE user_id = ?")->execute([$id]);
                $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
                $pdo->commit();
                $flash = "User and all associated history deleted successfully.";
            } catch (Exception $e) {
                $pdo->rollBack();
                $flash = "Deletion failed: " . $e->getMessage();
                $flash_type = 'error';
            }
        }
    }

    if ($action === 'reset_password') {
        $id   = (int) ($_POST['id'] ?? 0);
        $pass = trim($_POST['new_password'] ?? '');
        if ($id && strlen($pass) >= 6) {
            $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash=:h WHERE id=:id')->execute([':h'=>$hash,':id'=>$id]);
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

$sql = "SELECT u.*, c.name AS college_name, d.name AS department_name, deg.name AS degree_name,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id) AS total_sessions
        FROM users u
        LEFT JOIN colleges c ON u.college_id = c.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN degrees deg ON u.degree_id = deg.id
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
    <button class="btn btn-secondary" onclick="openModal('modal-bulk')"><i data-lucide="upload" style="width:18px"></i> Bulk Onboarding</button>
    <button class="btn btn-create" onclick="openModal('modal-create')"><i data-lucide="user-plus" style="width:18px"></i> Register User</button>
  </div>
</div>

<div class="card">
  <div class="card-header"><span class="card-title">Collective Users <span class="badge badge-gray"><?= count($users) ?></span></span></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Identity</th><th>Classification</th><th>College / Dept</th><th>Sessions</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="font-weight:700"><?= h($u['last_name'] ? "{$u['first_name']} " . ($u['middle_name'] ? substr($u['middle_name'],0,1).'. ' : '') . $u['last_name'] : $u['name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($u['user_id']) ?></div>
          </td>
          <td><span class="badge <?= $u['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= strtoupper($u['role']) ?></span></td>
          <td>
            <div style="font-size:12px; font-weight:600"><?= h($u['college_name'] ?? '—') ?></div>
            <div class="td-muted" style="font-size:11px"><?= h($u['department_name'] ?? '—') ?></div>
          </td>
          <td><span style="font-weight:700"><?= $u['total_sessions'] ?></span></td>
          <td><span class="badge badge-<?= $u['status']==='active'?'green':($u['status']==='suspended'?'red':'gray') ?>"><span class="badge-dot"></span><?= ucfirst($u['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:4px">
              <button class="btn btn-edit btn-sm" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Profile"><i data-lucide="edit-3" style="width:14px"></i></button>
              <button class="btn btn-warning btn-sm" onclick="resetPw(<?= $u['id'] ?>,'<?= addslashes(h($u['name'])) ?>')" title="Reset Password"><i data-lucide="key" style="width:14px"></i></button>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user and all history?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <button type="submit" class="btn btn-delete btn-sm" title="Delete"><i data-lucide="trash-2" style="width:14px"></i></button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Change status?')">
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
      <button class="btn-close" onclick="closeModal('modal-bulk')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="action" value="bulk_import">
      <div class="modal-body">
        <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px">Upload a CSV file with headers: <strong>Username,Email,Contact Number,Designation,Affiliation,Gender,Year,Department,User Type,Degree,Speciality,Staff Id,Ra Expiry Date,Rank,Batch,Cadre,Dob</strong></p>
        <div class="form-group">
          <label class="form-label">Select CSV File</label>
          <input type="file" name="csv_file" class="form-control" accept=".csv" required>
        </div>
        <div class="alert alert-info" style="font-size:12px; margin-top:12px; padding:12px; background:var(--secondary); border-radius:8px">
          Passwords will be defaulted to the user's <strong>Staff Id</strong>.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-bulk')">Cancel</button>
        <button type="submit" class="btn btn-primary">Start Onboarding</button>
      </div>
    </form>
  </div>
</div>

<!-- Register Modal -->
<div class="modal-backdrop" id="modal-create">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span class="modal-title">Register Identity</span><button class="btn-close" onclick="closeModal('modal-create')"><i data-lucide="x" style="width:16px"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Student/Staff ID *</label><input name="user_id" class="form-control" placeholder="24-0000-001" required></div>
            <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">First Name *</label><input name="first_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Middle Name</label><input name="middle_name" class="form-control"></div>
            <div class="form-group"><label class="form-label">Last Name *</label><input name="last_name" class="form-control" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">College</label><select name="college_id" id="reg-college" class="form-control" onchange="loadDepartments(this.value)"><option value="">Select College</option></select></div>
            <div class="form-group"><label class="form-label">Department</label><select name="department_id" id="reg-dept" class="form-control" onchange="loadDegrees(this.value)" disabled><option value="">Select Dept</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Degree</label><select name="degree_id" id="reg-degree" class="form-control" onchange="loadSpecializations(this.value)" disabled><option value="">Select Degree</option></select></div>
            <div class="form-group"><label class="form-label">Specialization</label><select name="specialization_id" id="reg-spec" class="form-control" disabled><option value="">Select Specialization</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Email</label><input name="email" type="email" class="form-control"></div>
            <div class="form-group"><label class="form-label">Contact Number</label><input name="contact_number" class="form-control"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Gender</label><select name="gender" class="form-control"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
            <div class="form-group"><label class="form-label">Birth Date</label><input name="dob" type="date" class="form-control"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Batch (Number Only)</label><input name="batch" type="number" class="form-control" placeholder="e.g. 3"></div>
            <div class="form-group"><label class="form-label">Cadre</label><select name="cadre" class="form-control"><option value="Undergraduate">Undergraduate</option><option value="Postgraduate">Postgraduate</option><option value="Others">Others</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Initial Password</label><input name="password" type="password" class="form-control" placeholder="Default is Staff ID"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-create')">Cancel</button><button type="submit" class="btn btn-primary">Enroll User</button></div>
    </form>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-backdrop" id="modal-edit">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span class="modal-title">Edit Identity</span><button class="btn-close" onclick="closeModal('modal-edit')"><i data-lucide="x" style="width:16px"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="edit-id">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
          <div class="form-group"><label class="form-label">Student/Staff ID *</label><input name="user_id" id="edit-user_id" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Role</label><select name="role" id="edit-role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name *</label><input name="first_name" id="edit-first_name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Middle Name</label><input name="middle_name" id="edit-middle_name" class="form-control"></div>
          <div class="form-group"><label class="form-label">Last Name *</label><input name="last_name" id="edit-last_name" class="form-control" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input name="email" id="edit-email" type="email" class="form-control"></div>
          <div class="form-group"><label class="form-label">Contact Number</label><input name="contact_number" id="edit-contact_number" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">College</label><select name="college_id" id="edit-college" class="form-control" onchange="loadDepartmentsEdit(this.value)"><option value="">Select College</option></select></div>
          <div class="form-group"><label class="form-label">Department</label><select name="department_id" id="edit-dept" class="form-control" onchange="loadDegreesEdit(this.value)" disabled><option value="">Select Dept</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Degree</label><select name="degree_id" id="edit-degree" class="form-control" onchange="loadSpecializationsEdit(this.value)" disabled><option value="">Select Degree</option></select></div>
          <div class="form-group"><label class="form-label">Specialization</label><select name="specialization_id" id="edit-spec" class="form-control" disabled><option value="">Select Specialization</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Batch</label><input name="batch" id="edit-batch" type="number" class="form-control"></div>
          <div class="form-group"><label class="form-label">Cadre</label><select name="cadre" id="edit-cadre" class="form-control"><option value="Undergraduate">Undergraduate</option><option value="Postgraduate">Postgraduate</option><option value="Others">Others</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Gender</label><select name="gender" id="edit-gender" class="form-control"><option value="">Select</option><option value="Male">Male</option><option value="Female">Female</option></select></div>
          <div class="form-group"><label class="form-label">Birth Date</label><input name="dob" id="edit-dob" type="date" class="form-control"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-edit')">Cancel</button><button type="submit" class="btn btn-primary">Save Changes</button></div>
    </form>
  </div>
</div>

<!-- Reset PW Modal -->
<div class="modal-backdrop" id="modal-reset-pw">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Reset - <span id="rp-name"></span></span><button class="btn-close" onclick="closeModal('modal-reset-pw')"><i data-lucide="x" style="width:16px"></i></button></div>
    <form method="POST"><input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" id="rp-uid">
      <div class="modal-body"><div class="form-group"><label class="form-label">New Credential</label><input name="new_password" type="password" class="form-control" required minlength="6"></div></div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-reset-pw')">Cancel</button><button type="submit" class="btn btn-primary">Update</button></div>
    </form>
  </div>
</div>

<script>
async function loadColleges() {
    const res = await fetch('../api/classifications.php?type=colleges');
    const json = await res.json();
    const regSelect = document.getElementById('reg-college');
    const editSelect = document.getElementById('edit-college');
    
    const options = '<option value="">Select College</option>' + json.data.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
    if(regSelect) regSelect.innerHTML = options;
    if(editSelect) editSelect.innerHTML = options;
}

async function loadDepartments(collegeId, targetId = 'reg-dept', degId = 'reg-degree') {
    const dSelect = document.getElementById(targetId);
    const degSelect = document.getElementById(degId);
    const specSelect = document.getElementById(targetId.includes('edit') ? 'edit-spec' : 'reg-spec');
    dSelect.innerHTML = '<option value="">Select Dept</option>';
    degSelect.innerHTML = '<option value="">Select Degree</option>';
    if(specSelect) { specSelect.innerHTML = '<option value="">Select Specialization</option>'; specSelect.disabled = true; }
    degSelect.disabled = true;
    if (!collegeId) { dSelect.disabled = true; return; }
    const res = await fetch(`../api/classifications.php?type=departments&college_id=${collegeId}`);
    const json = await res.json();
    json.data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.name;
        dSelect.appendChild(opt);
    });
    dSelect.disabled = false;
}

function loadDepartmentsEdit(cid) { return loadDepartments(cid, 'edit-dept', 'edit-degree'); }

async function loadDegrees(deptId, targetId = 'reg-degree') {
    const degSelect = document.getElementById(targetId);
    const specSelect = document.getElementById(targetId.includes('edit') ? 'edit-spec' : 'reg-spec');
    degSelect.innerHTML = '<option value="">Select Degree</option>';
    if(specSelect) { specSelect.innerHTML = '<option value="">Select Specialization</option>'; specSelect.disabled = true; }
    if (!deptId) { degSelect.disabled = true; return; }
    const res = await fetch(`../api/classifications.php?type=degrees&department_id=${deptId}`);
    const json = await res.json();
    json.data.forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.id;
        opt.textContent = d.name;
        degSelect.appendChild(opt);
    });
    degSelect.disabled = false;
}

function loadDegreesEdit(did) { return loadDegrees(did, 'edit-degree'); }

async function loadSpecializations(degreeId, targetId = 'reg-spec') {
    const sSelect = document.getElementById(targetId);
    sSelect.innerHTML = '<option value="">Select Specialization</option>';
    if (!degreeId) { sSelect.disabled = true; return; }
    const res = await fetch(`../api/classifications.php?type=specializations&degree_id=${degreeId}`);
    const json = await res.json();
    json.data.forEach(s => {
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        sSelect.appendChild(opt);
    });
    sSelect.disabled = false;
}

function loadSpecializationsEdit(sid) { return loadSpecializations(sid, 'edit-spec'); }

loadColleges();

async function editUser(u) {
    document.getElementById('edit-id').value = u.id;
    document.getElementById('edit-user_id').value = u.user_id;
    document.getElementById('edit-first_name').value = u.first_name || '';
    document.getElementById('edit-middle_name').value = u.middle_name || '';
    document.getElementById('edit-last_name').value = u.last_name || '';
    document.getElementById('edit-role').value = u.role;
    document.getElementById('edit-email').value = u.email || '';
    document.getElementById('edit-contact_number').value = u.contact_number || '';
    document.getElementById('edit-gender').value = u.gender || '';
    document.getElementById('edit-dob').value = u.dob || '';
    document.getElementById('edit-batch').value  = u.batch  || '';
    document.getElementById('edit-cadre').value  = u.cadre  || 'Undergraduate';
    document.getElementById('edit-cadre').value  = u.cadre  || 'Undergraduate';
    
    // Cascading loads for Edit
    document.getElementById('edit-college').value = u.college_id || '';
    if (u.college_id) {
        await loadDepartments(u.college_id, 'edit-dept', 'edit-degree');
        document.getElementById('edit-dept').value = u.department_id || '';
        if (u.department_id) {
            await loadDegrees(u.department_id, 'edit-degree');
            document.getElementById('edit-degree').value = u.degree_id || '';
            if (u.degree_id) {
                await loadSpecializations(u.degree_id, 'edit-spec');
                document.getElementById('edit-spec').value = u.specialization_id || '';
            }
        }
    }
    
    openModal('modal-edit');
}
function resetPw(id, name) {
    document.getElementById('rp-uid').value = id;
    document.getElementById('rp-name').textContent = name;
    openModal('modal-reset-pw');
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
