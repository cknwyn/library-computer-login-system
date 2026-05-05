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
                $college_id = !empty($_POST['college_id']) ? (int)$_POST['college_id'] : null;
                $dept_id    = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
                $deg_id     = !empty($_POST['degree_id']) ? (int)$_POST['degree_id'] : null;

                $stmt = $pdo->prepare(
                    "UPDATE users SET 
                        user_id = :sid, first_name = :fname, middle_name = :mname, last_name = :lname,
                        name = :name, role = :role, username = :uname, 
                        email = :email, contact_number = :phone, designation = :desig, 
                        college_id = :cid, gender = :gen, year = :yr, 
                        department_id = :did, 
                        degree_id = :degid, specialization_id = :specid, 
                        ra_expiry_date = :raex, rank = :rnk, batch = :btch, 
                        cadre = :cadre, dob = :dob
                     WHERE id = :id"
                );
                $stmt->execute([
                    ':sid'   => $sid,
                    ':fname' => $fname,
                    ':mname' => $mname,
                    ':lname' => $lname,
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
                                      degree_id, specialization_id, ra_expiry_date, rank, batch, cadre, dob)
                     VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :uname, :email, :phone, :desig, :cid, :gen, :yr, :did, 
                             :degid, :specid, :raex, :rnk, :btch, :cadre, :dob)"
                );
                $stmt->execute([
                    ':sid'    => $sid, ':fname'  => $fname, ':mname'  => $mname, ':lname'  => $lname, ':name'   => $name, ':hash'   => $hash, ':role'   => $role,
                    ':uname' => trim($_POST['username'] ?? '') ?: null,
                    ':email' => trim($_POST['email'] ?? '') ?: null,
                    ':phone' => trim($_POST['contact_number'] ?? '') ?: null,
                    ':desig' => trim($_POST['designation'] ?? '') ?: null,
                    ':cid'   => $college_id, ':gen'   => trim($_POST['gender'] ?? '') ?: null,
                    ':yr'    => trim($_POST['year'] ?? '') ?: null, ':did'   => $dept_id,
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
            $raw_headers = fgetcsv($handle);
            if (!$raw_headers) { $flash = "Empty CSV file."; $flash_type = "error"; }
            else {
                // Normalize headers for foolproof matching
                $header_map = [];
                $norm = function($s) { return strtolower(preg_replace('/[^a-z0-9]/', '', $s)); };
                foreach ($raw_headers as $idx => $h) {
                    $header_map[$norm($h)] = $idx;
                }

                $get_val = function($row, $keys) use ($header_map, $norm) {
                    foreach ($keys as $k) {
                        $n = $norm($k);
                        if (isset($header_map[$n])) return trim($row[$header_map[$n]] ?? '');
                    }
                    return null;
                };

                $count = 0; $errors = 0; $error_details = []; $row_num = 1;
                $pdo->beginTransaction();
                $cache_coll = []; $cache_dept = []; $cache_deg = []; $cache_spec = [];

                try {
                    while (($row = fgetcsv($handle)) !== false) {
                        $row_num++;
                        if (count($row) < 2) continue; 
                        
                        // Extract with fuzzy matching
                        $sid   = $get_val($row, ['Staff Id', 'User ID', 'ID', 'Student ID']);
                        $rawName = $get_val($row, ['Username', 'Name', 'Full Name', 'Display Name']);
                        if (!$sid) { 
                            $errors++; 
                            $error_details[] = "Row {$row_num}: Missing Staff/Student ID";
                            continue; 
                        }
                        
                        $role  = (stripos($get_val($row, ['User Type', 'Role', 'Type']) ?? '', 'STUDENT') !== false) ? 'student' : 'staff';
                        $email = $get_val($row, ['Email', 'Email Address']);
                        $phone = $get_val($row, ['Contact Number', 'Phone', 'Mobile', 'Contact']);
                        $desig = $get_val($row, ['Designation', 'Position']);
                        $gen   = $get_val($row, ['Gender', 'Sex']);
                        $yr    = $get_val($row, ['Year', 'Year Level']);
                        $rnk   = $get_val($row, ['Rank', 'Level', 'Year Level']);
                        $btch  = $get_val($row, ['Batch']);
                        $cadre = $get_val($row, ['Cadre']);
                        $dob_raw = $get_val($row, ['Dob', 'Birth Date', 'Birthday']);
                        
                        // --- Validations & Standardization ---
                        
                        // 1. Email Validation
                        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $email = null; }

                        // 2. Gender Standardization
                        if ($gen) {
                            $gen_low = strtolower($gen);
                            if (in_array($gen_low, ['m', 'male', 'boy', 'man'])) $gen = 'Male';
                            elseif (in_array($gen_low, ['f', 'female', 'girl', 'woman'])) $gen = 'Female';
                            else $gen = null;
                        }

                        // 3. Numeric Cleanup (Batch & Year)
                        $btch = $btch ? preg_replace('/[^0-9]/', '', $btch) : null;
                        $yr   = $yr   ? preg_replace('/[^0-9]/', '', $yr) : null;

                        // 4. Date Validation
                        $val_date = function($d) {
                            if (!$d || $d === '-' || strlen($d) < 5) return null;
                            $ts = strtotime($d);
                            return ($ts && $ts > 100000000) ? date('Y-m-d', $ts) : null;
                        };
                        $dob = $val_date($dob_raw);
                        $ra_exp = $val_date($get_val($row, ['Ra Expiry Date', 'Expiry']));
                        
                        $hash  = password_hash($sid, PASSWORD_BCRYPT, ['cost' => 10]);
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

                        // --- Academic Resolution ---
                        $cid = null; $did = null; $degid = null; $specid = null;

                        $coll_name = $get_val($row, ['Affiliation', 'College', 'Unit', 'Academic Unit']);
                        if ($coll_name && $coll_name !== '-') {
                            $clean_coll = preg_replace('/^College of /i', '', $coll_name);
                            if (!isset($cache_coll[$coll_name])) {
                                $st = $pdo->prepare("SELECT id FROM colleges WHERE name LIKE ? OR code = ? OR ? LIKE CONCAT('%', name, '%') LIMIT 1");
                                $st->execute(["%$clean_coll%", $coll_name, $coll_name]);
                                $cache_coll[$coll_name] = $st->fetchColumn() ?: null;
                            }
                            $cid = $cache_coll[$coll_name];
                        }

                        $dept_name = $get_val($row, ['Department', 'Dept']);
                        if ($dept_name && $dept_name !== '-') {
                            $clean_dept = preg_replace('/^Department of /i', '', $dept_name);
                            if (!isset($cache_dept[$dept_name])) {
                                $st = $pdo->prepare("SELECT id FROM departments WHERE name LIKE ? OR name LIKE ? OR ? LIKE CONCAT('%', name, '%') LIMIT 1");
                                $st->execute(["%$dept_name%", "%$clean_dept%", $dept_name]);
                                $cache_dept[$dept_name] = $st->fetchColumn() ?: null;
                            }
                            $did = $cache_dept[$dept_name];
                        }

                        $deg_name = $get_val($row, ['Degree', 'Program']);
                        if ($deg_name && $deg_name !== '-') {
                            $clean_deg = preg_replace('/^(BS in |AB in |Bachelor of Science in |Bachelor of Arts in )/i', '', $deg_name);
                            if (!isset($cache_deg[$deg_name])) {
                                $st = $pdo->prepare("SELECT id FROM degrees WHERE name LIKE ? OR name LIKE ? OR ? LIKE CONCAT('%', name, '%') LIMIT 1");
                                $st->execute(["%$deg_name%", "%$clean_deg%", $deg_name]);
                                $cache_deg[$deg_name] = $st->fetchColumn() ?: null;
                            }
                            $degid = $cache_deg[$deg_name];
                        }

                        $spec_name = $get_val($row, ['Speciality', 'Specialization', 'Track']);
                        if ($spec_name && $spec_name !== '-') {
                            if (!isset($cache_spec[$spec_name])) {
                                $st = $pdo->prepare("SELECT id FROM specializations WHERE name LIKE ? OR ? LIKE CONCAT('%', name, '%') LIMIT 1");
                                $st->execute(["%$spec_name%", $spec_name]);
                                $cache_spec[$spec_name] = $st->fetchColumn() ?: null;
                            }
                            $specid = $cache_spec[$spec_name];
                        }
                        
                        $stmt = $pdo->prepare(
                            "INSERT INTO users (user_id, first_name, middle_name, last_name, name, password_hash, role, email, contact_number, 
                                              designation, gender, year, college_id, department_id, degree_id, specialization_id, ra_expiry_date, rank, batch, cadre, dob)
                             VALUES (:sid, :fname, :mname, :lname, :name, :hash, :role, :email, :phone, :desig, :gen, :yr, :cid, :did, :degid,
                                     :specid, :raex, :rnk, :btch, :cadre, :dob)
                             ON DUPLICATE KEY UPDATE
                                first_name = VALUES(first_name), middle_name = VALUES(middle_name), last_name = VALUES(last_name),
                                name = VALUES(name), role = VALUES(role), email = VALUES(email), contact_number = VALUES(contact_number),
                                designation = VALUES(designation), gender = VALUES(gender), year = VALUES(year),
                                college_id = VALUES(college_id), department_id = VALUES(department_id), 
                                degree_id = VALUES(degree_id), specialization_id = VALUES(specialization_id),
                                rank = VALUES(rank), batch = VALUES(batch), cadre = VALUES(cadre), dob = VALUES(dob)"
                        );
                        
                        try {
                            $stmt->execute([
                                ':sid'    => $sid, ':fname'  => $fname, ':mname'  => $mname, ':lname'  => $lname, ':name'   => $rawName,
                                ':hash'   => $hash, ':role'   => $role, 
                                ':email'  => $email,
                                ':phone'  => $phone ?: null,
                                ':desig'  => $desig ?: null,
                                ':gen'    => $gen ?: null,
                                ':yr'     => $yr ?: null,
                                ':cid'    => $cid, ':did' => $did, ':degid' => $degid, ':specid' => $specid,
                                ':raex'   => $ra_exp,
                                ':rnk'    => $rnk ?: null, ':btch'  => $btch ?: null,
                                ':cadre'  => $cadre ?: null,
                                ':dob'    => $dob
                            ]);
                            $count++;
                        } catch (PDOException $e) { 
                            $errors++; 
                            $error_details[] = "Row {$row_num}: Database error ({$e->getCode()})";
                        }

                    }
                    $pdo->commit();
                    $flash = "Successfully onboarded {$count} users.";
                    if ($errors > 0) {
                        $flash .= " Found {$errors} issues: " . implode(" | ", array_slice($error_details, 0, 3));
                        if (count($error_details) > 3) $flash .= " ...and " . (count($error_details)-3) . " more.";
                        $flash_type = 'warning';
                    }
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $flash = 'Import failed: ' . $e->getMessage();
                    $flash_type = 'error';
                }
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
    $where[] = '(u.user_id LIKE :q1 OR u.name LIKE :q2 OR u.email LIKE :q3 OR c.name LIKE :q4 OR d.name LIKE :q5 OR deg.name LIKE :q6 OR u.first_name LIKE :q7 OR u.last_name LIKE :q8)';
    $term = "%{$search}%";
    $params[':q1'] = $term; $params[':q2'] = $term; $params[':q3'] = $term; $params[':q4'] = $term;
    $params[':q5'] = $term; $params[':q6'] = $term; $params[':q7'] = $term; $params[':q8'] = $term;
}
if ($role_f) { $where[] = 'u.role=:role'; $params[':role'] = $role_f; }

