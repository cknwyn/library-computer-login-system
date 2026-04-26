<?php
// ============================================================
// API — Track Website Visit — /api/track_website.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

// ── Check session ─────────────────────────────────────────────
$token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? $_POST['token'] ?? null;
if (!$token) {
    echo json_encode(['success' => false, 'error' => 'Missing session token.']);
    exit;
}

$session = get_session_by_token($token);
if (!$session || $session['status'] !== 'active') {
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
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to store tracking data.']);
}
