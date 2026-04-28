<?php
// ============================================================
// Shared helper functions
// ============================================================
require_once __DIR__ . '/../config.php';

/**
 * Output a JSON response and exit.
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Generate a cryptographically secure session token.
 */
function generate_token(int $bytes = 32): string {
    return bin2hex(random_bytes($bytes));
}

/**
 * Format seconds into a human-readable duration string.
 * e.g. 3661 → "1h 1m 1s"
 */
function format_duration(int $seconds): string {
    $h = intdiv($seconds, 3600);
    $m = intdiv($seconds % 3600, 60);
    $s = $seconds % 60;
    $parts = [];
    if ($h > 0) $parts[] = "{$h}h";
    if ($m > 0) $parts[] = "{$m}m";
    $parts[] = "{$s}s";
    return implode(' ', $parts);
}

/**
 * Log an event to the activity_logs table.
 */
function log_activity(
    string $action,
    ?string $details    = null,
    ?int $user_id       = null,
    ?int $admin_id      = null,
    ?int $terminal_id   = null
): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = db()->prepare(
            'INSERT INTO activity_logs (user_id, admin_id, terminal_id, action, details, ip_address)
             VALUES (:uid, :aid, :tid, :action, :details, :ip)'
        );
        $stmt->execute([
            ':uid'     => $user_id,
            ':aid'     => $admin_id,
            ':tid'     => $terminal_id,
            ':action'  => $action,
            ':details' => $details,
            ':ip'      => $ip,
        ]);
    } catch (Exception $e) {
        // Non-fatal; logging failure should not break request
    }
}

/**
 * Sanitize a string for HTML output.
 */
function h(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirect to a URL (for admin panel pages).
 */
function redirect(string $url): void {
    header("Location: {$url}");
    exit;
}

/**
 * Return the base path for the admin panel.
 */
function admin_url(string $page = ''): string {
    return APP_URL . '/admin/' . ltrim($page, '/');
}

/**
 * Returns elapsed seconds from a timestamp to now.
 */
function elapsed_since(string $timestamp): int {
    return max(0, time() - strtotime($timestamp));
}

/**
 * Mark sessions that haven't had a heartbeat as abandoned.
 */
function cleanup_abandoned_sessions(): void {
    try {
        $threshold = SESSION_ABANDON_THRESHOLD;
        $stmt = db()->prepare(
            "UPDATE sessions
             SET    status           = 'abandoned',
                    logout_time      = last_heartbeat,
                    duration_seconds = TIMESTAMPDIFF(SECOND, login_time, last_heartbeat)
             WHERE  status           = 'active'
               AND  last_heartbeat IS NOT NULL
               AND  TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) > :threshold"
        );
        $stmt->execute([':threshold' => $threshold]);
    } catch (Exception $e) {
        // Non-fatal
    }
}

/**
 * Standardizes college/affiliation names based on common abbreviations.
 */
function standardize_affiliation(?string $text): ?string {
    if (!$text) return null;
    $text = trim($text);
    $map = [
        'CAMP'   => 'College of Allied Medical Professions (CAMP)',
        'CAS'    => 'College of Arts and Sciences (CAS)',
        'CBA'    => 'College of Business and Accountancy (CBA)',
        'CCS'    => 'College of Computer Studies (CCS)',
        'CCJE'   => 'College of Criminal Justice Education (CCJE)',
        'CED'    => 'College of Education (CED)',
        'CEA'    => 'College of Engineering and Architecture (CEA)',
        'CON'    => 'College of Nursing (CON)',
        'AUF-IS' => 'AUF Integrated School (AUF-IS)',
        'AUF IS' => 'AUF Integrated School (AUF-IS)',
        'MED'    => 'School of Medicine',
        'LAW'    => 'School of Law'
    ];
    
    $upper = strtoupper($text);
    // First, check if it's a direct abbreviation match
    if (isset($map[$upper])) return $map[$upper];
    
    // Then, check if it already contains the full name to avoid double-processing
    foreach ($map as $abbr => $full) {
        if (stripos($text, $full) !== false) return $full;
        if (stripos($text, $abbr) !== false && strlen($text) < 10) return $full; // Catch short strings like "ccs student"
    }
    
    return $text; 
}

/**
 * Standardizes department/course names.
 */
function standardize_department(?string $text): ?string {
    if (!$text) return null;
    $text = trim($text);
    // Standardize common BS/MS prefixes
    $text = preg_replace('/^bs\s+/i', 'BS ', $text);
    $text = preg_replace('/^ms\s+/i', 'MS ', $text);
    return $text;
}
