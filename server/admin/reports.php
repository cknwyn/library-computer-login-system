<?php
// ============================================================
// Admin — Reports — /admin/reports.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo   = db();
$admin = current_admin();

$date_from = $_GET['date_from'] ?? date('Y-m-01');    // Start of current month
$date_to   = $_GET['date_to']   ?? date('Y-m-d');
$group_by  = in_array($_GET['group_by'] ?? '', ['day','user','terminal','users_list','websites_list','college']) ? $_GET['group_by'] : 'day';
$export    = isset($_GET['export']);
$college_f = $_GET['college'] ?? '';
$course_f  = $_GET['course']  ?? '';

// If college changes, clear course unless it belongs to that college
if ($college_f && $course_f) {
    $check = $pdo->prepare("SELECT COUNT(*) FROM departments WHERE id = ? AND college_id = ?");
    $check->execute([$course_f, $college_f]);
    if (!$check->fetchColumn()) $course_f = '';
}

// ── Load filter options ──────────────────────────────────────
$colleges = $pdo->query("SELECT id, name FROM colleges ORDER BY name")->fetchAll();

$courses_sql = "SELECT id, name FROM departments WHERE 1=1";
if ($college_f) {
    $courses_sql .= " AND college_id = " . (int)$college_f;
}
$courses_sql .= " ORDER BY name";
$courses = $pdo->query($courses_sql)->fetchAll();

$where_clauses = ["DATE(s.login_time) BETWEEN :df AND :dt", "s.status IN ('completed','force_ended','abandoned')"];
$params = [':df' => $date_from, ':dt' => $date_to];

if ($college_f) {
    $where_clauses[] = "u.college_id = :coll";
    $params[':coll'] = $college_f;
}
if ($course_f) {
    $where_clauses[] = "u.department_id = :course";
    $params[':course'] = $course_f;
}
$where_str = implode(" AND ", $where_clauses);

// ── Aggregate stats for the date range ───────────────────────
$range_stats = $pdo->prepare(
    "SELECT
       COUNT(*)                              AS total_sessions,
       COUNT(DISTINCT s.user_id)              AS unique_users,
       COUNT(DISTINCT s.terminal_id)          AS terminals_used,
       AVG(s.duration_seconds)                AS avg_duration,
       SUM(s.duration_seconds)                AS total_duration,
       MAX(s.duration_seconds)                AS max_duration,
       MIN(CASE WHEN s.duration_seconds>0 THEN s.duration_seconds END) AS min_duration
     FROM sessions s
     JOIN users u ON u.id = s.user_id
     WHERE $where_str"
);
$range_stats->execute($params);
$stats = $range_stats->fetch();

