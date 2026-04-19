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
$group_by  = in_array($_GET['group_by'] ?? '', ['day','user','terminal']) ? $_GET['group_by'] : 'day';
$export    = isset($_GET['export']);

// ── Aggregate stats for the date range ───────────────────────
$range_stats = $pdo->prepare(
    "SELECT
       COUNT(*)                              AS total_sessions,
       COUNT(DISTINCT user_id)              AS unique_users,
       COUNT(DISTINCT terminal_id)          AS terminals_used,
       AVG(duration_seconds)                AS avg_duration,
       SUM(duration_seconds)                AS total_duration,
       MAX(duration_seconds)                AS max_duration,
       MIN(CASE WHEN duration_seconds>0 THEN duration_seconds END) AS min_duration
     FROM sessions
     WHERE DATE(login_time) BETWEEN :df AND :dt
       AND status IN ('completed','force_ended','abandoned')"
);
$range_stats->execute([':df'=>$date_from,':dt'=>$date_to]);
$stats = $range_stats->fetch();

// ── Grouped breakdown ─────────────────────────────────────────
if ($group_by === 'day') {
    $sql = "SELECT DATE(s.login_time) AS label,
                   COUNT(*)           AS sessions,
                   COUNT(DISTINCT s.user_id) AS users,
                   AVG(s.duration_seconds)   AS avg_dur,
                   SUM(s.duration_seconds)   AS total_dur
            FROM sessions s
            WHERE DATE(s.login_time) BETWEEN :df AND :dt
              AND s.status IN ('completed','force_ended','abandoned')
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
            WHERE DATE(s.login_time) BETWEEN :df AND :dt
              AND s.status IN ('completed','force_ended','abandoned')
            GROUP BY s.user_id
            ORDER BY sessions DESC";
} else {
    $sql = "SELECT t.terminal_code AS label,
                   t.location AS extra,
                   COUNT(*) AS sessions,
                   AVG(s.duration_seconds) AS avg_dur,
                   SUM(s.duration_seconds) AS total_dur
            FROM sessions s
            JOIN terminals t ON t.id=s.terminal_id
            WHERE DATE(s.login_time) BETWEEN :df AND :dt
              AND s.status IN ('completed','force_ended','abandoned')
            GROUP BY s.terminal_id
            ORDER BY sessions DESC";
}
$breakdown_stmt = $pdo->prepare($sql);
$breakdown_stmt->execute([':df'=>$date_from,':dt'=>$date_to]);
$breakdown = $breakdown_stmt->fetchAll();

// ── CSV Export ────────────────────────────────────────────────
if ($export) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="library-report-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['User ID','User Name','Terminal','Login Time','Logout Time','Duration (s)','Duration','Status']);
    $rows = $pdo->prepare(
        "SELECT u.user_id, u.name, t.terminal_code,
                s.login_time, s.logout_time, s.duration_seconds, s.status
         FROM sessions s
         JOIN users     u ON u.id=s.user_id
         JOIN terminals t ON t.id=s.terminal_id
         WHERE DATE(s.login_time) BETWEEN :df AND :dt
         ORDER BY s.login_time DESC"
    );
    $rows->execute([':df'=>$date_from,':dt'=>$date_to]);
    while ($r = $rows->fetch()) {
        fputcsv($out, [$r['user_id'],$r['name'],$r['terminal_code'],$r['login_time'],$r['logout_time'],
                       $r['duration_seconds'],format_duration((int)$r['duration_seconds']),$r['status']]);
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
        <option value="day"      <?= $group_by==='day'     ?'selected':'' ?>>Day</option>
        <option value="user"     <?= $group_by==='user'    ?'selected':'' ?>>User</option>
        <option value="terminal" <?= $group_by==='terminal'?'selected':'' ?>>Terminal</option>
      </select>
      <button type="submit" class="btn btn-primary">Generate</button>
      <a href="?date_from=<?= $date_from ?>&date_to=<?= $date_to ?>&group_by=<?= $group_by ?>&export=1"
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
    <span class="card-title"><i data-lucide="bar-chart-2" style="width:18px;vertical-align:middle;margin-right:4px"></i> Breakdown by <?= ucfirst($group_by) ?></span>

  </div>
  <?php if (empty($breakdown)): ?>
    <div class="empty-state"><div class="empty-icon"><i data-lucide="inbox"></i></div><p>No completed sessions in this range.</p></div>

  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th><?= ucfirst($group_by) ?></th>
          <?= $group_by!=='day' ? '<th>Name/Location</th>' : '' ?>
          <th>Sessions</th>
          <th>Avg. Duration</th>
          <th>Total Time</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($breakdown as $r): ?>
        <tr>
          <td class="mono" style="font-weight:600"><?= h($r['label']) ?></td>
          <?php if ($group_by!=='day'): ?>
            <td class="td-muted"><?= h($r['extra']??'—') ?></td>
          <?php endif; ?>
          <td><?= (int)$r['sessions'] ?></td>
          <td><?= format_duration((int)round((float)($r['avg_dur']??0))) ?></td>
          <td><?= format_duration((int)($r['total_dur']??0)) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
