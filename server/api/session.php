<?php
// ============================================================
// GET /api/session.php
// Returns current session info for the logged-in user.
// Header: X-Session-Token: <token>
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_response(['success' => false, 'error' => 'Method not allowed'], 405);

$session = verify_client_token();
if (!$session) {
    json_response(['success' => false, 'error' => 'Invalid or expired session token.'], 401);
}

$elapsed = elapsed_since($session['login_time']);

json_response([
    'success' => true,
    'session' => [
        'id'               => $session['id'],
        'user_name'        => $session['user_name'],
        'user_code'        => $session['user_code'],
        'role'             => $session['role'],
        'terminal_code'    => $session['terminal_code'],
        'login_time'       => $session['login_time'],
        'elapsed_seconds'  => $elapsed,
        'elapsed_display'  => format_duration($elapsed),
        'status'           => $session['status'],
    ],
]);
