<?php
// ============================================================
// Admin — Website Tracking — /admin/websites.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo = db();
$admin = current_admin();

// ── Load data ─────────────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
$date   = $_GET['date'] ?? date('Y-m-d');

$query = "SELECT wl.*, ws.url, ws.title, u.name AS user_name, u.user_id AS user_code, t.terminal_code
          FROM website_logs wl
          JOIN websites ws ON wl.website_id = ws.id
          JOIN users u ON u.id = wl.user_id
          JOIN sessions s ON s.id = wl.session_id
          JOIN terminals t ON t.id = s.terminal_id
          WHERE (DATE(wl.visited_at) = :dt)
          AND (:sh = '' OR ws.url LIKE :sh2 OR u.name LIKE :sh3 OR u.user_id LIKE :sh4)
          ORDER BY wl.visited_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([
    ':dt'  => $date,
    ':sh'  => $search,
    ':sh2' => "%$search%",
    ':sh3' => "%$search%",
    ':sh4' => "%$search%"
]);
$logs = $stmt->fetchAll();

// ── CSV Export ────────────────────────────────────────────────
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="website-logs-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Staff Id', 'User Name', 'Terminal', 'Page Title', 'URL', 'Visited At']);
    foreach ($logs as $l) {
        fputcsv($out, [
            $l['user_code'], 
            $l['user_name'], 
            $l['terminal_code'], 
            $l['title'] ?: 'Untitled', 
            $l['url'], 
            $l['visited_at']
        ]);
    }
    fclose($out);
    exit;
}

$page = 'websites';
include __DIR__ . '/partials/header.php';
?>

<div class="filter-bar">
  <form method="GET" style="display:flex; gap:16px; width:100%; flex-wrap:wrap">
    <div class="search-wrap" style="flex:1">
      <i data-lucide="search" class="search-icon"></i>
      <input type="text" name="search" class="form-control search-input" placeholder="Search URL, User or ID..." value="<?= h($search) ?>">
    </div>
    
    <div style="width:200px">
      <input type="date" name="date" class="form-control" value="<?= h($date) ?>" onchange="this.form.submit()">
    </div>
    
    <button type="submit" class="btn btn-primary">Filter</button>
    <a href="?date=<?= $date ?>&search=<?= h($search) ?>&export=1" class="btn btn-outline"><i data-lucide="download"></i> Export CSV</a>
    <?php if ($search || $date !== date('Y-m-d')): ?>
      <a href="websites.php" class="btn btn-outline" title="Reset">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <span class="card-title">Website Tracking Logs <span class="badge badge-gray" style="margin-left:8px"><?= count($logs) ?></span></span>
  </div>
  
  <?php if (empty($logs)): ?>
    <div class="empty-state">
      <div class="empty-icon"><i data-lucide="globe"></i></div>
      <p>No website visits tracked for this criteria.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Terminal</th>
            <th>Website / URL</th>
            <th>Time</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($logs as $l): ?>
          <tr>
            <td>
              <div style="font-weight:700"><?= h($l['user_name']) ?></div>
              <div class="mono td-muted" style="font-size:11px"><?= h($l['user_code']) ?></div>
            </td>
            <td>
              <span class="badge badge-blue"><?= h($l['terminal_code']) ?></span>
            </td>
            <td style="max-width: 400px; overflow: hidden;">
              <div style="font-weight:600; color: var(--text-primary); text-overflow: ellipsis; white-space: nowrap; overflow: hidden;" title="<?= h($l['title'] ?? 'No Title') ?>">
                <?= h($l['title'] ?: 'Untitled Page') ?>
              </div>
              <a href="<?= h($l['url']) ?>" target="_blank" class="td-muted" style="font-size:12px; display: block; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">
                <?= h($l['url']) ?>
              </a>
            </td>
            <td class="td-muted">
              <?= date('H:i:s', strtotime($l['visited_at'])) ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
