<?php
// ============================================================
// Database connection singleton using PDO
// ============================================================
require_once __DIR__ . '/../config.php';

class Database {
    private static ?PDO $instance = null;

    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                DB_HOST, DB_PORT, DB_NAME
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                // Sync timezone
                $tz = date('P');
                self::$instance->exec("SET time_zone = '{$tz}'");
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    die(json_encode(['success' => false, 'error' => 'DB connection failed: ' . $e->getMessage()]));
                }
                die(json_encode(['success' => false, 'error' => 'Database unavailable. Please contact the administrator.']));
            }
        }
        return self::$instance;
    }

    // Prevent cloning
    private function __clone() {}
}

// Convenience shorthand
function db(): PDO {
    return Database::get();
}
