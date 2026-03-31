<?php
// ============================================================
// Admin panel authentication helpers
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

/**
 * Start and configure the PHP session for the admin panel.
 */
function admin_session_start(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => false, // Set true when using HTTPS
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

/**
 * Return true if an admin is currently logged in.
 */
function admin_is_logged_in(): bool {
    admin_session_start();
    if (empty($_SESSION['admin_id'])) {
        return false;
    }
    // Check inactivity timeout
    if (!empty($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) > ADMIN_SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Redirect to admin login if not authenticated.
 */
function require_admin_login(): void {
    if (!admin_is_logged_in()) {
        redirect(APP_URL . '/admin/index.php?expired=1');
    }
}

/**
 * Attempt to log in an admin. Returns admin row on success or false.
 */
function admin_login(string $username, string $password): array|false {
    $stmt = db()->prepare('SELECT * FROM admins WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch();
    if ($admin && password_verify($password, $admin['password_hash'])) {
        admin_session_start();
        session_regenerate_id(true);
        $_SESSION['admin_id']      = $admin['id'];
        $_SESSION['admin_name']    = $admin['name'];
        $_SESSION['last_activity'] = time();
        return $admin;
    }
    return false;
}

/**
 * Get the currently logged-in admin's data from session.
 */
function current_admin(): array {
    return [
        'id'   => $_SESSION['admin_id']   ?? null,
        'name' => $_SESSION['admin_name'] ?? 'Admin',
    ];
}

/**
 * Log out the current admin.
 */
function admin_logout(): void {
    admin_session_start();
    session_unset();
    session_destroy();
}

/**
 * Verify a token header sent by the Electron client.
 * Returns the session row on success or null.
 */
function verify_client_token(): ?array {
    $token = $_SERVER['HTTP_X_SESSION_TOKEN'] ?? ($_GET['token'] ?? '');
    if (empty($token)) return null;

    $stmt = db()->prepare(
        "SELECT s.*, u.name AS user_name, u.user_id AS user_code, u.role,
                t.terminal_code
         FROM   sessions s
         JOIN   users     u ON u.id = s.user_id
         JOIN   terminals t ON t.id = s.terminal_id
         WHERE  s.session_token = :token
           AND  s.status = 'active'
         LIMIT  1"
    );
    $stmt->execute([':token' => $token]);
    return $stmt->fetch() ?: null;
}
