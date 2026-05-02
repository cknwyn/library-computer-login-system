<?php
require_once __DIR__ . '/../includes/db.php';
$pdo = db();

try {
    echo "Starting migration...<br>";

    // 1. Create specializations table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS specializations (
        id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        degree_id     INT UNSIGNED NOT NULL,
        name          VARCHAR(150) NOT NULL,
        UNIQUE KEY (degree_id, name),
        FOREIGN KEY (degree_id) REFERENCES degrees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "✓ Specializations table created/verified.<br>";

    // 2. Add specialization_id to users if not exists
    $columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'specialization_id'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN specialization_id INT UNSIGNED DEFAULT NULL AFTER degree_id");
        $pdo->exec("ALTER TABLE users ADD CONSTRAINT fk_user_specialization FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON UPDATE CASCADE ON DELETE SET NULL");
        echo "✓ Column 'specialization_id' added to users table.<br>";
    } else {
        echo "ℹ Column 'specialization_id' already exists.<br>";
    }

    // 3. Drop old speciality column if it exists (optional but cleaner)
    $old_cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'speciality'")->fetchAll();
    if (!empty($old_cols)) {
        // We might want to keep it or migrate data? 
        // For now, let's just keep it to be safe, or drop it if the user is okay.
        // The error happened because the NEW code uses specialization_id.
        echo "ℹ Old 'speciality' column still exists.<br>";
    }

    echo "<b>Migration successful!</b> You can now delete this file.";
} catch (Exception $e) {
    echo "<b style='color:red'>Migration failed:</b> " . $e->getMessage();
}
