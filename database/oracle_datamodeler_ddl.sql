-- Optimized for Oracle SQL Data Modeler (MySQL to Standard SQL conversion)
-- This file removes MySQL-specific syntax (UNSIGNED, ENGINE, ENUM) for better compatibility.

-- Colleges
CREATE TABLE colleges (
    id   INT NOT NULL PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE,
    code VARCHAR(50)  DEFAULT NULL UNIQUE
);

-- Departments
CREATE TABLE departments (
    id         INT NOT NULL PRIMARY KEY,
    college_id INT NOT NULL,
    name       VARCHAR(150) NOT NULL,
    CONSTRAINT uk_dept UNIQUE (college_id, name),
    FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

-- Degrees
CREATE TABLE degrees (
    id            INT NOT NULL PRIMARY KEY,
    department_id INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    CONSTRAINT uk_degree UNIQUE (department_id, name),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

-- Specializations
CREATE TABLE specializations (
    id            INT NOT NULL PRIMARY KEY,
    degree_id     INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    CONSTRAINT uk_spec UNIQUE (degree_id, name),
    FOREIGN KEY (degree_id) REFERENCES degrees(id) ON DELETE CASCADE
);

-- Campuses
CREATE TABLE campuses (
    id               INT NOT NULL PRIMARY KEY,
    name             VARCHAR(150) NOT NULL UNIQUE,
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Rooms
CREATE TABLE rooms (
    id               INT NOT NULL PRIMARY KEY,
    campus_id        INT NOT NULL,
    name             VARCHAR(150) NOT NULL,
    description      TEXT,
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT uk_room UNIQUE (campus_id, name),
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE
);

-- Users
CREATE TABLE users (
    id                INT NOT NULL PRIMARY KEY,
    user_id           VARCHAR(50) NOT NULL UNIQUE,
    first_name        VARCHAR(100),
    middle_name       VARCHAR(100),
    last_name         VARCHAR(100),
    name              VARCHAR(255) NOT NULL,
    username          VARCHAR(100),
    email             VARCHAR(150),
    password_hash     VARCHAR(255) NOT NULL,
    role              VARCHAR(20) DEFAULT 'student' NOT NULL, -- Replaced ENUM
    status            VARCHAR(20) DEFAULT 'active' NOT NULL,  -- Replaced ENUM
    contact_number    VARCHAR(50),
    designation       VARCHAR(100),
    college_id        INT,
    gender            VARCHAR(20),                            -- Replaced ENUM
    year              VARCHAR(10),
    department_id     INT,
    degree_id         INT,
    specialization_id INT,
    ra_expiry_date    DATE,
    rank              VARCHAR(50),
    batch             VARCHAR(50),
    cadre             VARCHAR(100),
    dob               DATE,
    creation_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (college_id)    REFERENCES colleges(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (degree_id)     REFERENCES degrees(id),
    FOREIGN KEY (specialization_id) REFERENCES specializations(id)
);

-- Admins
CREATE TABLE admins (
    id            INT NOT NULL PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150),
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Terminals
CREATE TABLE terminals (
    id            INT NOT NULL PRIMARY KEY,
    terminal_code VARCHAR(30)  NOT NULL UNIQUE,
    terminal_name VARCHAR(100),
    pc_hostname   VARCHAR(150),
    campus_id     INT,
    room_id       INT,
    status        VARCHAR(20) DEFAULT 'offline' NOT NULL, -- Replaced ENUM
    last_seen     TIMESTAMP,
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (campus_id) REFERENCES campuses(id),
    FOREIGN KEY (room_id)   REFERENCES rooms(id)
);

-- Websites
CREATE TABLE websites (
    id            INT NOT NULL PRIMARY KEY,
    url           TEXT NOT NULL,
    title         VARCHAR(255),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sessions
CREATE TABLE sessions (
    id               INT NOT NULL PRIMARY KEY,
    user_id          INT NOT NULL,
    terminal_id      INT NOT NULL,
    session_token    VARCHAR(64)  NOT NULL UNIQUE,
    login_time       TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    logout_time      TIMESTAMP,
    duration_seconds INT,
    last_heartbeat   TIMESTAMP,
    status           VARCHAR(20) DEFAULT 'active' NOT NULL, -- Replaced ENUM
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (terminal_id) REFERENCES terminals(id)
);

-- Website Logs
CREATE TABLE website_logs (
    id           INT NOT NULL PRIMARY KEY,
    session_id   INT NOT NULL,
    user_id      INT NOT NULL,
    website_id   INT NOT NULL,
    visited_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)    REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
);

-- Activity Logs
CREATE TABLE activity_logs (
    id          INT NOT NULL PRIMARY KEY,
    user_id     INT,
    admin_id    INT,
    terminal_id INT,
    action      VARCHAR(100) NOT NULL,
    details     TEXT,
    ip_address  VARCHAR(45),
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL
);

-- Password Resets
CREATE TABLE password_resets (
    id          INT NOT NULL PRIMARY KEY,
    user_id     INT NOT NULL,
    token       VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
