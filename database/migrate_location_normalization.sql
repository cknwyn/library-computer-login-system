-- ============================================================
-- Migration: Location Normalization (Campuses, Rooms)
-- ============================================================

-- 1. Create new location tables
CREATE TABLE IF NOT EXISTS campuses (
    id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    campus_id  INT UNSIGNED NOT NULL,
    name       VARCHAR(150) NOT NULL,
    UNIQUE KEY (campus_id, name),
    FOREIGN KEY (campus_id) REFERENCES campuses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 2. Update terminals table
ALTER TABLE terminals
ADD COLUMN terminal_name VARCHAR(100) DEFAULT NULL AFTER terminal_code,
ADD COLUMN pc_hostname   VARCHAR(150) DEFAULT NULL AFTER terminal_name,
ADD COLUMN campus_id     INT UNSIGNED DEFAULT NULL AFTER pc_hostname,
ADD COLUMN room_id       INT UNSIGNED DEFAULT NULL AFTER campus_id;

-- 3. Add foreign key constraints
ALTER TABLE terminals
ADD CONSTRAINT fk_terminal_campus FOREIGN KEY (campus_id) REFERENCES campuses(id) ON UPDATE CASCADE ON DELETE SET NULL,
ADD CONSTRAINT fk_terminal_room   FOREIGN KEY (room_id)   REFERENCES rooms(id)   ON UPDATE CASCADE ON DELETE SET NULL;
