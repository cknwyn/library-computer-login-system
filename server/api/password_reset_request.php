<?php
// ============================================================
// API — Request Password Reset — /api/password_reset_request.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$user_id_val = trim($input['user_id'] ?? '');

if (empty($user_id_val)) {
    json_response(['success' => false, 'error' => 'Student/Staff ID is required'], 400);
}

$pdo = db();

// 1. Find user
$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE user_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$user_id_val]);
$user = $stmt->fetch();

if (!$user) {
    // For security, don't reveal if user exists or not, 
    // but in a kiosk system it might be better to be helpful.
    // However, we'll stay semi-vague if email is missing.
    json_response(['success' => false, 'error' => 'User not found or inactive'], 404);
}

if (empty($user['email'])) {
    json_response(['success' => false, 'error' => 'No email address associated with this account. Please contact the librarian.'], 400);
}

// 2. Generate 6-digit code
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$token_hash = password_hash($code, PASSWORD_BCRYPT); // Store hashed for security
$expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

try {
    $pdo->beginTransaction();
    
    // 3. Clear existing resets for this user
    $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
    
    // 4. Save new reset token
    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$user['id'], $token_hash, $expires_at]);
    
    // 5. Send email
    $subject = "Password Reset Code - " . APP_NAME;
    $message = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px;'>
            <h2 style='color: #1e293b; margin-top: 0;'>Password Reset Request</h2>
            <p style='color: #475569;'>Hello <strong>" . h($user['name']) . "</strong>,</p>
            <p style='color: #475569;'>You requested to reset your password for the AUF Library System. Use the code below to proceed:</p>
            <div style='background: #f8fafc; padding: 24px; border-radius: 8px; text-align: center; margin: 30px 0;'>
                <span style='font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #005FB8;'>" . $code . "</span>
            </div>
            <p style='color: #64748b; font-size: 13px;'>This code will expire in 15 minutes. If you did not request this, please ignore this email.</p>
            <hr style='border: 0; border-top: 1px solid #f1f5f9; margin: 30px 0;'>
            <p style='color: #94a3b8; font-size: 11px; text-align: center;'>&copy; " . date('Y') . " " . APP_NAME . "</p>
        </div>
    ";
    
    if (send_email($user['email'], $subject, $message)) {
        $pdo->commit();
        json_response(['success' => true, 'message' => 'Verification code sent to ' . substr($user['email'], 0, 3) . '***' . substr($user['email'], strpos($user['email'], '@'))]);
    } else {
        $pdo->rollBack();
        json_response(['success' => false, 'error' => 'Failed to send email. Please try again later.'], 500);
    }
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    json_response(['success' => false, 'error' => 'Server error: ' . $e->getMessage()], 500);
}