// ── Grouped breakdown ─────────────────────────────────────────
if ($group_by === 'day') {
    $sql = "SELECT DATE(s.login_time) AS label,
                   COUNT(*)           AS sessions,
                   COUNT(DISTINCT s.user_id) AS users,
                   AVG(s.duration_seconds)   AS avg_dur,
                   SUM(s.duration_seconds)   AS total_dur
            FROM sessions s
            JOIN users u ON u.id = s.user_id
            WHERE $where_str
            GROUP BY DATE(s.login_time)
            ORDER BY label ASC";
} elseif ($group_by === 'user') {
    $sql = "SELECT u.user_id AS label,
                   u.name AS extra,
                   COUNT(*)  AS sessions,
                   AVG(s.duration_seconds) AS avg_dur,
                   SUM(s.duration_seconds) AS total_dur
            FROM sessions s
            JOIN users u ON u.id=s.user_id
            WHERE $where_str
            GROUP BY s.user_id
            ORDER BY sessions DESC";
} elseif ($group_by === 'users_list') {
    $u_where = ["DATE(u.creation_date) BETWEEN :df AND :dt"];
    if ($college_f) $u_where[] = "u.college_id = :coll";
    if ($course_f)  $u_where[] = "u.department_id = :course";
    $u_where_str = implode(" AND ", $u_where);
    $sql = "SELECT u.user_id AS label, u.name AS extra, u.role, u.email, u.creation_date, c.name AS college_name
            FROM users u
            LEFT JOIN colleges c ON u.college_id = c.id
            WHERE $u_where_str
            ORDER BY u.creation_date DESC";
} elseif ($group_by === 'websites_list') {
    $w_where = ["DATE(wl.visited_at) BETWEEN :df AND :dt"];
    if ($college_f) $w_where[] = "u.college_id = :coll";
    if ($course_f)  $w_where[] = "u.department_id = :course";
    $w_where_str = implode(" AND ", $w_where);
    $sql = "SELECT u.user_id AS label, u.name AS extra, w.title, w.url, wl.visited_at, t.terminal_code
            FROM website_logs wl
            JOIN websites w ON wl.website_id = w.id
            JOIN users u ON u.id = wl.user_id
            JOIN sessions s ON s.id = wl.session_id
            JOIN terminals t ON t.id = s.terminal_id
            WHERE $w_where_str
            ORDER BY wl.visited_at DESC";
} elseif ($group_by === 'college') {
    $sql = "SELECT COALESCE(c.name, 'No Affiliation') AS label,
                   COUNT(*) AS sessions,
                   COUNT(DISTINCT s.user_id) AS users,
                   AVG(s.duration_seconds) AS avg_dur,
                   SUM(s.duration_seconds) AS total_dur
            FROM sessions s
            JOIN users u ON u.id=s.user_id
            LEFT JOIN colleges c ON u.college_id = c.id
            WHERE $where_str
            GROUP BY u.college_id
            ORDER BY sessions DESC";
} else {
    $sql = "SELECT t.terminal_code AS label,
                   COALESCE(r.name, 'Unknown') AS extra,
                   COUNT(*) AS sessions,
                   AVG(s.duration_seconds) AS avg_dur,
                   SUM(s.duration_seconds) AS total_dur
            FROM sessions s
            JOIN terminals t ON t.id=s.terminal_id
            LEFT JOIN rooms r ON r.id=t.room_id
            JOIN users u ON u.id = s.user_id
            WHERE $where_str
            GROUP BY s.terminal_id
            ORDER BY sessions DESC";
}
$breakdown_stmt = $pdo->prepare($sql);
$breakdown_stmt->execute($params);
$breakdown = $breakdown_stmt->fetchAll();

