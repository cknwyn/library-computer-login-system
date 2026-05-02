-- ============================================================
-- ORACLE APEX / ORACLE SQL COMPATIBLE SEED DATA
-- Optimized for Oracle 12c+ / APEX SQL Workshop
-- ============================================================

-- 1. AUF CAMPUS LOCATIONS
INSERT INTO campuses (id, name) VALUES (1, 'AUF - Main Campus');
INSERT INTO campuses (id, name) VALUES (2, 'AUF-IS - Santa Barbara Campus');

-- 2. AUF ROOMS
INSERT INTO rooms (id, campus_id, name) VALUES (1, 1, 'Learning Commons');
INSERT INTO rooms (id, campus_id, name) VALUES (2, 2, 'Highschool Library');
INSERT INTO rooms (id, campus_id, name) VALUES (3, 2, 'Grade school library');

-- 3. AUF COLLEGES
INSERT INTO colleges (id, name, code) VALUES (1, 'College of Allied Medical Professions', 'CAMP');
INSERT INTO colleges (id, name, code) VALUES (2, 'College of Computer Studies', 'CCS');
INSERT INTO colleges (id, name, code) VALUES (3, 'College of Arts and Sciences', 'CAS');
INSERT INTO colleges (id, name, code) VALUES (4, 'College of Nursing', 'CON');
INSERT INTO colleges (id, name, code) VALUES (5, 'College of Engineering and Architecture', 'CEA');
INSERT INTO colleges (id, name, code) VALUES (6, 'College of Business and Accountancy', 'CBA');
INSERT INTO colleges (id, name, code) VALUES (7, 'College of Criminal Justice Education', 'CCJE');
INSERT INTO colleges (id, name, code) VALUES (8, 'College of Education', 'CED');
INSERT INTO colleges (id, name, code) VALUES (9, 'School of Medicine', 'SOM');
INSERT INTO colleges (id, name, code) VALUES (10, 'School of Law', 'SOL');
INSERT INTO colleges (id, name, code) VALUES (11, 'Senior Highschool', 'SHS');

-- 4. DEPARTMENTS
INSERT INTO departments (id, college_id, name) VALUES (1, 1, 'Department of Physical Therapy');
INSERT INTO departments (id, college_id, name) VALUES (2, 1, 'Department of Medical Technology');
INSERT INTO departments (id, college_id, name) VALUES (3, 2, 'Department of Information Technology');
INSERT INTO departments (id, college_id, name) VALUES (4, 2, 'Department of Computer Science');
INSERT INTO departments (id, college_id, name) VALUES (5, 2, 'Department of Multimedia Arts');
INSERT INTO departments (id, college_id, name) VALUES (6, 3, 'Department of Psychology');
INSERT INTO departments (id, college_id, name) VALUES (7, 5, 'Department of Civil Engineering');
INSERT INTO departments (id, college_id, name) VALUES (8, 5, 'Department of Architecture');
INSERT INTO departments (id, college_id, name) VALUES (9, 6, 'Department of Accounting');
INSERT INTO departments (id, college_id, name) VALUES (10, 4, 'Department of Nursing');
INSERT INTO departments (id, college_id, name) VALUES (11, 7, 'Department of Criminology');
INSERT INTO departments (id, college_id, name) VALUES (12, 8, 'Department of Education');
INSERT INTO departments (id, college_id, name) VALUES (13, 9, 'School of Medicine');
INSERT INTO departments (id, college_id, name) VALUES (14, 10, 'School of Law');

-- 5. DEGREES
INSERT INTO degrees (id, department_id, name) VALUES (1, 2, 'BS Medical Technology');
INSERT INTO degrees (id, department_id, name) VALUES (2, 2, 'BS Pharmacy');
INSERT INTO degrees (id, department_id, name) VALUES (3, 4, 'BS Computer Science');
INSERT INTO degrees (id, department_id, name) VALUES (4, 3, 'BS Information Technology');
INSERT INTO degrees (id, department_id, name) VALUES (5, 5, 'B Multimedia Arts');
INSERT INTO degrees (id, department_id, name) VALUES (6, 6, 'BS Psychology');
INSERT INTO degrees (id, department_id, name) VALUES (7, 8, 'BS Architecture');
INSERT INTO degrees (id, department_id, name) VALUES (8, 7, 'BS Civil Engineering');
INSERT INTO degrees (id, department_id, name) VALUES (9, 10, 'BS Nursing');
INSERT INTO degrees (id, department_id, name) VALUES (10, 11, 'BS Criminology');
INSERT INTO degrees (id, department_id, name) VALUES (11, 12, 'B Elementary Education');
INSERT INTO degrees (id, department_id, name) VALUES (12, 13, 'Doctor of Medicine');
INSERT INTO degrees (id, department_id, name) VALUES (13, 14, 'Juris Doctor');

-- 6. SPECIALIZATIONS
INSERT INTO specializations (id, degree_id, name) VALUES (1, 4, 'Network Security');
INSERT INTO specializations (id, degree_id, name) VALUES (2, 4, 'Data Science');
INSERT INTO specializations (id, degree_id, name) VALUES (3, 8, 'Structural Engineering');
INSERT INTO specializations (id, degree_id, name) VALUES (4, 6, 'Clinical Psychology');

-- 7. TERMINALS
INSERT INTO terminals (id, terminal_code, terminal_name, campus_id, room_id, status) VALUES 
(1, 'AUF-MAIN-LC-01', 'Learning Commons PC 01', 1, 1, 'online');
INSERT INTO terminals (id, terminal_code, terminal_name, campus_id, room_id, status) VALUES 
(2, 'AUF-IS-HS-LIB', 'IS Highschool Library Station', 2, 2, 'online');

-- 8. ADMIN ACCOUNT
-- Assuming Admin ID 1
INSERT INTO users (id, user_id, first_name, last_name, name, password_hash, role)
VALUES (999, 'admin', 'Library', 'Admin', 'Library Admin', '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O', 'admin');

INSERT INTO admins (id, username, password_hash, name, email)
VALUES (1, 'admin', '$2y$10$8K9O6O1qX9O6O1qX9O6O1uJ9O6O1qX9O6O1qX9O6O1qX9O6O1qX9O', 'Library System Admin', 'admin@auf.edu.ph');

COMMIT;
