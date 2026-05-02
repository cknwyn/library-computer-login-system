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

// ── Resolve Website ID ─────────────────────────────────────────
$pdo = db();
try {
    // Check if website exists
    $stmt = $pdo->prepare("SELECT id FROM websites WHERE url = ? LIMIT 1");
    $stmt->execute([$url]);
    $website_id = $stmt->fetchColumn();

    if (!$website_id) {
        // Create new website entry
        $stmt = $pdo->prepare("INSERT INTO websites (url, title) VALUES (?, ?)");
        $stmt->execute([$url, $title ?: null]);
        $website_id = $pdo->lastInsertId();
    } else if ($title) {
        // Optional: Update title if it was missing before
        $pdo->prepare("UPDATE websites SET title = ? WHERE id = ? AND (title IS NULL OR title = '')")->execute([$title, $website_id]);
    }

    // ── Store log ─────────────────────────────────────────────────
    $stmt = $pdo->prepare(
        "INSERT INTO website_logs (session_id, user_id, website_id) 
         VALUES (:sid, :uid, :wid)"
    );

    $success = $stmt->execute([
        ':sid' => $session['id'],
        ':uid' => $session['user_id'],
        ':wid' => $website_id
    ]);
} catch (Exception $e) {
    $success = false;
    $error_msg = $e->getMessage();
}

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
