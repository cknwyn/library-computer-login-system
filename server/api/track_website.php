<?php
// ============================================================
// API — Track Website Visit — /api/track_website.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// ── Check session ─────────────────────────────────────────────
$session = verify_client_token();
if (!$session) {
    echo json_encode(['success' => false, 'error' => 'Invalid or expired session.']);
    exit;
}

// ── Parse Input ───────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$url   = trim($input['url']   ?? '');
$title = trim($input['title'] ?? '');

if (empty($url)) {
    echo json_encode(['success' => false, 'error' => 'URL is required.']);
    exit;
}

// ── Store log ─────────────────────────────────────────────────
$pdo = db();
$stmt = $pdo->prepare(
    "INSERT INTO website_logs (session_id, user_id, url, title) 
     VALUES (:sid, :uid, :url, :title)"
);

$success = $stmt->execute([
    ':sid'   => $session['id'],
    ':uid'   => $session['user_id'],
    ':url'   => $url,
    ':title' => $title ?: null
]);

if ($success) {
    echo json_encode([
        'success' => true, 
        'db' => DB_NAME, 
        'affected' => $stmt->rowCount(),
        'sid' => $session['id'],
        'uid' => $session['user_id']
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to store tracking data.', 'pdo_error' => $stmt->errorInfo()]);
}
