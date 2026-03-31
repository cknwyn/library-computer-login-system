<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';
admin_session_start();
$admin = current_admin();
log_activity('ADMIN_LOGOUT', 'Admin signed out', null, $admin['id'] ?? null);
admin_logout();
redirect(APP_URL . '/admin/index.php');
