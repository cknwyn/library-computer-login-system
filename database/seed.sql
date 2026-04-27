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
-- Default password for all: Password@123
-- ============================================================
INSERT INTO users (user_id, name, password_hash, role, department, email) VALUES
('21-0000-001', 'Ana Reyes',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Computer Science',    'ana.reyes@student.edu'),
('21-0000-002', 'Ben Santos',     '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Information Technology','ben.santos@student.edu'),
('22-0000-010', 'Carla Mendoza',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Education',            'carla.mendoza@student.edu'),
('15-0000-001', 'Dr. Jose Cruz',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Faculty',              'jose.cruz@library.edu'),
('18-0000-002', 'Maria Lim',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Library Services',     'maria.lim@library.edu');


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
