-- ============================================================
-- ANGELES UNIVERSITY FOUNDATION (AUF) - NORMALIZED SEED DATA
-- Comprehensive Academic Hierarchy & Sample Identities
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- CLEAR EXISTING DATA
DELETE FROM website_logs;
DELETE FROM sessions;
DELETE FROM users;
DELETE FROM admins;
DELETE FROM specializations;
DELETE FROM degrees;
DELETE FROM departments;
DELETE FROM colleges;
DELETE FROM rooms;
DELETE FROM campuses;

-- RESET AUTO-INCREMENT
ALTER TABLE campuses AUTO_INCREMENT = 1;
ALTER TABLE rooms AUTO_INCREMENT = 1;
ALTER TABLE colleges AUTO_INCREMENT = 1;
ALTER TABLE departments AUTO_INCREMENT = 1;
ALTER TABLE degrees AUTO_INCREMENT = 1;
ALTER TABLE specializations AUTO_INCREMENT = 1;
ALTER TABLE users AUTO_INCREMENT = 1;

-- 1. AUF CAMPUS LOCATIONS
INSERT INTO campuses (name) VALUES 
('AUF - Main Campus'), 
('AUF-IS - Santa Barbara Campus');

-- 2. AUF ROOMS (Lookup Campus ID by Name)
INSERT INTO rooms (name, campus_id) VALUES 
('Learning Commons', (SELECT id FROM campuses WHERE name = 'AUF - Main Campus' LIMIT 1)),
('Highschool Library', (SELECT id FROM campuses WHERE name = 'AUF-IS - Santa Barbara Campus' LIMIT 1)),
('Grade school library', (SELECT id FROM campuses WHERE name = 'AUF-IS - Santa Barbara Campus' LIMIT 1));

-- 3. AUF COLLEGES
INSERT INTO colleges (name, code) VALUES 
('College of Allied Medical Professions', 'CAMP'),
('College of Computer Studies', 'CCS'),
('College of Arts and Sciences', 'CAS'),
('College of Nursing', 'CON'),
('College of Engineering and Architecture', 'CEA'),
('College of Business and Accountancy', 'CBA'),
('College of Criminal Justice Education', 'CCJE'),
('College of Education', 'CED'),
('School of Medicine', 'SOM'),
('School of Law', 'SOL'),
('Grade School', 'GS'),
('Elementary', 'ELEM'),
('Junior Highschool', 'JHS'),
('Senior Highschool', 'SHS'),
('Graduate School', 'GRAD');

-- 4. DEPARTMENTS (Lookup College ID by Code)
INSERT INTO departments (name, college_id) VALUES 
('Department of Physical Therapy', (SELECT id FROM colleges WHERE code = 'CAMP' LIMIT 1)),
('Department of Occupational Therapy', (SELECT id FROM colleges WHERE code = 'CAMP' LIMIT 1)),
('Department of Radiologic Technology', (SELECT id FROM colleges WHERE code = 'CAMP' LIMIT 1)),
('Department of Medical Technology', (SELECT id FROM colleges WHERE code = 'CAMP' LIMIT 1)),
('Department of Pharmacy', (SELECT id FROM colleges WHERE code = 'CAMP' LIMIT 1)),
('Department of Information Technology', (SELECT id FROM colleges WHERE code = 'CCS' LIMIT 1)),
('Department of Computer Science', (SELECT id FROM colleges WHERE code = 'CCS' LIMIT 1)),
('Department of Multimedia Arts', (SELECT id FROM colleges WHERE code = 'CCS' LIMIT 1)),
('Department of Communication', (SELECT id FROM colleges WHERE code = 'CAS' LIMIT 1)),
('Department of Biological Sciences', (SELECT id FROM colleges WHERE code = 'CAS' LIMIT 1)),
('Department of Psychology', (SELECT id FROM colleges WHERE code = 'CAS' LIMIT 1)),
('Department of Civil Engineering', (SELECT id FROM colleges WHERE code = 'CEA' LIMIT 1)),
('Department of Architecture', (SELECT id FROM colleges WHERE code = 'CEA' LIMIT 1)),
('Department of Computer Engineering', (SELECT id FROM colleges WHERE code = 'CEA' LIMIT 1)),
('Department of Electronics Engineering', (SELECT id FROM colleges WHERE code = 'CEA' LIMIT 1)),
('Department of Accounting', (SELECT id FROM colleges WHERE code = 'CBA' LIMIT 1)),
('Department of Business Administration', (SELECT id FROM colleges WHERE code = 'CBA' LIMIT 1)),
('Department of Hospitality and Tourism Management', (SELECT id FROM colleges WHERE code = 'CBA' LIMIT 1)),
('Department of Nursing', (SELECT id FROM colleges WHERE code = 'CON' LIMIT 1)),
('Department of Criminology', (SELECT id FROM colleges WHERE code = 'CCJE' LIMIT 1)),
('Department of Education', (SELECT id FROM colleges WHERE code = 'CED' LIMIT 1)),
('School of Medicine', (SELECT id FROM colleges WHERE code = 'SOM' LIMIT 1)),
('School of Law', (SELECT id FROM colleges WHERE code = 'SOL' LIMIT 1)),
('General Academics', (SELECT id FROM colleges WHERE code = 'SHS' LIMIT 1));

