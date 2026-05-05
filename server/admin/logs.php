<?php
// ============================================================
// Admin — System Logs — /admin/logs.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin_login();

$pdo   = db();
$admin = current_admin();

// ── Params ───────────────────────────────────────────────────
$page_num = max(1, (int)($_GET['p'] ?? 1));
$limit    = 50;
$offset   = ($page_num - 1) * $limit;

$action_f = $_GET['action'] ?? '';
$search   = trim($_GET['search'] ?? '');
$date_f   = $_GET['date'] ?? '';

// ── Build Query ──────────────────────────────────────────────
$where = ["1=1"];
$params = [];

if ($action_f) {
    $where[] = "action = :action";
    $params[':action'] = $action_f;
}
if ($date_f) {
    $where[] = "DATE(creation_date) = :date";
    $params[':date'] = $date_f;
}
if ($search) {
    $where[] = "(details LIKE :search OR user_id IN (SELECT id FROM users WHERE user_id LIKE :search))";
    $params[':search'] = "%$search%";
}

$where_str = implode(" AND ", $where);

// Count total for pagination
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE $where_str");
$count_stmt->execute($params);
$total_rows = (int)$count_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch logs
$sql = "SELECT al.*, 
               u.user_id AS student_id, u.name AS student_name, u.first_name, u.middle_name, u.last_name,
               a.username AS admin_user, a.name AS admin_name,
               t.terminal_code

        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        LEFT JOIN admins a ON al.admin_id = a.id
        LEFT JOIN terminals t ON al.terminal_id = t.id
        WHERE $where_str
        ORDER BY al.creation_date DESC
        LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get distinct actions for filter
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// ── CSV Export ────────────────────────────────────────────────
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit-trail-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Timestamp', 'Action', 'Actor Name', 'Actor Type', 'Terminal', 'Details', 'IP Address']);
    
    $stmt = $pdo->prepare("SELECT al.*, 
                   u.user_id AS student_id, u.name AS student_name, u.first_name, u.middle_name, u.last_name,
                   a.username AS admin_user, a.name AS admin_name,
                   t.terminal_code
            FROM activity_logs al
            LEFT JOIN users u ON al.user_id = u.id
            LEFT JOIN admins a ON al.admin_id = a.id
            LEFT JOIN terminals t ON al.terminal_id = t.id
            WHERE $where_str
            ORDER BY al.creation_date DESC");
    $stmt->execute($params);
    
    while ($l = $stmt->fetch()) {
        $actor_name = 'System';
        $actor_type = 'System';
        if ($l['admin_id']) {
            $actor_name = $l['admin_name'];
            $actor_type = 'Admin';
        } elseif ($l['user_id']) {
            $actor_name = format_user_name([
                'first_name' => $l['first_name'],
                'middle_name' => $l['middle_name'],
                'last_name' => $l['last_name'],
                'name' => $l['student_name']
            ]);
            $actor_type = "Student ({$l['student_id']})";
        }
        fputcsv($out, [
            $l['creation_date'], $l['action'], $actor_name, $actor_type, 
            $l['terminal_code'] ?? '-', $l['details'], $l['ip_address'] ?? '-'

        ]);
    }
    fclose($out);
    exit;
}

$page = 'logs';
include __DIR__ . '/partials/header.php';
?>


