-- ============================================================
-- Library Computer Login System - Database Schema
-- XAMPP / MySQL 5.7+
-- ============================================================

CREATE DATABASE IF NOT EXISTS library_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE library_system;

-- ============================================================
-- USERS (Library patrons: students and staff)
-- ============================================================
CREATE TABLE users (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Staff ID or Student ID',
    name             VARCHAR(100) NOT NULL,
    username         VARCHAR(100) DEFAULT NULL,
    email            VARCHAR(150) DEFAULT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    role             ENUM('student', 'staff') NOT NULL DEFAULT 'student',
    contact_number   VARCHAR(50)  DEFAULT NULL,
    designation      VARCHAR(100) DEFAULT NULL,
    affiliation      VARCHAR(150) DEFAULT NULL,
    gender           VARCHAR(20)  DEFAULT NULL,
    year             VARCHAR(10)  DEFAULT NULL,
    department       VARCHAR(100) DEFAULT NULL,
    user_type        VARCHAR(50)  DEFAULT NULL COMMENT 'Raw string from import (e.g. STUDENT, FACULTY)',
    degree           VARCHAR(150) DEFAULT NULL,
    speciality       VARCHAR(150) DEFAULT NULL,
    ra_expiry_date   DATE         DEFAULT NULL,
    rank             VARCHAR(50)  DEFAULT NULL,
    batch            VARCHAR(50)  DEFAULT NULL,
    cadre            VARCHAR(100) DEFAULT NULL,
    dob              DATE         DEFAULT NULL,
    status           ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- ADMINS (Librarians / IT staff who access the admin panel)
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
-- TERMINALS (nComputing thin-client stations)
-- ============================================================
CREATE TABLE terminals (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    terminal_code VARCHAR(30)  NOT NULL UNIQUE COMMENT 'e.g. PC-01, LIB-A-03',
    location      VARCHAR(100) DEFAULT NULL  COMMENT 'e.g. Reading Room A',
    status        ENUM('online', 'offline', 'maintenance') NOT NULL DEFAULT 'offline',
    last_seen     TIMESTAMP NULL DEFAULT NULL,
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- SESSIONS (Each user login event)
-- ============================================================
CREATE TABLE sessions (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          INT UNSIGNED NOT NULL,
    terminal_id      INT UNSIGNED NOT NULL,
    session_token    VARCHAR(64)  NOT NULL UNIQUE COMMENT 'Secure token sent to Electron client',
    login_time       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    logout_time      TIMESTAMP NULL DEFAULT NULL,
    duration_seconds INT UNSIGNED NULL DEFAULT NULL COMMENT 'Computed on logout',
    last_heartbeat   TIMESTAMP NULL DEFAULT NULL,
    status           ENUM('active', 'completed', 'force_ended', 'abandoned') NOT NULL DEFAULT 'active',
    creation_date    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id)     ON UPDATE CASCADE,
    FOREIGN KEY (terminal_id) REFERENCES terminals(id) ON UPDATE CASCADE,
    INDEX idx_status (status),
    INDEX idx_user_id (user_id),
    INDEX idx_terminal_id (terminal_id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB;

-- ============================================================
-- WEBSITE LOGS (Tracking URLs visited during sessions)
-- ============================================================
CREATE TABLE website_logs (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id   INT UNSIGNED NOT NULL,
    user_id      INT UNSIGNED NOT NULL,
    url          TEXT         NOT NULL,
    title        VARCHAR(255) DEFAULT NULL,
    visited_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON UPDATE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_visited_at (visited_at)
) ENGINE=InnoDB;

-- ============================================================
-- ACTIVITY LOGS (Audit trail for all events)
-- ============================================================
CREATE TABLE activity_logs (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL DEFAULT NULL,
    admin_id    INT UNSIGNED NULL DEFAULT NULL,
    terminal_id INT UNSIGNED NULL DEFAULT NULL,
    action      VARCHAR(100) NOT NULL COMMENT 'e.g. USER_LOGIN, USER_LOGOUT, APP_REQUEST',
    details     TEXT         DEFAULT NULL,
    ip_address  VARCHAR(45)  DEFAULT NULL,
    creation_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_creation_date (creation_date)
) ENGINE=InnoDB;