$sql = "SELECT u.*, c.name AS college_name, d.name AS department_name, 
               deg.name AS degree_name, spec.name AS specialization_name,
               (SELECT COUNT(*) FROM sessions s WHERE s.user_id=u.id) AS total_sessions
        FROM users u
        LEFT JOIN colleges c ON u.college_id = c.id
        LEFT JOIN departments d ON u.department_id = d.id
        LEFT JOIN degrees deg ON u.degree_id = deg.id
        LEFT JOIN specializations spec ON u.specialization_id = spec.id
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
      <input class="form-control search-input" name="q" id="search-q" placeholder="Search ID, name, email, college..." value="<?= h($search) ?>">
      <?php if ($search): ?>
        <a href="?" class="search-clear" title="Clear Search"><i data-lucide="x" style="width:14px"></i></a>
      <?php endif; ?>
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
  <div class="table-wrap" style="max-height: 600px; overflow-y: auto;">
    <table>
      <thead><tr><th>Identity</th><th>Classification</th><th>Academic Profile</th><th>Sessions</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td>
            <div style="font-weight:700"><?= h($u['last_name'] ? "{$u['first_name']} " . ($u['middle_name'] ? substr($u['middle_name'],0,1).'. ' : '') . $u['last_name'] : $u['name']) ?></div>
            <div class="td-muted mono" style="font-size:11px"><?= h($u['user_id']) ?></div>
          </td>
          <td>
            <span class="badge <?= $u['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= strtoupper($u['role']) ?></span>
            <div class="td-muted" style="font-size:10px; margin-top:2px"><?= h($u['rank'] ?? '') ?></div>
          </td>
          <td>
            <div style="font-size:12px; font-weight:700; color:#334155"><?= h($u['college_name'] ?? '—') ?></div>
            <div class="td-muted" style="font-size:11px; line-height:1.3">
                <?= h($u['department_name'] ?? '') ?><?= $u['degree_name'] ? " &rsaquo; " . h($u['degree_name']) : '' ?>
                <?php if ($u['specialization_name']): ?>
                    <div style="font-style:italic; color:#64748b; margin-top:1px">Track: <?= h($u['specialization_name']) ?></div>
                <?php endif; ?>
            </div>
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
  <div class="modal" style="max-width: 650px;">
    <div class="modal-header">
      <span class="modal-title">Bulk Onboarding</span>
      <button class="btn-close" onclick="closeModal('modal-bulk')"><i data-lucide="x" style="width:16px"></i></button>
    </div>
    <form method="POST" enctype="multipart/form-data" id="bulkOnboardingForm">
      <input type="hidden" name="action" value="bulk_import">
      <div class="modal-body">
        <div class="onboarding-desc">
          Quickly onboard multiple users by uploading a CSV file. This feature streamlines the registration process, allowing you to onboard a large number of users at once.
        </div>
        <div class="onboarding-note">
          Note: A maximum of 5000 users can be added at a time by uploading a .csv file. In case of non-english data, please upload a UTF-8 encoded .csv file only.
        </div>

        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <span style="font-size: 13px; font-weight: 700; color: #1e293b;">UPLOAD FILE</span>
          <a href="<?= ASSETS_URL ?>/templates/user_import_template.csv" download class="link" style="font-size: 13px; color: #3b82f6; text-decoration: none;">Download Sample CSV</a>
        </div>

        <div class="drop-zone" id="dropZone" onclick="document.getElementById('csv_file').click()">
          <div class="drop-zone-icon">
            <i data-lucide="upload-cloud" style="width: 48px; height: 48px;"></i>
          </div>
          <div class="drop-zone-text">Choose a file or drag & drop it here</div>
          <div class="drop-zone-subtext">Maximum 5,000 users at a time<br>CSV format only</div>
          <button type="button" class="btn-browse">Browse File</button>
          <input type="file" name="csv_file" id="csv_file" hidden accept=".csv" required onchange="handleFileSelect(this)">
        </div>

        <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-top: 24px;">ONBOARDING DEFAULTS</div>
        <div class="group-details-grid">
          <div class="custom-select-wrap">
            <div class="custom-select-label">Default Role</div>
            <select name="default_role" class="custom-select">
              <option value="student">Student</option>
              <option value="staff">Staff / Faculty</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="justify-content: center; padding-bottom: 32px; border-top: none;">
        <button type="submit" class="btn-save-onboarding">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('csv_file');

