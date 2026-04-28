-- ============================================================
-- Library Computer Login System - Seed Data
-- Run AFTER schema.sql
-- ============================================================

USE library_system;

-- ============================================================
-- DEFAULT ADMIN ACCOUNT
-- Username: admin | Password: Admin@1234
-- CHANGE THIS PASSWORD IN PRODUCTION!
-- ============================================================
INSERT INTO admins (username, password_hash, name, email) VALUES
(
    'admin',
    '$2y$12$m7NlCcFvGaMtyY/CtqKOHuscHiPJpBy6sJMINlZkMdE5WrfezOBH.', -- Admin@1234
    'System Administrator',
    'admin@library.edu'
);

-- ============================================================
-- SAMPLE USERS
-- ============================================================
INSERT INTO users (user_id, name, password_hash, role, affiliation, department, email, gender, creation_date) VALUES
('21-0000-001', 'Ana Reyes',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Computing', 'Computer Science',    'ana.reyes@student.edu', 'Female', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('21-0000-002', 'Ben Santos',     '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Computing', 'Information Technology','ben.santos@student.edu', 'Male', DATE_SUB(NOW(), INTERVAL 4 DAY)),
('22-0000-010', 'Carla Mendoza',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Education (CED)', 'Elementary Education', 'carla.mendoza@student.edu', 'Female', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('15-0000-001', 'Dr. Jose Cruz',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Graduate School',      'Faculty',              'jose.cruz@library.edu', 'Male', DATE_SUB(NOW(), INTERVAL 10 DAY)),
('18-0000-002', 'Maria Lim',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Library Services',     'Library Services',     'maria.lim@library.edu', 'Female', DATE_SUB(NOW(), INTERVAL 20 DAY)),
('23-0000-005', 'John Doe',       '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Engineering', 'Civil Engineering',   'john.doe@student.edu', 'Male', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('23-0000-006', 'Jane Smith',     '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Engineering', 'Electrical Engineering','jane.smith@student.edu', 'Female', DATE_SUB(NOW(), INTERVAL 1 DAY));


-- ============================================================
-- TERMINALS (nComputing stations)
-- ============================================================
INSERT INTO terminals (terminal_code, location, status) VALUES
('PC-01', 'Reading Room A', 'offline'),
('PC-02', 'Reading Room A', 'offline'),
('PC-03', 'Reading Room A', 'offline'),
('PC-04', 'Reading Room B', 'offline'),
('PC-05', 'Reading Room B', 'offline'),
('PC-06', 'Study Hall',     'offline'),
('PC-07', 'Study Hall',     'offline'),
('PC-08', 'Study Hall',     'offline'),
('LOCAL-TEST', 'Developer Machine', 'offline');


-- ============================================================
-- SAMPLE SESSIONS
-- ============================================================
-- Adding some completed sessions for report testing
INSERT INTO sessions (user_id, terminal_id, login_time, logout_time, duration_seconds, status) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 1 HOUR), 3600, 'completed'),
(2, 2, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 45 MINUTE), 2700, 'completed'),
(3, 3, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 2 HOUR), 7200, 'completed'),
(4, 4, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 3 DAY), INTERVAL 30 MINUTE), 1800, 'completed'),
(1, 5, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 90 MINUTE), 5400, 'completed'),
(6, 6, DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_ADD(DATE_SUB(NOW(), INTERVAL 5 HOUR), INTERVAL 1 HOUR), 3600, 'completed'),
(7, 7, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_ADD(DATE_SUB(NOW(), INTERVAL 4 HOUR), INTERVAL 2 HOUR), 7200, 'completed');


-- ============================================================
-- SAMPLE WEBSITE LOGS
-- ============================================================
INSERT INTO website_logs (session_id, user_id, url, title, visited_at) VALUES
(1, 1, 'https://www.google.com', 'Google Search', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(1, 1, 'https://en.wikipedia.org/wiki/Library', 'Library - Wikipedia', DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 5 MINUTE)),
(2, 2, 'https://github.com', 'GitHub: Let’s build from here', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 3, 'https://scholar.google.com', 'Google Scholar', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(5, 1, 'https://www.jstor.org', 'JSTOR', DATE_SUB(NOW(), INTERVAL 1 DAY)),
(6, 6, 'https://www.autodesk.com', 'Autodesk', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(7, 7, 'https://ieeexplore.ieee.org', 'IEEE Xplore', DATE_SUB(NOW(), INTERVAL 4 HOUR));
