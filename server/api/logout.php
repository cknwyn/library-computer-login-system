<?php
// ============================================================
// POST /api/logout.php
// Ends the current session and records duration.
// Header: X-Session-Token: <token>
// Body (JSON): { "session_id": 1 }
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success' => false, 'error' => 'Method not allowed'], 405);

$session = verify_client_token();
if (!$session) {
    json_response(['success' => false, 'error' => 'Invalid or expired session token.'], 401);
}

// ── End session ───────────────────────────────────────────────
$stmt = db()->prepare(
    "UPDATE sessions
     SET    status           = 'completed',
            logout_time      = NOW(),
            duration_seconds = TIMESTAMPDIFF(SECOND, login_time, NOW())
     WHERE  id = :id AND status = 'active'"
);
$stmt->execute([':id' => $session['id']]);

// ── Update terminal status ────────────────────────────────────
db()->prepare("UPDATE terminals SET status = 'offline' WHERE id = :tid")
    ->execute([':tid' => $session['terminal_id']]);

// ── Fetch final duration ──────────────────────────────────────
$row = db()->prepare('SELECT duration_seconds FROM sessions WHERE id = :id');
$row->execute([':id' => $session['id']]);
$duration = (int) ($row->fetchColumn() ?? 0);

log_activity(
    'USER_LOGOUT',
    "Session #{$session['id']} | Duration: " . format_duration($duration),
    $session['user_id'],
    null,
    $session['terminal_id']
);

json_response([
    'success'          => true,
    'session_id'       => $session['id'],
    'duration_seconds' => $duration,
    'duration_display' => format_duration($duration),
]);
