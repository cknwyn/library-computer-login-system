-- ============================================================
-- ANGELES UNIVERSITY FOUNDATION (AUF) - NORMALIZED SEED DATA
-- Enterprise 3NF / AUF Identity Context
-- ============================================================

-- 1. AUF Campus Locations
INSERT INTO campuses (name) VALUES 
('Main Campus'), 
('Medical Center Campus');

INSERT INTO rooms (name, campus_id) VALUES 
('University Library (Main)', 1),
('Cyberzone - Information Commons', 1),
('High School Library', 1),
('Nursing Virtual Lab', 1),
('Medical Library (AUFMC)', 2),
('HS Cyberzone', 1);

-- 2. AUF Academic Hierarchy
INSERT INTO colleges (name, code) VALUES 
('College of Computer Studies', 'CCS'),
('College of Nursing', 'CON'),
('College of Engineering and Architecture', 'CEA'),
('College of Business and Accountancy', 'CBA'),
('College of Arts and Sciences', 'CAS'),
('College of Allied Medical Professions', 'CAMP'),
('College of Criminal Justice Education', 'CCJE'),
('College of Education', 'CED'),
('School of Medicine', 'SOM'),
('School of Law', 'SOL'),
('Graduate School', 'GS');

-- CCS Departments
INSERT INTO departments (name, college_id) VALUES 
('Information Technology', 1), 
('Computer Science', 1);

-- CON Departments
INSERT INTO departments (name, college_id) VALUES 
('Nursing Education', 2);

-- CEA Departments
INSERT INTO departments (name, college_id) VALUES 
('Civil Engineering', 3), 
('Electrical Engineering', 3), 
('Architecture', 3);

-- CBA Departments
INSERT INTO departments (name, college_id) VALUES 
('Accountancy', 4), 
('Management and Marketing', 4);

-- Degrees (Sample AUF Programs)
INSERT INTO degrees (name, department_id) VALUES 
('BS in Information Technology', 1),
('BS in Computer Science', 2),
('Bachelor of Science in Nursing', 3),
('BS in Civil Engineering', 4),
('BS in Architecture', 6),
('BS in Accountancy', 7);

-- 3. Users (AUF Identities)
-- Passwords are set to 'admin' using the known compatible hash:
-- Jane Reyes (CCS Student)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role, college_id, department_id, degree_id, gender, year, batch, cadre, speciality)
VALUES ('24-1234-567', 'Jane', 'Reyes', 'Jane Reyes', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'student', 1, 1, 1, 'Female', '1st', 3, 'Undergraduate', 'Infrastructure');

-- John Santos (Nursing Student)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role, college_id, department_id, degree_id, gender, year, batch, cadre, speciality)
VALUES ('22-0987-123', 'John', 'Santos', 'John Santos', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'student', 2, 3, 3, 'Male', '3rd', 1, 'Undergraduate', 'Clinical Nursing');

-- Maria Dela Cruz (Faculty/Staff)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role, college_id, department_id, designation, gender)
VALUES ('F-99001', 'Maria', 'Dela Cruz', 'Maria Dela Cruz', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'staff', 1, 1, 'Associate Professor', 'Female');

-- Admin Account (Dashboard & Patron)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role)
VALUES ('admin', 'System', 'Administrator', 'System Administrator', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'admin');

INSERT INTO admins (username, password_hash, name, email)
VALUES ('admin', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'AUF System Admin', 'admin@auf.edu.ph');

-- 4. Terminals (AUF Catalog)
INSERT INTO terminals (terminal_code, terminal_name, campus_id, room_id, status) VALUES 
('AUF-MAIN-LIB-01', 'Reference Desk PC 1', 1, 1, 'online'),
('AUF-MAIN-LIB-02', 'Reference Desk PC 2', 1, 1, 'online'),
('AUF-CYBER-01', 'Cyberzone Workstation 01', 1, 2, 'online'),
('AUF-CYBER-02', 'Cyberzone Workstation 02', 1, 2, 'online'),
('AUF-MC-LIB-01', 'Med-Lib Search Terminal', 2, 5, 'online'),
('AUF-HS-LIB-01', 'HS Library Terminal 1', 1, 3, 'maintenance');

-- 5. Standard Research Websites
INSERT INTO websites (url, title) VALUES 
('https://www.auf.edu.ph', 'Angeles University Foundation'),
('https://library.auf.edu.ph', 'AUF University Library'),
('https://canvas.auf.edu.ph', 'AUF Canvas LMS'),
('https://google.com', 'Google Search'),
('https://wikipedia.org', 'Wikipedia'),
('https://github.com', 'GitHub'),
('https://sciencedirect.com', 'ScienceDirect Research');

-- 6. Simulated Session History (Realistic AUF Context)
-- Jane Reyes (CCS Student) on Cyberzone Station
INSERT INTO sessions (user_id, terminal_id, session_token, login_time, logout_time, duration_seconds, status)
VALUES (1, 3, 'token-jane-ccs', NOW() - INTERVAL 4 HOUR, NOW() - INTERVAL 3 HOUR, 3600, 'completed');

INSERT INTO website_logs (session_id, user_id, website_id, visited_at) VALUES 
(1, 1, 3, NOW() - INTERVAL 235 MINUTE),
(1, 1, 6, NOW() - INTERVAL 220 MINUTE),
(1, 1, 4, NOW() - INTERVAL 200 MINUTE);

-- John Santos (CON Student) on Main Library Terminal
INSERT INTO sessions (user_id, terminal_id, session_token, login_time, logout_time, duration_seconds, status)
VALUES (2, 1, 'token-john-con', NOW() - INTERVAL 45 MINUTE, NULL, NULL, 'active');

INSERT INTO website_logs (session_id, user_id, website_id, visited_at) VALUES 
(2, 2, 7, NOW() - INTERVAL 30 MINUTE),
(2, 2, 2, NOW() - INTERVAL 10 MINUTE);
