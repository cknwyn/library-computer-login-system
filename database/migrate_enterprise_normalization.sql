-- ============================================================
-- DATABASE NORMALIZATION (Phase 3: Enterprise Integrity)
-- ============================================================

-- 1. Create WEBSITES lookup table
CREATE TABLE IF NOT EXISTS websites (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url           TEXT NOT NULL,
    title         VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_url_hash (url(255))
) ENGINE=InnoDB;

-- 2. Migrate existing website_logs to use website_id
ALTER TABLE website_logs ADD COLUMN website_id INT UNSIGNED DEFAULT NULL AFTER session_id;

-- Insert existing unique websites into the lookup table
INSERT IGNORE INTO websites (url, title)
SELECT DISTINCT url, title FROM website_logs;

-- Update website_logs with the new IDs
UPDATE website_logs wl
JOIN websites w ON wl.url = w.url
SET wl.website_id = w.id;

-- Make website_id mandatory and drop old columns
ALTER TABLE website_logs 
    MODIFY COLUMN website_id INT UNSIGNED NOT NULL,
    DROP COLUMN url,
    DROP COLUMN title;

-- Add Foreign Key for website_logs
ALTER TABLE website_logs 
    ADD CONSTRAINT fk_website_logs_website 
    FOREIGN KEY (website_id) REFERENCES websites(id) 
    ON UPDATE CASCADE ON DELETE CASCADE;

-- 3. Cleanup redundant text columns in USERS
-- (We keep the IDs: college_id, department_id, degree_id)
ALTER TABLE users 
    DROP COLUMN affiliation,
    DROP COLUMN department,
    DROP COLUMN degree;

-- 4. Cleanup redundant text column in TERMINALS
ALTER TABLE terminals 
    DROP COLUMN location;

-- 5. Tighten types (Optional but recommended for strict normalization)
ALTER TABLE users 
    MODIFY COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT NULL;
