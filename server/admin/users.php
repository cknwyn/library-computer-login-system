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
                    ':affil' => trim($_POST['affiliation'] ?? '') ?: null,
                    ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null,
                    ':dept'  => trim($_POST['department'] ?? '') ?: null,
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

                $affil = null; $dept = null; $deg = null;
                if ($college_id) {
                    $stmt = $pdo->prepare("SELECT name FROM colleges WHERE id = ?");
                    $stmt->execute([$college_id]);
                    $affil = $stmt->fetchColumn();
                }
                if ($dept_id) {
                    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                    $stmt->execute([$dept_id]);
                    $dept = $stmt->fetchColumn();
                }
                if ($deg_id) {
                    $stmt = $pdo->prepare("SELECT name FROM degrees WHERE id = ?");
                    $stmt->execute([$deg_id]);
                    $deg = $stmt->fetchColumn();
                }

                $stmt = $pdo->prepare(
                    "INSERT INTO users (user_id, first_name, middle_name, last_name, name, password_hash, role, username, email, contact_number, 
                                      designation, affiliation, college_id, gender, year, department, department_id, 
                                      user_type, degree, degree_id, speciality, ra_expiry_date, rank, batch, cadre, dob)
                     VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :uname, :email, :phone, :desig, :affil, :cid, :gen, :yr, :dept, :did, :utype, 
                             :deg, :degid, :spec, :raex, :rnk, :btch, :cadre, :dob)"
                );
                $stmt->execute([
                    ':sid'    => $sid,
                    ':fname'  => $fname,
                    ':mname'  => $mname,
                    ':lname'  => $lname,
                    ':name'   => $name,
                    ':hash'   => $hash,
                    ':role'   => $role,
                    ':uname' => trim($_POST['username'] ?? '') ?: null,
                    ':email' => trim($_POST['email'] ?? '') ?: null,
                    ':phone' => trim($_POST['contact_number'] ?? '') ?: null,
                    ':desig' => trim($_POST['designation'] ?? '') ?: null,
                    ':affil' => $affil,
                    ':cid'   => $college_id,
                    ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null,
                    ':dept'  => $dept,
                    ':did'   => $dept_id,
                    ':utype' => strtoupper($role),
                    ':deg'   => $deg,
                    ':degid' => $deg_id,
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
                    
                    $affil = trim($data['Affiliation'] ?? '') ?: null;
                    $dept  = trim($data['Department'] ?? '') ?: null;
                    $deg   = trim($data['Degree'] ?? '') ?: null;

                    $stmt = $pdo->prepare(
                        "INSERT INTO users (user_id, first_name, middle_name, last_name, name, password_hash, role, username, email, contact_number, 
                                          designation, affiliation, gender, year, department, user_type, degree, speciality, ra_expiry_date, rank, batch, cadre, dob)
                         VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :uname, :email, :phone, :desig, :affil, :gen, :yr, :dept, :utype, 
                                 :deg, :spec, :raex, :rnk, :btch, :cadre, :dob)"
                    );
                    
                    $stmt->execute([
                        ':sid'    => $sid, ':fname'  => $fname, ':mname'  => $mname, ':lname'  => $lname, ':name'   => $rawName,
                        ':hash'   => $hash, ':role'   => $role, ':uname' => ($data['Username'] !== '-' ? $data['Username'] : null),
                        ':email' => ($data['Email'] !== '-'    ? $data['Email']    : null),
                        ':phone' => trim($data['Contact Number'] ?? '') ?: null,
                        ':desig' => trim($data['Designation'] ?? '') ?: null,
                        ':affil' => $affil, ':gen'   => trim($data['Gender'] ?? '') ?: null,
                        ':yr'    => trim($data['Year'] ?? '') ?: null, ':dept'  => $dept, ':utype' => strtoupper($role),
                        ':deg'   => $deg, ':spec'  => trim($data['Speciality'] ?? '') ?: null,
                        ':raex'  => !empty($data['Ra Expiry Date']) ? date('Y-m-d', strtotime($data['Ra Expiry Date'])) : null,
                        ':rnk'   => trim($data['Rank'] ?? '') ?: null, ':btch'  => trim($data['Batch'] ?? '') ?: null,
                        ':cadre' => trim($data['Cadre'] ?? '') ?: null,
                        ':dob'   => !empty($data['Dob']) ? date('Y-m-d', strtotime($data['Dob'])) : null
                    ]);
                    $count++;
                }
                $pdo->commit();
                $flash = "Successfully onboarded {$count} users. (Errors: {$errors})";
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
    <button class="btn btn-secondary" onclick="openModal('modal-bulk')"><i data-lucide="upload"></i> Bulk Onboarding</button>
    <button class="btn btn-primary" onclick="openModal('modal-create')"><i data-lucide="user-plus"></i> Register User</button>
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
            <div style="font-weight:700">
                <?php 
                if ($u['last_name']) {
                    echo h($u['last_name']) . ', ' . h($u['first_name']);
                    if ($u['middle_name']) echo ' ' . h(substr($u['middle_name'], 0, 1)) . '.';
                } else {
                    echo h($u['name']);
                }
                ?>
            </div>
            <div class="td-muted mono" style="font-size:11px"><?= h($u['user_id']) ?></div>
          </td>
          <td><span class="badge <?= $u['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= strtoupper($u['role']) ?></span></td>
          <td>
            <div style="font-size:12px; font-weight:600"><?= h($u['college_name'] ?? $u['affiliation'] ?? '—') ?></div>
            <div class="td-muted" style="font-size:11px"><?= h($u['department_name'] ?? $u['department'] ?? '—') ?></div>
          </td>
          <td><span style="font-weight:700"><?= $u['total_sessions'] ?></span></td>
          <td><span class="badge badge-<?= $u['status']==='active'?'green':($u['status']==='suspended'?'red':'gray') ?>"><span class="badge-dot"></span><?= ucfirst($u['status']) ?></span></td>
          <td>
            <div style="display:flex;gap:4px">
              <button class="btn btn-info btn-sm" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Profile"><i data-lucide="edit-3" style="width:14px"></i></button>
              <button class="btn btn-warning btn-sm" onclick="resetPw(<?= $u['id'] ?>,'<?= addslashes(h($u['name'])) ?>')" title="Reset Password"><i data-lucide="key" style="width:14px"></i></button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modals (Add/Edit/Bulk/Reset) -->
