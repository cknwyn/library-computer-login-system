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
('2021-00001', 'Ana Reyes',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Computer Science',    'ana.reyes@student.edu'),
('2021-00002', 'Ben Santos',     '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Information Technology','ben.santos@student.edu'),
('2022-00010', 'Carla Mendoza',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'student', 'Education',            'carla.mendoza@student.edu'),
('STAFF-001',  'Dr. Jose Cruz',  '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Faculty',              'jose.cruz@library.edu'),
('STAFF-002',  'Maria Lim',      '$2y$12$2mVB4/4etVp6XPm7ZqfuyOv0ijPhJiFJKdRMWkrBvXSOHbKZB6suq', 'staff',   'Library Services',     'maria.lim@library.edu');

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
-- INSTALLED APPS (Master whitelist)
-- ============================================================
INSERT INTO installed_apps (name, description, version, category) VALUES
('Microsoft Word',       'Word processing application',                      '2021', 'Productivity'),
('Microsoft Excel',      'Spreadsheet application',                          '2021', 'Productivity'),
('Microsoft PowerPoint', 'Presentation application',                         '2021', 'Productivity'),
('Google Chrome',        'Web browser',                                       '120',  'Internet'),
('Mozilla Firefox',      'Open-source web browser',                          '121',  'Internet'),
('Adobe Acrobat Reader', 'PDF viewer and annotator',                         'DC',   'Productivity'),
('LibreOffice',          'Free and open-source office suite',                '7.6',  'Productivity'),
('VLC Media Player',     'Multimedia player',                                '3.0',  'Media'),
('Notepad++',            'Source code and text editor',                      '8.6',  'Development'),
('7-Zip',                'File archiver with high compression ratio',        '23.0', 'Utilities'),
('Mendeley',             'Reference manager and academic social network',    '2.x',  'Research'),
('Zotero',               'Free, easy-to-use reference management software',  '6.0',  'Research'),
('MATLAB',               'Numerical computing environment',                  'R2023','Research'),
('Python 3',             'Programming language and runtime',                 '3.12', 'Development'),
('Visual Studio Code',   'Source code editor',                               '1.85', 'Development');
