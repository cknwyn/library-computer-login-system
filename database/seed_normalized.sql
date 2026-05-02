-- ============================================================
-- NORMALIZED SEED DATA (Enterprise 3NF)
-- ============================================================

-- 1. Locations
INSERT INTO campuses (name) VALUES ('Main Campus'), ('West Campus'), ('Medical Center');

INSERT INTO rooms (name, campus_id) VALUES 
('Library Hall A', 1), ('Library Hall B', 1), ('Cyber Zone', 1),
('Reading Room', 2), ('Medical Library', 3);

-- 2. Academic Hierarchy
INSERT INTO colleges (name) VALUES 
('College of Engineering'), ('College of Arts & Sciences'), ('School of Medicine'), ('College of Business');

INSERT INTO departments (name, college_id) VALUES 
('Computer Science', 1), ('Electrical Engineering', 1),
('Psychology', 2), ('Biology', 2),
('Nursing', 3), ('Pharmacy', 3),
('Accountancy', 4), ('Marketing', 4);

INSERT INTO degrees (name, department_id) VALUES 
('BS in Computer Science', 1), ('BS in Information Technology', 1),
('BS in Psychology', 3),
('BS in Nursing', 5),
('BS in Accountancy', 7);

-- 3. Users
-- Default passwords are same as user_id for convenience
-- Jane Doe (Student)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role, college_id, department_id, degree_id, gender, year)
VALUES ('2024-00001', 'Jane', 'Doe', 'Jane Doe', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'student', 1, 1, 1, 'Female', '3rd');

-- John Smith (Staff)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role, college_id, department_id, designation, gender)
VALUES ('STAFF-101', 'John', 'Smith', 'John Smith', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'staff', 1, 2, 'Lab Technician', 'Male');

-- Admin User (PATRON ACCOUNT)
INSERT INTO users (user_id, first_name, last_name, name, password_hash, role)
VALUES ('admin', 'System', 'Admin', 'System Admin', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'admin');

-- Dashboard Administrator (STAFF ACCOUNT)
INSERT INTO admins (username, password_hash, name, email)
VALUES ('admin', '$2y$12$N9qo8uLOickgx2ZMRZoMyeIjZAgNo8U8y.X6E/eM0D6R.K9R7N3Sy', 'System Administrator', 'admin@auf.edu.ph');

-- 4. Terminals
INSERT INTO terminals (terminal_code, terminal_name, campus_id, room_id, status) VALUES 
('PC-01', 'Reference Desk A', 1, 1, 'online'),
('PC-02', 'Reference Desk B', 1, 1, 'online'),
('LIB-C-01', 'Cyber Corner 1', 1, 3, 'online'),
('MED-01', 'Medical Lab Station', 3, 5, 'online');

-- 5. Websites
INSERT INTO websites (url, title) VALUES 
('https://google.com', 'Google'),
('https://wikipedia.org', 'Wikipedia'),
('https://github.com', 'GitHub'),
('https://stackoverflow.com', 'Stack Overflow'),
('https://library.auf.edu.ph', 'University Library');

-- 6. Sessions & Logs (Simulated History)
-- Session for Jane Doe
INSERT INTO sessions (user_id, terminal_id, session_token, login_time, logout_time, duration_seconds, status)
VALUES (1, 1, 'mock-token-jane-1', NOW() - INTERVAL 2 HOUR, NOW() - INTERVAL 1 HOUR, 3600, 'completed');

-- Website logs for Jane's session
INSERT INTO website_logs (session_id, user_id, website_id, visited_at) VALUES 
(1, 1, 1, NOW() - INTERVAL 115 MINUTE),
(1, 1, 5, NOW() - INTERVAL 110 MINUTE),
(1, 1, 2, NOW() - INTERVAL 90 MINUTE);

-- Session for John Smith
INSERT INTO sessions (user_id, terminal_id, session_token, login_time, logout_time, duration_seconds, status)
VALUES (2, 2, 'mock-token-john-1', NOW() - INTERVAL 30 MINUTE, NULL, NULL, 'active');

-- Website logs for John's active session
INSERT INTO website_logs (session_id, user_id, website_id, visited_at) VALUES 
(2, 2, 3, NOW() - INTERVAL 20 MINUTE),
(2, 2, 4, NOW() - INTERVAL 15 MINUTE);
