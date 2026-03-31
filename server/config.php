<?php
// ============================================================
// Library Computer Login System — Configuration
// ============================================================
// Copy this file and set your environment variables,
// OR edit the defaults below for local XAMPP development.
// ============================================================

// ── Database ─────────────────────────────────────────────────
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'library_system');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');           // Default XAMPP root has no password

// ── Application ───────────────────────────────────────────────
define('APP_NAME',    'Library Computer System');
define('APP_VERSION', '1.0.0');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost/library-system');

// ── Session ───────────────────────────────────────────────────
// How many seconds without a heartbeat before a session is declared abandoned
define('SESSION_ABANDON_THRESHOLD', 120); // 2 minutes

// Token expiry for API session tokens (seconds). 0 = never expire (rely on heartbeat)
define('TOKEN_LIFETIME', 0);

// ── Security ──────────────────────────────────────────────────
// Used for HMAC signing — change this to a long random string in production!
define('APP_SECRET', getenv('APP_SECRET') ?: 'change-this-in-production-please-use-random-string');

// ── Admin Panel ───────────────────────────────────────────────
define('ADMIN_SESSION_LIFETIME', 3600); // 1 hour of inactivity logs admin out

// ── Error Reporting ───────────────────────────────────────────
// Set to false in production
define('DEBUG_MODE', true);

if (DEBUG_MODE) {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// ── Timezone ──────────────────────────────────────────────────
date_default_timezone_set('Asia/Manila'); // Adjust to your timezone