// ── CSV Export ────────────────────────────────────────────────
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="library-report-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    
    if ($group_by === 'users_list') {
        fputcsv($out, [
            'Username', 'Email', 'Contact Number', 'Designation', 'College', 
            'Gender', 'Year', 'Department', 'User Type', 'Degree', 
            'Speciality', 'Staff Id', 'Ra Expiry Date', 'Rank', 'Batch', 
            'Cadre', 'Dob', 'creation_date'
        ]);
        $rows = $pdo->prepare("SELECT u.*, c.name AS college_name, d.name AS dept_name, deg.name AS degree_name 
                               FROM users u
                               LEFT JOIN colleges c ON u.college_id = c.id
                               LEFT JOIN departments d ON u.department_id = d.id
                               LEFT JOIN degrees deg ON u.degree_id = deg.id
                               WHERE $u_where_str ORDER BY u.creation_date DESC");
        $rows->execute($params);
        while ($u = $rows->fetch()) {
            fputcsv($out, [
                $u['username'] ?? '-', $u['email'] ?? '-', $u['contact_number'] ?? '-',
                $u['designation'] ?? '-', $u['college_name'] ?? '-', $u['gender'] ?? '-',
                $u['year'] ?? '-', $u['dept_name'] ?? '-', $u['user_type'] ?? '-',
                $u['degree_name'] ?? '-', $u['speciality'] ?? '-', $u['user_id'],
                $u['ra_expiry_date'] ?? '-', $u['rank'] ?? '-', $u['batch'] ?? '-',
                $u['cadre'] ?? '-', $u['dob'] ?? '-', $u['creation_date']
            ]);
        }
    } elseif ($group_by === 'websites_list') {
        fputcsv($out, ['Staff Id', 'User Name', 'Terminal', 'Page Title', 'URL', 'Visited At']);
        $rows = $pdo->prepare("SELECT u.user_id, u.name, t.terminal_code, w.title, w.url, wl.visited_at
                               FROM website_logs wl
                               JOIN websites w ON wl.website_id = w.id
                               JOIN users u ON u.id = wl.user_id
                               JOIN sessions s ON s.id = wl.session_id
                               JOIN terminals t ON t.id = s.terminal_id
                               WHERE $w_where_str
                               ORDER BY wl.visited_at DESC");
        $rows->execute($params);
        while ($l = $rows->fetch()) {
            fputcsv($out, [$l['user_id'], $l['name'], $l['terminal_code'], $l['title'], $l['url'], $l['visited_at']]);
        }
    } else {
        fputcsv($out, ['User ID','User Name','College','Terminal','Room','Campus','Login Time','Logout Time','Duration (s)','Duration','Status']);
        $rows = $pdo->prepare(
            "SELECT u.user_id, u.name, coll.name AS college_name, t.terminal_code, r.name AS room_name, camp.name AS campus_name,
                    s.login_time, s.logout_time, s.duration_seconds, s.status
             FROM sessions s
             JOIN users     u ON u.id=s.user_id
             LEFT JOIN colleges coll ON u.college_id = coll.id
             JOIN terminals t ON t.id=s.terminal_id
             LEFT JOIN rooms r ON r.id=t.room_id
             LEFT JOIN campuses camp ON camp.id=r.campus_id
             WHERE $where_str
             ORDER BY s.login_time DESC"
        );
        $rows->execute($params);
        while ($r = $rows->fetch()) {
            fputcsv($out, [$r['user_id'],$r['name'],$r['college_name']??'—',$r['terminal_code'],$r['room_name']??'—',$r['campus_name']??'—',$r['login_time'],$r['logout_time'],
                           $r['duration_seconds'],format_duration((int)$r['duration_seconds']),$r['status']]);
        }
    }
    fclose($out);
    exit;
}

// ── Chart data (JSON for inline JS) ──────────────────────────
$chart_labels = array_column($breakdown, 'label');
$chart_sessions = array_column($breakdown, 'sessions');
$chart_avg = array_map(fn($r) => round((float)($r['avg_dur']??0)), $breakdown);

$page = 'reports';
include __DIR__ . '/partials/header.php';
?>

<!-- Filter Form -->
<div class="card" style="margin-bottom:24px">
  <div class="card-body" style="padding:20px">
    <form method="GET" class="filter-bar" style="margin-bottom:0;flex-wrap:wrap">
      <label class="form-label" style="margin:0;white-space:nowrap">Date Range:</label>
      <input type="date" name="date_from" class="form-control" style="max-width:160px" value="<?= h($date_from) ?>">
      <span class="td-muted">to</span>
      <input type="date" name="date_to" class="form-control" style="max-width:160px" value="<?= h($date_to) ?>">
      <label class="form-label" style="margin:0;white-space:nowrap">Group by:</label>
      <select name="group_by" class="form-control" style="max-width:140px">
        <option value="day"        <?= $group_by==='day'       ?'selected':'' ?>>Day</option>
        <option value="user"       <?= $group_by==='user'      ?'selected':'' ?>>User Stats</option>
        <option value="terminal"   <?= $group_by==='terminal'     ?'selected':'' ?>>Terminal</option>
        <option value="college"    <?= $group_by==='college'      ?'selected':'' ?>>College</option>
        <option value="users_list" <?= $group_by==='users_list'   ?'selected':'' ?>>User Registry</option>
        <option value="websites_list" <?= $group_by==='websites_list' ?'selected':'' ?>>Website Tracking</option>
      </select>
      <button type="submit" class="btn btn-primary">Generate</button>

      <div style="flex-basis:100%; height:0; margin:0"></div> <!-- Break -->

      <label class="form-label" style="margin:0;white-space:nowrap">Filter College:</label>
      <select name="college" class="form-control" style="max-width:200px" onchange="this.form.submit()">
        <option value="">— All Colleges —</option>
        <?php foreach ($colleges as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $college_f==(string)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <label class="form-label" style="margin:0;white-space:nowrap">Course/Dept:</label>
      <select name="course" class="form-control" style="max-width:200px">
        <option value="">— All Courses —</option>
        <?php foreach ($courses as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $course_f==(string)$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
        <?php endforeach; ?>
      </select>

      <a href="?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&group_by=<?= $group_by ?>&college=<?= h($college_f) ?>&course=<?= h($course_f) ?>&export=1"
         class="btn btn-outline"><i data-lucide="download" style="width:16px;vertical-align:middle;margin-right:4px"></i> Export CSV</a>

    </form>
  </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom:32px">
  <div class="stat-card">
    <span class="stat-label">Total Sessions</span>
    <span class="stat-value"><?= number_format((int)($stats['total_sessions']??0)) ?></span>
    <span class="stat-sub"><?= date('M d', strtotime($date_from)) ?> – <?= date('M d', strtotime($date_to)) ?></span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Unique Users</span>
    <span class="stat-value"><?= (int)($stats['unique_users']??0) ?></span>
    <span class="stat-sub">Distinct individuals</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Avg. Duration</span>
    <span class="stat-value" style="font-size:24px"><?= format_duration((int)($stats['avg_duration']??0)) ?></span>
    <span class="stat-sub">Per session</span>
  </div>
  <div class="stat-card">
    <span class="stat-label">Total Time Used</span>
    <span class="stat-value" style="font-size:24px"><?= format_duration((int)($stats['total_duration']??0)) ?></span>
    <span class="stat-sub">Cumulative</span>
  </div>
</div>

<!-- Chart -->
<?php if (!empty($breakdown) && $group_by === 'day'): ?>
<div class="card" style="margin-bottom:32px">
  <div class="card-header"><span class="card-title">Volume Distribution</span></div>
  <div class="card-body">
    <canvas id="chart-sessions" style="max-height:300px"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function(){
  const ctx = document.getElementById('chart-sessions').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chart_labels) ?>,
      datasets: [{
        label: 'Sessions',
        data: <?= json_encode($chart_sessions) ?>,
        backgroundColor: '#005FB8',
        borderRadius: 6,
        barThickness: 32
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: '#64748B', font: { weight: '600' } }, grid: { display: false } },
        y: { ticks: { color: '#64748B' }, grid: { color: '#F1F5F9' }, beginAtZero: true }
      }
    }
  });
})();
</script>
<?php endif; ?>


