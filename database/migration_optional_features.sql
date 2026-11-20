-- run once on a database made from the earlier schema.sql
-- new installs do not need this file

ALTER TABLE courses
  ADD COLUMN course_type ENUM('compulsory','elective') NOT NULL DEFAULT 'compulsory' AFTER credit_units;

CREATE TABLE IF NOT EXISTS settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- placeholder limits, change them in Admin > Unit limits
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES ('min_units','15'), ('max_units','24');

-- marks the sample electives (does nothing if sample data was not imported)
UPDATE courses SET course_type = 'elective' WHERE course_code IN ('CSC 213','CSC 413','CSC 415');
