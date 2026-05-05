<?php
/**
 * Standalone Utility: Fix Admin Login
 * This script resets the 'admin' password to 'Admin@1234' in the database.
 */
require_once __DIR__ . '/includes/db.php';

header('Content-Type: text/plain');

echo "--- Library System Admin Recovery Tool ---\n";

try {
    $pdo = db();
    
    // The correct hash for 'Admin@1234'
    $newHash = '$2y$10$0i2fBEtTfw6YspniXA0cme.MQ63jnMnOqY9wJa/rz3De2.ASBQMkm';
    
    echo "Attempting to update 'admins' table...\n";
    $stmt1 = $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = 'admin'");
    $stmt1->execute([$newHash]);
    $affected1 = $stmt1->rowCount();
    
    echo "Attempting to update 'users' table (admin role)...\n";
    $stmt2 = $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = 'admin'");
    $stmt2->execute([$newHash]);
    $affected2 = $stmt2->rowCount();

    if ($affected1 > 0 || $affected2 > 0) {
        echo "SUCCESS: Admin password has been reset to: Admin@1234\n";
        echo "Records updated: " . ($affected1 + $affected2) . "\n";
        echo "You can now log in at /admin/index.php\n";
    } else {
        echo "NOTICE: No records were updated. Either the password is already correct, or the 'admin' user does not exist.\n";
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\nIMPORTANT: Delete this file after use for security reasons.\n";