<!-- Breakdown Table -->
<div class="card">
  <div class="card-header">
    <span class="card-title"><i data-lucide="bar-chart-2" style="width:18px;vertical-align:middle;margin-right:4px"></i> Breakdown by <?= ucwords(str_replace('_', ' ', $group_by)) ?></span>

  </div>
  <?php if (empty($breakdown)): ?>
    <div class="empty-state"><div class="empty-icon"><i data-lucide="inbox"></i></div><p>No completed sessions in this range.</p></div>

  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= ($group_by==='users_list' ? 'Staff ID' : ($group_by==='college' ? 'College' : ucfirst($group_by))) ?></th>
          <?= !in_array($group_by, ['day','college']) ? '<th>'.($group_by==='users_list'?'Full Name':'Name/Location').'</th>' : '' ?>
          <?php if ($group_by === 'users_list'): ?>
            <th>Email</th>
            <th>Role</th>
            <th>Date Registered</th>
          <?php elseif ($group_by === 'websites_list'): ?>
            <th>Terminal</th>
            <th>Website</th>
            <th>Visited At</th>
          <?php else: ?>
            <th>Sessions</th>
            <th>Avg. Duration</th>
            <th>Total Time</th>
          <?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($breakdown as $r): ?>
        <tr>
          <td class="mono" style="font-weight:600"><?= h($label_display = ($group_by==='college' ? ($r['label'] ?: 'Unspecified') : $r['label'])) ?></td>
          <?php if (!in_array($group_by, ['day','college'])): ?>
            <td class="td-muted"><?= h($r['extra']??'—') ?></td>
          <?php endif; ?>
          <?php if ($group_by === 'users_list'): ?>
            <td><?= h($r['email'] ?? '—') ?></td>
            <td><span class="badge <?= $r['role']==='staff'?'badge-blue':'badge-yellow' ?>"><?= strtoupper($r['role']) ?></span></td>
            <td class="td-muted"><?= date('M d, Y', strtotime($r['creation_date'])) ?></td>
          <?php elseif ($group_by === 'websites_list'): ?>
            <td><span class="badge badge-blue"><?= h($r['terminal_code']) ?></span></td>
            <td>
              <div style="font-weight:600; font-size:12px"><?= h($r['title'] ?: 'Untitled Page') ?></div>
              <div class="td-muted" style="font-size:11px; max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap"><?= h($r['url']) ?></div>
            </td>
            <td class="td-muted"><?= date('M d, H:i:s', strtotime($r['visited_at'])) ?></td>
          <?php else: ?>
            <td><?= (int)$r['sessions'] ?></td>
            <td><?= format_duration((int)round((float)($r['avg_dur']??0))) ?></td>
            <td><?= format_duration((int)($r['total_dur']??0)) ?></td>
          <?php endif; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
