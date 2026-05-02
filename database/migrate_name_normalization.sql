-- ============================================================
-- Migration: Name Normalization (Title, First, Middle, Last)
-- ============================================================

-- 1. Add new columns to users table
ALTER TABLE users
ADD COLUMN first_name  VARCHAR(100) DEFAULT NULL AFTER user_id,
ADD COLUMN middle_name VARCHAR(100) DEFAULT NULL AFTER first_name,
ADD COLUMN last_name   VARCHAR(100) DEFAULT NULL AFTER middle_name;

-- 2. Heuristic name splitting for existing data
-- Format A: "Last, First Middle"
UPDATE users 
SET 
    last_name   = TRIM(SUBSTRING_INDEX(name, ',', 1)),
    first_name  = TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(name, ',', -1), ' ', 2)),
    middle_name = IF(LOCATE(' ', TRIM(SUBSTRING_INDEX(name, ',', -1))) > 0, 
                     TRIM(SUBSTRING_INDEX(TRIM(SUBSTRING_INDEX(name, ',', -1)), ' ', -1)), 
                     NULL)
WHERE name LIKE '%,%';

-- Format B: "First Middle Last" (no comma)
UPDATE users
SET
    first_name  = SUBSTRING_INDEX(TRIM(name), ' ', 1),
    last_name   = IF(LOCATE(' ', TRIM(name)) > 0, SUBSTRING_INDEX(TRIM(name), ' ', -1), NULL),
    middle_name = IF(LENGTH(TRIM(name)) - LENGTH(REPLACE(TRIM(name), ' ', '')) >= 2,
                     SUBSTRING_INDEX(SUBSTRING_INDEX(TRIM(name), ' ', 2), ' ', -1),
                     NULL)
WHERE name NOT LIKE '%,%' AND name LIKE '% %';

-- Fallback: If only one name exists
UPDATE users SET first_name = name WHERE first_name IS NULL AND last_name IS NULL;
