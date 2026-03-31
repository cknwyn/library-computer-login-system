<?php
// ============================================================
// GET  /api/apps.php          — List available apps
// POST /api/apps.php          — Submit an app install/uninstall request
// Header: X-Session-Token: <token>
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Session-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$session = verify_client_token();
if (!$session) {
    json_response(['success' => false, 'error' => 'Invalid or expired session token.'], 401);
}

// ── GET: list installed apps ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = db()->query(
        "SELECT id, name, description, version, category, status
         FROM   installed_apps
         WHERE  status = 'active'
         ORDER  BY category, name"
    );
    $apps = $stmt->fetchAll();

    // Also return this user's pending requests
    $stmt2 = db()->prepare(
        "SELECT app_name, request_type, status, requested_at
         FROM   app_requests
         WHERE  user_id = :uid AND session_id = :sid
         ORDER  BY requested_at DESC"
    );
    $stmt2->execute([':uid' => $session['user_id'], ':sid' => $session['id']]);
    $my_requests = $stmt2->fetchAll();

    json_response([
        'success'     => true,
        'apps'        => $apps,
        'my_requests' => $my_requests,
    ]);
}

// ── POST: submit an app request ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body         = json_decode(file_get_contents('php://input'), true);
    $app_name     = trim($body['app_name']     ?? '');
    $request_type = trim($body['request_type'] ?? '');
    $reason       = trim($body['reason']       ?? '');

    if (!$app_name || !in_array($request_type, ['install', 'uninstall'], true)) {
        json_response(['success' => false, 'error' => 'App name and valid request type (install/uninstall) are required.'], 400);
    }

    if (mb_strlen($app_name) > 100) {
        json_response(['success' => false, 'error' => 'App name is too long (100 characters max).'], 400);
    }

    // Prevent duplicate pending request for the same app in this session
    $stmt = db()->prepare(
        "SELECT id FROM app_requests
         WHERE  session_id = :sid AND app_name = :app AND request_type = :type AND status = 'pending'"
    );
    $stmt->execute([':sid' => $session['id'], ':app' => $app_name, ':type' => $request_type]);
    if ($stmt->fetch()) {
        json_response(['success' => false, 'error' => 'You already have a pending request for this app.'], 409);
    }

    $stmt = db()->prepare(
        'INSERT INTO app_requests (user_id, session_id, app_name, request_type, reason)
         VALUES (:uid, :sid, :app, :type, :reason)'
    );
    $stmt->execute([
        ':uid'    => $session['user_id'],
        ':sid'    => $session['id'],
        ':app'    => $app_name,
        ':type'   => $request_type,
        ':reason' => $reason ?: null,
    ]);
    $request_id = (int) db()->lastInsertId();

    log_activity(
        'APP_REQUEST',
        "Request #{$request_id}: {$request_type} '{$app_name}'",
        $session['user_id'],
        null,
        $session['terminal_id']
    );

    json_response([
        'success'    => true,
        'request_id' => $request_id,
        'message'    => "Your {$request_type} request for \"{$app_name}\" has been submitted. The administrator has been notified.",
    ]);
}

json_response(['success' => false, 'error' => 'Method not allowed'], 405);