// Handle Drag Events
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
    }, false);
});

['dragenter', 'dragover'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.add('active'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropZone.addEventListener(eventName, () => dropZone.classList.remove('active'), false);
});

dropZone.addEventListener('drop', e => {
    const dt = e.dataTransfer;
    const files = dt.files;
    fileInput.files = files;
    handleFileSelect(fileInput);
});

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const fileName = input.files[0].name;
        const dropZoneText = dropZone.querySelector('.drop-zone-text');
        dropZoneText.innerText = "Selected: " + fileName;
        dropZoneText.style.color = "#059669"; // Success green
    }
}
</script>

<!-- Register Modal -->
<div class="modal-backdrop" id="modal-create">
  <div class="modal" style="max-width:600px">
    <div class="modal-header"><span class="modal-title">Register Identity</span><button class="btn-close" onclick="closeModal('modal-create')"><i data-lucide="x" style="width:16px"></i></button></div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body" style="max-height:70vh; overflow-y:auto">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Student/Staff ID <span class="required-star">*</span></label><input name="user_id" class="form-control" placeholder="24-0000-001" required></div>
            <div class="form-group"><label class="form-label">Role</label><select name="role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">First Name <span class="required-star">*</span></label><input name="first_name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Middle Name</label><input name="middle_name" class="form-control"></div>
            <div class="form-group"><label class="form-label">Last Name <span class="required-star">*</span></label><input name="last_name" class="form-control" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">College / Academic Unit</label><select name="college_id" id="reg-college" class="form-control" onchange="loadDepartments(this.value)"><option value="">Select Unit</option></select></div>
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
            <div class="form-group"><label class="form-label">Rank / Year Level</label><select name="rank" class="form-control">
                <option value="">Select Rank</option>
                <optgroup label="Elementary">
                  <option value="Grade 1">Grade 1</option>
                  <option value="Grade 2">Grade 2</option>
                  <option value="Grade 3">Grade 3</option>
                  <option value="Grade 4">Grade 4</option>
                  <option value="Grade 5">Grade 5</option>
                  <option value="Grade 6">Grade 6</option>
                </optgroup>
                <optgroup label="Secondary">
                  <option value="Grade 7">Grade 7</option>
                  <option value="Grade 8">Grade 8</option>
                  <option value="Grade 9">Grade 9</option>
                  <option value="Grade 10">Grade 10</option>
                  <option value="Grade 11">Grade 11</option>
                  <option value="Grade 12">Grade 12</option>
                </optgroup>
                <optgroup label="Higher Education">
                  <option value="1st Year">1st Year</option>
                  <option value="2nd Year">2nd Year</option>
                  <option value="3rd Year">3rd Year</option>
                  <option value="4th Year">4th Year</option>
                  <option value="5th Year">5th Year</option>
                  <option value="Irregular">Irregular</option>
                </optgroup>
                <option value="Faculty/Staff">Faculty/Staff</option>
            </select></div>
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
          <div class="form-group"><label class="form-label">Student/Staff ID <span class="required-star">*</span></label><input name="user_id" id="edit-user_id" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Role</label><select name="role" id="edit-role" class="form-control"><option value="student">Student</option><option value="staff">Staff</option></select></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">First Name <span class="required-star">*</span></label><input name="first_name" id="edit-first_name" class="form-control" required></div>
          <div class="form-group"><label class="form-label">Middle Name</label><input name="middle_name" id="edit-middle_name" class="form-control"></div>
          <div class="form-group"><label class="form-label">Last Name <span class="required-star">*</span></label><input name="last_name" id="edit-last_name" class="form-control" required></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">Email</label><input name="email" id="edit-email" type="email" class="form-control"></div>
          <div class="form-group"><label class="form-label">Contact Number</label><input name="contact_number" id="edit-contact_number" class="form-control"></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label class="form-label">College / Academic Unit</label><select name="college_id" id="edit-college" class="form-control" onchange="loadDepartmentsEdit(this.value)"><option value="">Select Unit</option></select></div>
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
          <div class="form-group" style="flex:1"><label class="form-label">Rank / Year Level</label><select name="rank" id="edit-rank" class="form-control">
              <option value="">Select Rank</option>
              <optgroup label="Elementary">
                <option value="Grade 1">Grade 1</option>
                <option value="Grade 2">Grade 2</option>
                <option value="Grade 3">Grade 3</option>
                <option value="Grade 4">Grade 4</option>
                <option value="Grade 5">Grade 5</option>
                <option value="Grade 6">Grade 6</option>
              </optgroup>
              <optgroup label="Secondary">
                <option value="Grade 7">Grade 7</option>
                <option value="Grade 8">Grade 8</option>
                <option value="Grade 9">Grade 9</option>
                <option value="Grade 10">Grade 10</option>
                <option value="Grade 11">Grade 11</option>
                <option value="Grade 12">Grade 12</option>
              </optgroup>
              <optgroup label="Higher Education">
                <option value="1st Year">1st Year</option>
                <option value="2nd Year">2nd Year</option>
                <option value="3rd Year">3rd Year</option>
                <option value="4th Year">4th Year</option>
                <option value="5th Year">5th Year</option>
                <option value="Irregular">Irregular</option>
              </optgroup>
              <option value="Faculty/Staff">Faculty/Staff</option>
          </select></div>
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
    document.getElementById('edit-rank').value   = u.rank   || '';
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
