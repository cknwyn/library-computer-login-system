<?php
// ============================================================
// API — Verify Reset & Update Password — /api/password_reset_verify.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$user_id_val  = trim($input['user_id'] ?? '');
$code         = trim($input['code'] ?? '');
$new_password = trim($input['new_password'] ?? '');

if (empty($user_id_val) || empty($code) || empty($new_password)) {
    json_response(['success' => false, 'error' => 'All fields are required'], 400);
}

if (strlen($new_password) < 6) {
    json_response(['success' => false, 'error' => 'Password must be at least 6 characters'], 400);
}

$pdo = db();

// 1. Find user
$stmt = $pdo->prepare("SELECT id FROM users WHERE user_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$user_id_val]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['success' => false, 'error' => 'Invalid user or session'], 404);
}

// 2. Find and verify reset token
$stmt = $pdo->prepare("SELECT token, expires_at FROM password_resets WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user['id']]);
$reset = $stmt->fetch();

if (!$reset) {
    json_response(['success' => false, 'error' => 'No reset request found for this user'], 400);
}

if (strtotime($reset['expires_at']) < time()) {
    json_response(['success' => false, 'error' => 'Code has expired. Please request a new one.'], 400);
}

if (!password_verify($code, $reset['token'])) {
    json_response(['success' => false, 'error' => 'Invalid verification code'], 400);
}

// 3. Update password
try {
    $pdo->beginTransaction();
    
    $new_hash = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$new_hash, $user['id']]);
    
    // 4. Delete the used token
    $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
    
    // 5. Log activity
    log_activity('PASSWORD_RESET_EMAIL', "User {$user_id_val} reset password via email", $user['id']);
    
    $pdo->commit();
    json_response(['success' => true, 'message' => 'Password updated successfully. You can now log in.']);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}