-- 5. DEGREES (Lookup Department ID by Name)
INSERT INTO degrees (name, department_id) VALUES 
('BS Medical Technology', (SELECT id FROM departments WHERE name = 'Department of Medical Technology' LIMIT 1)),
('BS Occupational Therapy', (SELECT id FROM departments WHERE name = 'Department of Occupational Therapy' LIMIT 1)),
('BS Pharmacy', (SELECT id FROM departments WHERE name = 'Department of Pharmacy' LIMIT 1)),
('BS Clinical Pharmacy', (SELECT id FROM departments WHERE name = 'Department of Pharmacy' LIMIT 1)),
('BS Radiologic Technology', (SELECT id FROM departments WHERE name = 'Department of Radiologic Technology' LIMIT 1)),
('BS Physical Therapy', (SELECT id FROM departments WHERE name = 'Department of Physical Therapy' LIMIT 1)),
('BS Computer Science', (SELECT id FROM departments WHERE name = 'Department of Computer Science' LIMIT 1)),
('BS Information Technology', (SELECT id FROM departments WHERE name = 'Department of Information Technology' LIMIT 1)),
('B Multimedia Arts', (SELECT id FROM departments WHERE name = 'Department of Multimedia Arts' LIMIT 1)),
('AB Communication', (SELECT id FROM departments WHERE name = 'Department of Communication' LIMIT 1)),
('BS Biology', (SELECT id FROM departments WHERE name = 'Department of Biological Sciences' LIMIT 1)),
('BS Psychology', (SELECT id FROM departments WHERE name = 'Department of Psychology' LIMIT 1)),
('AB Psychology', (SELECT id FROM departments WHERE name = 'Department of Psychology' LIMIT 1)),
('BS Human Biology', (SELECT id FROM departments WHERE name = 'Department of Biological Sciences' LIMIT 1)),
('BS Architecture', (SELECT id FROM departments WHERE name = 'Department of Architecture' LIMIT 1)),
('BS Civil Engineering', (SELECT id FROM departments WHERE name = 'Department of Civil Engineering' LIMIT 1)),
('BS Computer Engineering', (SELECT id FROM departments WHERE name = 'Department of Computer Engineering' LIMIT 1)),
('BS Electronics Engineering', (SELECT id FROM departments WHERE name = 'Department of Electronics Engineering' LIMIT 1)),
('BS Nursing', (SELECT id FROM departments WHERE name = 'Department of Nursing' LIMIT 1)),
('BS Criminology', (SELECT id FROM departments WHERE name = 'Department of Criminology' LIMIT 1)),
('B Elementary Education', (SELECT id FROM departments WHERE name = 'Department of Education' LIMIT 1)),
('B Secondary Education', (SELECT id FROM departments WHERE name = 'Department of Education' LIMIT 1)),
('Doctor of Medicine', (SELECT id FROM departments WHERE name = 'School of Medicine' LIMIT 1)),
('Juris Doctor', (SELECT id FROM departments WHERE name = 'School of Law' LIMIT 1));

-- 6. SPECIALIZATIONS
INSERT INTO specializations (name, degree_id) VALUES 
('Network Security', (SELECT id FROM degrees WHERE name = 'BS Information Technology' LIMIT 1)),
('Data Science', (SELECT id FROM degrees WHERE name = 'BS Information Technology' LIMIT 1)),
('Structural Engineering', (SELECT id FROM degrees WHERE name = 'BS Civil Engineering' LIMIT 1)),
('Clinical Psychology', (SELECT id FROM degrees WHERE name = 'BS Psychology' LIMIT 1));

-- 7. TERMINALS (Lookup Room by Name and Campus by Name)
INSERT INTO terminals (terminal_code, terminal_name, campus_id, room_id, status) VALUES 
('AUF-MAIN-LC-01', 'Learning Commons PC 01', 
    (SELECT id FROM campuses WHERE name = 'AUF - Main Campus' LIMIT 1), 
    (SELECT id FROM rooms WHERE name = 'Learning Commons' LIMIT 1), 
    'online'),
('AUF-IS-HS-LIB', 'IS Highschool Library Station', 
    (SELECT id FROM campuses WHERE name = 'AUF-IS - Santa Barbara Campus' LIMIT 1), 
    (SELECT id FROM rooms WHERE name = 'Highschool Library' LIMIT 1), 
    'online');

-- 8. ADMIN ACCOUNT
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role)
VALUES ('admin', 'Library', 'Admin', 'Library Admin', '$2y$10$0i2fBEtTfw6YspniXA0cme.MQ63jnMnOqY9wJa/rz3De2.ASBQMkm', 'admin');

INSERT INTO admins (username, password_hash, name, email)
VALUES ('admin', '$2y$10$0i2fBEtTfw6YspniXA0cme.MQ63jnMnOqY9wJa/rz3De2.ASBQMkm', 'Library System Admin', 'admin@auf.edu.ph');

SET FOREIGN_KEY_CHECKS = 1;
