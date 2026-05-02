-- ============================================================
-- Migration: Database Normalization (Colleges, Depts, Degrees)
-- ============================================================

-- 1. Create new lookup tables
CREATE TABLE IF NOT EXISTS colleges (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(50)  DEFAULT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS departments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_id INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    UNIQUE KEY (college_id, name),
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS degrees (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    UNIQUE KEY (department_id, name),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Add foreign key columns to users table
ALTER TABLE users 
ADD COLUMN college_id    INT UNSIGNED DEFAULT NULL AFTER affiliation,
ADD COLUMN department_id INT UNSIGNED DEFAULT NULL AFTER department,
ADD COLUMN degree_id     INT UNSIGNED DEFAULT NULL AFTER degree;

-- 3. Populate Colleges from existing users.affiliation
INSERT IGNORE INTO colleges (name)
SELECT DISTINCT affiliation FROM users WHERE affiliation IS NOT NULL AND affiliation != '' AND affiliation != '-';

-- 4. Populate Departments from existing users.department
-- Note: This is tricky because we need to link it to a college. 
-- We'll link it to the college that the user who has this department is currently affiliated with.
INSERT IGNORE INTO departments (college_id, name)
SELECT DISTINCT c.id, u.department 
FROM users u
JOIN colleges c ON u.affiliation = c.name
WHERE u.department IS NOT NULL AND u.department != '' AND u.department != '-';

-- 5. Populate Degrees from existing users.degree
INSERT IGNORE INTO degrees (department_id, name)
SELECT DISTINCT d.id, u.degree
FROM users u
JOIN departments d ON u.department = d.name
WHERE u.degree IS NOT NULL AND u.degree != '' AND u.degree != '-';

-- 6. Link existing users to the new tables
UPDATE users u
JOIN colleges c ON u.affiliation = c.name
SET u.college_id = c.id;

UPDATE users u
JOIN departments d ON u.department = d.name
SET u.department_id = d.id;

UPDATE users u
JOIN degrees deg ON u.degree = deg.name
SET u.degree_id = deg.id;

-- 7. Add foreign key constraints
ALTER TABLE users
ADD CONSTRAINT fk_user_college FOREIGN KEY (college_id) REFERENCES colleges(id) ON UPDATE CASCADE ON DELETE SET NULL,
ADD CONSTRAINT fk_user_department FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
ADD CONSTRAINT fk_user_degree FOREIGN KEY (degree_id) REFERENCES degrees(id) ON UPDATE CASCADE ON DELETE SET NULL;
