-- ============================================================
-- CLEANED DDL FOR ORACLE SQL DATA MODELER
-- Optimized for: Clear Relationship Labels & Clean Attributes
-- ============================================================

-- 1. ACADEMIC HIERARCHY
CREATE TABLE colleges (
    id   INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(50),
    CONSTRAINT pk_colleges PRIMARY KEY (id),
    CONSTRAINT uk_college_name UNIQUE (name)
);

CREATE TABLE departments (
    id         INT NOT NULL,
    college_id INT NOT NULL,
    name       VARCHAR(150) NOT NULL,
    CONSTRAINT pk_departments PRIMARY KEY (id),
    CONSTRAINT uk_dept_name UNIQUE (college_id, name),
    CONSTRAINT fk_dept_college FOREIGN KEY (college_id) REFERENCES colleges(id) ON DELETE CASCADE
);

CREATE TABLE degrees (
    id            INT NOT NULL,
    department_id INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    CONSTRAINT pk_degrees PRIMARY KEY (id),
    CONSTRAINT uk_degree_name UNIQUE (department_id, name),
    CONSTRAINT fk_degree_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
);

CREATE TABLE specializations (
    id            INT NOT NULL,
    degree_id     INT NOT NULL,
    name          VARCHAR(150) NOT NULL,
    CONSTRAINT pk_specializations PRIMARY KEY (id),
    CONSTRAINT uk_spec_name UNIQUE (degree_id, name),
    CONSTRAINT fk_spec_degree FOREIGN KEY (degree_id) REFERENCES degrees(id) ON DELETE CASCADE
);

-- 2. INFRASTRUCTURE
CREATE TABLE campuses (
    id               INT NOT NULL,
    name             VARCHAR(150) NOT NULL,
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_campuses PRIMARY KEY (id),
    CONSTRAINT uk_campus_name UNIQUE (name)
);

CREATE TABLE rooms (
    id               INT NOT NULL,
    campus_id        INT NOT NULL,
    name             VARCHAR(150) NOT NULL,
    description      TEXT,
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_rooms PRIMARY KEY (id),
    CONSTRAINT uk_room_name UNIQUE (campus_id, name),
    CONSTRAINT fk_room_campus FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE
);

-- 3. USERS & ADMINS
CREATE TABLE users (
    id                INT NOT NULL,
    user_id           VARCHAR(50) NOT NULL,
    first_name        VARCHAR(100),
    middle_name       VARCHAR(100),
    last_name         VARCHAR(100),
    name              VARCHAR(255) NOT NULL,
    username          VARCHAR(100),
    email             VARCHAR(150),
    password_hash     VARCHAR(255) NOT NULL,
    role              VARCHAR(20) DEFAULT 'student' NOT NULL,
    status            VARCHAR(20) DEFAULT 'active' NOT NULL,
    contact_number    VARCHAR(50),
    designation       VARCHAR(100),
    college_id        INT,
    department_id     INT,
    degree_id         INT,
    specialization_id INT,
    gender            VARCHAR(20),
    year              VARCHAR(10),
    ra_expiry_date    DATE,
    rank              VARCHAR(50),
    batch             VARCHAR(50),
    cadre             VARCHAR(100),
    dob               DATE,
    creation_date     TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_users PRIMARY KEY (id),
    CONSTRAINT uk_user_id UNIQUE (user_id),
    CONSTRAINT fk_user_college FOREIGN KEY (college_id) REFERENCES colleges(id),
    CONSTRAINT fk_user_dept FOREIGN KEY (department_id) REFERENCES departments(id),
    CONSTRAINT fk_user_degree FOREIGN KEY (degree_id) REFERENCES degrees(id),
    CONSTRAINT fk_user_spec FOREIGN KEY (specialization_id) REFERENCES specializations(id)
);

CREATE TABLE admins (
    id            INT NOT NULL,
    username      VARCHAR(50)  NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150),
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_admins PRIMARY KEY (id),
    CONSTRAINT uk_admin_user UNIQUE (username)
);

-- 4. ASSETS & SESSIONS
CREATE TABLE terminals (
    id            INT NOT NULL,
    terminal_code VARCHAR(30)  NOT NULL,
    terminal_name VARCHAR(100),
    pc_hostname   VARCHAR(150),
    campus_id     INT,
    room_id       INT,
    status        VARCHAR(20) DEFAULT 'offline' NOT NULL,
    last_seen     TIMESTAMP,
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_terminals PRIMARY KEY (id),
    CONSTRAINT uk_term_code UNIQUE (terminal_code),
    CONSTRAINT fk_term_campus FOREIGN KEY (campus_id) REFERENCES campuses(id),
    CONSTRAINT fk_term_room FOREIGN KEY (room_id) REFERENCES rooms(id)
);

CREATE TABLE websites (
    id            INT NOT NULL,
    url           TEXT NOT NULL,
    title         VARCHAR(255),
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_websites PRIMARY KEY (id)
);

CREATE TABLE sessions (
    id               INT NOT NULL,
    user_id          INT NOT NULL,
    terminal_id      INT NOT NULL,
    session_token    VARCHAR(64)  NOT NULL,
    login_time       TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    logout_time      TIMESTAMP,
    duration_seconds INT,
    last_heartbeat   TIMESTAMP,
    status           VARCHAR(20) DEFAULT 'active' NOT NULL,
    creation_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_sessions PRIMARY KEY (id),
    CONSTRAINT uk_sess_token UNIQUE (session_token),
    CONSTRAINT fk_sess_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sess_term FOREIGN KEY (terminal_id) REFERENCES terminals(id)
);

-- 5. LOGS
CREATE TABLE website_logs (
    id           INT NOT NULL,
    session_id   INT NOT NULL,
    user_id      INT NOT NULL,
    website_id   INT NOT NULL,
    visited_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_website_logs PRIMARY KEY (id),
    CONSTRAINT fk_wlog_sess FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    CONSTRAINT fk_wlog_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_wlog_web  FOREIGN KEY (website_id) REFERENCES websites(id) ON DELETE CASCADE
);

CREATE TABLE activity_logs (
    id          INT NOT NULL,
    user_id     INT,
    admin_id    INT,
    terminal_id INT,
    action      VARCHAR(100) NOT NULL,
    details     TEXT,
    ip_address  VARCHAR(45),
    creation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_activity_logs PRIMARY KEY (id),
    CONSTRAINT fk_alog_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_alog_admin FOREIGN KEY (admin_id) REFERENCES admins(id),
    CONSTRAINT fk_alog_term FOREIGN KEY (terminal_id) REFERENCES terminals(id)
);

CREATE TABLE password_resets (
    id          INT NOT NULL,
    user_id     INT NOT NULL,
    token       VARCHAR(255) NOT NULL,
    expires_at  TIMESTAMP NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
    CONSTRAINT pk_pw_resets PRIMARY KEY (id),
    CONSTRAINT fk_pwres_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. INDEXES (SEPARATE TO AVOID "UNKNOWN" COLUMNS)
CREATE INDEX idx_user_name ON users(name);
CREATE INDEX idx_user_email ON users(email);
CREATE INDEX idx_user_status ON users(status);
CREATE INDEX idx_sess_login ON sessions(login_time);
CREATE INDEX idx_wlog_time ON website_logs(visited_at);
CREATE INDEX idx_alog_action ON activity_logs(action);
CREATE INDEX idx_alog_date ON activity_logs(creation_date);