<div class="modal-backdrop" id="modal-create">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span class="modal-title">Register Identity</span><button class="btn-close" onclick="closeModal('modal-create')"><i data-lucide="x"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
            <div class="form-group"><label class="form-label">System/Staff ID *</label><input name="user_id" class="form-control" required></div>
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
            <div class="form-group"><label class="form-label">Degree</label><select name="degree_id" id="reg-degree" class="form-control" disabled><option value="">Select Degree</option></select></div>
            <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline" onclick="closeModal('modal-create')">Cancel</button><button type="submit" class="btn btn-primary">Enroll User</button></div>
    </form>
  </div>
</div>

<script>
async function loadColleges() {
    const res = await fetch('../api/classifications.php?type=colleges');
    const json = await res.json();
    const select = document.getElementById('reg-college');
    select.innerHTML = '<option value="">Select College</option>';
    json.data.forEach(c => {
        const opt = document.createElement('option');
        opt.value = c.id;
        opt.textContent = c.name;
        select.appendChild(opt);
    });
}
async function loadDepartments(collegeId) {
    const dSelect = document.getElementById('reg-dept');
    dSelect.innerHTML = '<option value="">Select Dept</option>';
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
async function loadDegrees(deptId) {
    const degSelect = document.getElementById('reg-degree');
    degSelect.innerHTML = '<option value="">Select Degree</option>';
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
loadColleges();

function editUser(u) {
    // Basic editing logic
    alert('Editing logic for ' + u.name);
}
function resetPw(id, name) {
    const pass = prompt('Enter new password for ' + name);
    if (pass) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `<input type="hidden" name="action" value="reset_password"><input type="hidden" name="id" value="${id}"><input type="hidden" name="new_password" value="${pass}">`;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
