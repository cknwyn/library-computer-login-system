<?php
// ============================================================
// POST /api/login.php
// Authenticates a user and creates a session.
// Body (JSON): { "user_id": "2021-00001", "password": "...", "terminal_code": "PC-01" }
// Response:    { "success": true, "session_token": "...", "session_id": 1, "user": {...} }
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['success' => false, 'error' => 'Method not allowed'], 405);

$body = json_decode(file_get_contents('php://input'), true);
$user_id_input   = trim($body['user_id']       ?? '');
$password_input  = trim($body['password']       ?? '');
$terminal_code   = trim($body['terminal_code']  ?? '');
$pc_name         = trim($body['pc_name']         ?? '');

if (!$user_id_input || !$password_input || !$terminal_code) {
    json_response(['success' => false, 'error' => 'ID, password, and terminal code are required.'], 400);
}

// ── Find user ────────────────────────────────────────────────
$stmt = db()->prepare('SELECT * FROM users WHERE user_id = :uid LIMIT 1');
$stmt->execute([':uid' => $user_id_input]);
$user = $stmt->fetch();

if (!$user || !password_verify($password_input, $user['password_hash'])) {
    log_activity('LOGIN_FAILED', "User ID: {$user_id_input} | Terminal: {$terminal_code}");
    json_response(['success' => false, 'error' => 'Invalid ID or password.'], 401);
}

if ($user['status'] !== 'active') {
    json_response(['success' => false, 'error' => 'Your account has been ' . $user['status'] . '. Please see the librarian.'], 403);
}

// ── Check for existing active session for this user ──────────
$stmt = db()->prepare("SELECT id FROM sessions WHERE user_id = :uid AND status = 'active' LIMIT 1");
$stmt->execute([':uid' => $user['id']]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'error' => 'You already have an active session on another terminal.'], 409);
}

// ── Resolve or create terminal ────────────────────────────────
$stmt = db()->prepare('SELECT * FROM terminals WHERE terminal_code = :code LIMIT 1');
$stmt->execute([':code' => $terminal_code]);
$terminal = $stmt->fetch();

if (!$terminal) {
    // Auto-register unknown terminals (convenient for local testing)
    $stmt = db()->prepare('INSERT INTO terminals (terminal_code, pc_name, status) VALUES (:code, :pcn, :s)');
    $stmt->execute([':code' => $terminal_code, ':pcn' => $pc_name ?: null, ':s' => 'online']);
    $terminal_id = (int) db()->lastInsertId();
} else {
    $terminal_id = (int) $terminal['id'];
    // Update terminal status to online and sync PC Name if it changed
    db()->prepare("UPDATE terminals SET status = 'online', pc_name = COALESCE(:pcn, pc_name), last_seen = NOW() WHERE id = :id")
        ->execute([':id' => $terminal_id, ':pcn' => $pc_name ?: null]);
}

// ── Create session ────────────────────────────────────────────
$token = generate_token(32);
$stmt  = db()->prepare(
    'INSERT INTO sessions (user_id, terminal_id, session_token, last_heartbeat)
     VALUES (:uid, :tid, :token, NOW())'
);
$stmt->execute([':uid' => $user['id'], ':tid' => $terminal_id, ':token' => $token]);
$session_id = (int) db()->lastInsertId();

log_activity('USER_LOGIN', "Session #{$session_id} | Terminal: {$terminal_code}", $user['id'], null, $terminal_id);

json_response([
    'success'       => true,
    'session_token' => $token,
    'session_id'    => $session_id,
    'user'          => [
        'id'         => $user['id'],
        'user_id'    => $user['user_id'],
        'name'       => $user['name'],
        'role'       => $user['role'],
        'department' => $user['department'],
    ],
    'terminal' => [
        'id'   => $terminal_id,
        'code' => $terminal_code,
    ],
    'login_time' => date('c'),
]);
