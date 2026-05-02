<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>System Database Optimization</h2>";
$pdo = db();

try {
    // 1. Create password_resets table
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB;";
    $pdo->exec($sql);
    echo "<p style='color:green;'>✅ SUCCESS: Table 'password_resets' is ready.</p>";

    // 2. Add performance indexes to 'users' table
    // Note: We check if they exist first (MySQL 8.0+ or try-catch)
    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_name (name)");
        echo "<p style='color:green;'>✅ SUCCESS: Search index for 'name' added.</p>";
    } catch (Exception $e) {
        echo "<p style='color:gray;'>ℹ️ INFO: Index 'idx_name' already exists or could not be added.</p>";
    }

    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_email (email)");
        echo "<p style='color:green;'>✅ SUCCESS: Search index for 'email' added.</p>";
    } catch (Exception $e) {
        echo "<p style='color:gray;'>ℹ️ INFO: Index 'idx_email' already exists or could not be added.</p>";
    }

    echo "<hr><p><b>Your database is now optimized for searching and password recovery!</b></p>";
    echo "<p><a href='admin/users.php'>Go to User Management</a></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ ERROR: " . h($e->getMessage()) . "</p>";
}
