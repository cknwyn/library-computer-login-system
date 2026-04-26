-- ============================================================
-- FIX / MIGRATION SCRIPT FOR USERS TABLE
-- Run this if your 'users' table is missing the new fields
-- ============================================================

USE library_system;

ALTER TABLE users
ADD COLUMN username         VARCHAR(100) DEFAULT NULL AFTER name,
ADD COLUMN contact_number   VARCHAR(50)  DEFAULT NULL AFTER role,
ADD COLUMN designation      VARCHAR(100) DEFAULT NULL AFTER contact_number,
ADD COLUMN affiliation      VARCHAR(150) DEFAULT NULL AFTER designation,
ADD COLUMN gender           VARCHAR(20)  DEFAULT NULL AFTER affiliation,
ADD COLUMN year             VARCHAR(10)  DEFAULT NULL AFTER gender,
ADD COLUMN user_type        VARCHAR(50)  DEFAULT NULL AFTER department,
ADD COLUMN degree           VARCHAR(150) DEFAULT NULL AFTER user_type,
ADD COLUMN speciality       VARCHAR(150) DEFAULT NULL AFTER degree,
ADD COLUMN ra_expiry_date   DATE         DEFAULT NULL AFTER speciality,
ADD COLUMN rank             VARCHAR(50)  DEFAULT NULL AFTER ra_expiry_date,
ADD COLUMN batch            VARCHAR(50)  DEFAULT NULL AFTER rank,
ADD COLUMN cadre            VARCHAR(100) DEFAULT NULL AFTER batch,
ADD COLUMN dob              DATE         DEFAULT NULL AFTER cadre;

-- Ensure constraints and types are compatible with new logic
ALTER TABLE users MODIFY user_id VARCHAR(50) NOT NULL;
ALTER TABLE users MODIFY name VARCHAR(100) NOT NULL;
ALTER TABLE users MODIFY password_hash VARCHAR(255) NOT NULL;
