-- ============================================================
-- Library Computer Login System - Database Schema (Enterprise 3NF)
-- Optimized for XAMPP / MySQL 5.7+ / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE library_system;

-- ============================================================
-- ACADEMIC CLASSIFICATIONS
-- ============================================================
CREATE TABLE colleges (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(50)  DEFAULT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE departments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    college_id INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    UNIQUE KEY (college_id, name),
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE degrees (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    UNIQUE KEY (department_id, name),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE specializations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    degree_id     INT UNSIGNED NOT NULL,
    name          VARCHAR(150) NOT NULL,
    UNIQUE KEY (degree_id, name),
    FOREIGN KEY (degree_id) REFERENCES degrees(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PHYSICAL INFRASTRUCTURE
-- ============================================================
CREATE TABLE campuses (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(150) NOT NULL UNIQUE,
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE rooms (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campus_id        INT UNSIGNED NOT NULL,
    name             VARCHAR(150) NOT NULL,
    description      TEXT         DEFAULT NULL,
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (campus_id, name),
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- USERS (Patrons)
-- ============================================================
CREATE TABLE users (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Staff ID or Student ID',
    first_name       VARCHAR(100) DEFAULT NULL,
    middle_name      VARCHAR(100) DEFAULT NULL,
    last_name        VARCHAR(100) DEFAULT NULL,
    name             VARCHAR(255) NOT NULL,
    username         VARCHAR(100) DEFAULT NULL,
    email            VARCHAR(150) DEFAULT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    role             ENUM('student', 'staff', 'admin') NOT NULL DEFAULT 'student',
    status           ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    contact_number   VARCHAR(50)  DEFAULT NULL,
    designation      VARCHAR(100) DEFAULT NULL,
    college_id       INT UNSIGNED DEFAULT NULL,
    gender           ENUM('Male', 'Female', 'Other') DEFAULT NULL,
    year             VARCHAR(10)  DEFAULT NULL,
    department_id    INT UNSIGNED DEFAULT NULL,
    degree_id        INT UNSIGNED DEFAULT NULL,
    specialization_id INT UNSIGNED DEFAULT NULL,
    ra_expiry_date   DATE         DEFAULT NULL,
    rank             VARCHAR(50)  DEFAULT NULL,
    batch            VARCHAR(50)  DEFAULT NULL,
    cadre            VARCHAR(100) DEFAULT NULL,
    dob              DATE         DEFAULT NULL,
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (college_id)    REFERENCES colleges(id)    ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (degree_id)     REFERENCES degrees(id)     ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (specialization_id) REFERENCES specializations(id) ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_name (name),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- ADMINISTRATION
-- ============================================================
CREATE TABLE admins (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) DEFAULT NULL,
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- NETWORK ASSETS
-- ============================================================
CREATE TABLE terminals (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    terminal_code VARCHAR(30)  NOT NULL UNIQUE COMMENT 'e.g. PC-01',
    terminal_name VARCHAR(100) DEFAULT NULL,
    pc_hostname   VARCHAR(150) DEFAULT NULL,
    campus_id     INT UNSIGNED DEFAULT NULL,
    room_id       INT UNSIGNED DEFAULT NULL,
    status        ENUM('online', 'offline', 'maintenance') NOT NULL DEFAULT 'offline',
    last_seen     TIMESTAMP NULL DEFAULT NULL,
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON UPDATE CASCADE ON DELETE SET NULL,
    FOREIGN KEY (room_id)   REFERENCES rooms(id)   ON UPDATE CASCADE ON DELETE SET NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY CATALOGS
-- ============================================================
CREATE TABLE websites (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    url           TEXT NOT NULL,
    title         VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_url_hash (url(255))
) ENGINE=InnoDB;

-- ============================================================
-- SESSIONS & LOGS
-- ============================================================
CREATE TABLE sessions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    terminal_id      INT UNSIGNED NOT NULL,
    session_token    VARCHAR(64)  NOT NULL UNIQUE,
    login_time       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_time      TIMESTAMP NULL DEFAULT NULL,
    duration_seconds INT UNSIGNED NULL DEFAULT NULL,
    last_heartbeat   TIMESTAMP NULL DEFAULT NULL,
    status           ENUM('active', 'completed', 'force_ended', 'abandoned') NOT NULL DEFAULT 'active',
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (terminal_id) REFERENCES terminals(id) ON UPDATE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_terminal_id (terminal_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB;

CREATE TABLE website_logs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id   INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    website_id   INT UNSIGNED NOT NULL,
    visited_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES websites(id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_visited_at (visited_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL DEFAULT NULL,
    admin_id    INT UNSIGNED NULL DEFAULT NULL,
    terminal_id INT UNSIGNED NULL DEFAULT NULL,
    action      VARCHAR(100) NOT NULL,
    details     TEXT         DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_creation_date (creation_date)
) ENGINE=InnoDB;

-- ============================================================
-- AUTHENTICATION & RECOVERY
-- ============================================================
CREATE TABLE password_resets (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    token       VARCHAR(255) NOT NULL COMMENT 'Hashed 6-digit code',
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB;
