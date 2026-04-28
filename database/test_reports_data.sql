-- ============================================================
-- EXTRA TEST DATA (Run after schema.sql and original seed)
-- ============================================================
USE library_system;

-- 1. Ensure existing users have realistic AUF colleges/affiliations
UPDATE users SET affiliation = 'College of Computer Studies (CCS)', department = 'BS Computer Science' WHERE user_id = '21-0000-001';
UPDATE users SET affiliation = 'College of Computer Studies (CCS)', department = 'BS Information Technology' WHERE user_id = '21-0000-002';
UPDATE users SET affiliation = 'College of Education (CED)', department = 'Elementary Education' WHERE user_id = '22-0000-010';
UPDATE users SET affiliation = 'Graduate School', department = 'Master of Arts in Education' WHERE user_id = '15-0000-001';
UPDATE users SET affiliation = 'College of Nursing (CON)', department = 'BS Nursing' WHERE user_id = '18-0000-002';

-- 2. Add new sample users from other colleges
INSERT IGNORE INTO users (user_id, name, password_hash, role, affiliation, department, email, gender, creation_date) VALUES
('23-0000-005', 'John Doe',       '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Engineering and Architecture (CEA)', 'Civil Engineering',   'john.doe@student.edu', 'Male', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('23-0000-006', 'Jane Smith',     '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Business and Accountancy (CBA)', 'Accountancy','jane.smith@student.edu', 'Female', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('23-0000-007', 'Mark Wilson',    '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'College of Allied Medical Professions (CAMP)', 'BS Physical Therapy','mark.wilson@student.edu', 'Male', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('23-0000-008', 'Lisa Wong',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'AUF Integrated School (AUF-IS)', 'Senior High School','lisa.wong@student.edu', 'Female', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- 3. Add sample completed sessions spread over the last few days
-- Note: Using subqueries to find user IDs dynamically
-- Added INSERT IGNORE to allow re-running the script safely
INSERT IGNORE INTO sessions (user_id, terminal_id, login_time, logout_time, duration_seconds, status, session_token) VALUES
((SELECT id FROM users WHERE user_id='21-0000-001'), 1, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 1 HOUR), 3600, 'completed', 'TEST-TOKEN-001'),
((SELECT id FROM users WHERE user_id='21-0000-002'), 2, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 45 MINUTE), 2700, 'completed', 'TEST-TOKEN-002'),
((SELECT id FROM users WHERE user_id='22-0000-010'), 3, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 2 HOUR), 7200, 'completed', 'TEST-TOKEN-003'),
((SELECT id FROM users WHERE user_id='15-0000-001'), 4, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 3 DAY), INTERVAL 30 MINUTE), 1800, 'completed', 'TEST-TOKEN-004'),
((SELECT id FROM users WHERE user_id='21-0000-001'), 5, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 90 MINUTE), 5400, 'completed', 'TEST-TOKEN-005'),
((SELECT id FROM users WHERE user_id='23-0000-005'), 6, DATE_SUB(NOW(), INTERVAL 5 HOUR), DATE_ADD(DATE_SUB(NOW(), INTERVAL 5 HOUR), INTERVAL 1 HOUR), 3600, 'completed', 'TEST-TOKEN-006'),
((SELECT id FROM users WHERE user_id='23-0000-006'), 7, DATE_SUB(NOW(), INTERVAL 4 HOUR), DATE_ADD(DATE_SUB(NOW(), INTERVAL 4 HOUR), INTERVAL 2 HOUR), 7200, 'completed', 'TEST-TOKEN-007');

-- 4. Add sample website logs
-- Added INSERT IGNORE to prevent duplicate logs on re-run
INSERT IGNORE INTO website_logs (session_id, user_id, url, title, visited_at) VALUES
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-001'), (SELECT id FROM users WHERE user_id='21-0000-001'), 'https://www.google.com', 'Google Search', DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-001'), (SELECT id FROM users WHERE user_id='21-0000-001'), 'https://en.wikipedia.org/wiki/Library', 'Library - Wikipedia', DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 5 MINUTE)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-002'), (SELECT id FROM users WHERE user_id='21-0000-002'), 'https://github.com', 'GitHub', DATE_SUB(NOW(), INTERVAL 2 DAY)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-003'), (SELECT id FROM users WHERE user_id='22-0000-010'), 'https://scholar.google.com', 'Google Scholar', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-005'), (SELECT id FROM users WHERE user_id='21-0000-001'), 'https://www.jstor.org', 'JSTOR', DATE_SUB(NOW(), INTERVAL 1 DAY)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-006'), (SELECT id FROM users WHERE user_id='23-0000-005'), 'https://www.autodesk.com', 'Autodesk', DATE_SUB(NOW(), INTERVAL 5 HOUR)),
((SELECT id FROM sessions WHERE session_token='TEST-TOKEN-007'), (SELECT id FROM users WHERE user_id='23-0000-006'), 'https://ieeexplore.ieee.org', 'IEEE Xplore', DATE_SUB(NOW(), INTERVAL 4 HOUR));
