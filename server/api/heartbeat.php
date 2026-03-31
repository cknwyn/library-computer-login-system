<?php
// ============================================================
// POST /api/heartbeat.php
// Called every 30 seconds by the Electron client to keep
// the session alive and update the terminal's last_seen.
// Header: X-Session-Token: <token>
// Response: { "success": true, "elapsed_seconds": 3600 }
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

// ── Update heartbeat ──────────────────────────────────────────
db()->prepare('UPDATE sessions SET last_heartbeat = NOW() WHERE id = :id')
    ->execute([':id' => $session['id']]);

// ── Update terminal last_seen ─────────────────────────────────
db()->prepare("UPDATE terminals SET last_seen = NOW(), status = 'online' WHERE id = :tid")
    ->execute([':tid' => $session['terminal_id']]);

// ── Compute elapsed time ──────────────────────────────────────
$elapsed = elapsed_since($session['login_time']);

json_response([
    'success'          => true,
    'elapsed_seconds'  => $elapsed,
    'elapsed_display'  => format_duration($elapsed),
    'session_status'   => 'active',
]);