<div class="card" style="margin-bottom:24px">
    <div class="card-body" style="padding:20px">
        <form method="GET" class="filter-bar" style="margin-bottom:0; display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap">
            <div style="flex:1; min-width:200px">
                <label class="form-label">Search Details / Student ID</label>
                <input type="text" name="search" class="form-control" value="<?= h($search) ?>" placeholder="Search...">
            </div>
            
            <div style="width:200px">
                <label class="form-label">Action Type</label>
                <select name="action" class="form-control">
                    <option value="">— All Actions —</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= h($a) ?>" <?= $action_f === $a ? 'selected' : '' ?>><?= h($a) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="width:160px">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= h($date_f) ?>">
            </div>

            <button type="submit" class="btn btn-create" style="height:42px">Filter</button>
            <?php if ($action_f || $search || $date_f): ?>
                <a href="logs.php" class="btn btn-outline" style="height:42px; line-height:28px">Reset</a>
            <?php endif; ?>
            
            <a href="?export=1&action=<?= h($action_f) ?>&search=<?= h($search) ?>&date=<?= h($date_f) ?>" 
               class="btn btn-secondary" style="height:42px; line-height:28px; margin-left:auto">
                <i data-lucide="download" style="width:16px;vertical-align:middle;margin-right:4px"></i> Export CSV
            </a>
        </form>

    </div>
</div>

<div class="card">
    <div class="card-header">
        <span class="card-title"><i data-lucide="history" style="width:18px;vertical-align:middle;margin-right:4px"></i> System Audit Trail <span style="font-size:13px; color:var(--text-muted); margin-left:12px; font-weight:400"><?= number_format($total_rows) ?> events found</span></span>
    </div>



    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <div class="empty-icon"><i data-lucide="inbox"></i></div>
            <p>No activity logs found for the selected criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:180px">Timestamp</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Terminal</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $l): ?>
                    <tr>
                        <td class="td-muted" style="font-size:12px"><?= date('M d, Y H:i:s', strtotime($l['creation_date'])) ?></td>
                        <td>
                            <span class="badge" style="background:var(--bg-light); color:var(--text-dark); border:1px solid var(--border-color)">
                                <?= h($l['action']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($l['admin_id']): ?>
                                <div style="font-weight:600; color:#6366F1"><i data-lucide="shield" style="width:12px; display:inline-block"></i> <?= h($l['admin_name']) ?></div>
                                <div class="td-muted" style="font-size:11px">Admin</div>
                            <?php elseif ($l['user_id']): ?>
                                <div style="font-weight:600"><?= h(format_user_name([
                                    'first_name' => $l['first_name'],
                                    'middle_name' => $l['middle_name'],
                                    'last_name' => $l['last_name'],
                                    'name' => $l['student_name']
                                ])) ?></div>
                                <div class="td-muted" style="font-size:11px">ID: <?= h($l['student_id']) ?></div>

                            <?php else: ?>
                                <span class="td-muted">System / Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($l['terminal_code']): ?>
                                <span class="badge badge-blue"><?= h($l['terminal_code']) ?></span>
                            <?php else: ?>
                                <span class="td-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="max-width:400px; font-size:13px">
                            <?= h($l['details']) ?>
                        </td>
                        <td class="mono td-muted" style="font-size:11px">
                            <?php 
                                if ($l['ip_address']) {
                                    $parts = explode('.', $l['ip_address']);
                                    if (count($parts) === 4) {
                                        echo h($parts[0] . '.' . $parts[1] . '.*.*');
                                    } else {
                                        echo 'Masked';
                                    }
                                } else {
                                    echo '—';
                                }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
        <div class="card-footer" style="display:flex; justify-content:space-between; align-items:center; padding:16px 24px">
            <div class="td-muted" style="font-size:13px">
                Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $total_rows) ?> of <?= number_format($total_rows) ?>
            </div>
            <div class="pagination" style="display:flex; gap:8px">
                <?php if ($page_num > 1): ?>
                    <a href="?p=<?= $page_num - 1 ?>&action=<?= h($action_f) ?>&search=<?= h($search) ?>&date=<?= h($date_f) ?>" class="btn btn-secondary">Previous</a>
                <?php endif; ?>
                
                <?php if ($page_num < $total_pages): ?>
                    <a href="?p=<?= $page_num + 1 ?>&action=<?= h($action_f) ?>&search=<?= h($search) ?>&date=<?= h($date_f) ?>" class="btn btn-secondary">Next</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
