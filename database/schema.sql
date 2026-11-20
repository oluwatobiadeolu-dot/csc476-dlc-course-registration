-- DLC course registration prototype (CSC 476)
-- select your (empty) database in phpMyAdmin, then import


DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS discrepancy_reports;
DROP TABLE IF EXISTS registrations;
DROP TABLE IF EXISTS deadlines;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS academic_sessions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS departments;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  matric_no VARCHAR(30) NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('student','admin') NOT NULL DEFAULT 'student',
  department_id INT UNSIGNED NULL,
  level SMALLINT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE academic_sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_name VARCHAR(9) NOT NULL,
  semester ENUM('First','Second') NOT NULL,
  is_current TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_session_semester (session_name, semester)
) ENGINE=InnoDB;

CREATE TABLE courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(12) NOT NULL UNIQUE,
  title VARCHAR(200) NOT NULL,
  credit_units TINYINT UNSIGNED NOT NULL,
  course_type ENUM('compulsory','elective') NOT NULL DEFAULT 'compulsory',
  level SMALLINT UNSIGNED NOT NULL,
  semester ENUM('First','Second') NOT NULL,
  department_id INT UNSIGNED NULL,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  published_at DATETIME NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  session_id INT UNSIGNED NOT NULL,
  status ENUM('active','dropped') NOT NULL DEFAULT 'active',
  registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dropped_at DATETIME NULL,
  -- one row per student per course per session: blocks duplicate registration (TC08)
  UNIQUE KEY uq_student_course_session (student_id, course_id, session_id),
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE deadlines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id INT UNSIGNED NOT NULL UNIQUE,
  registration_deadline DATETIME NOT NULL,
  drop_deadline DATETIME NOT NULL,
  FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE discrepancy_reports (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id INT UNSIGNED NOT NULL,
  course_id INT UNSIGNED NOT NULL,
  message TEXT NOT NULL,
  status ENUM('open','under_review','resolved') NOT NULL DEFAULT 'open',
  admin_response TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- unit limits (placeholders, 0 = no limit)
CREATE TABLE settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

-- starting data
INSERT INTO settings (setting_key, setting_value) VALUES ('min_units','15'), ('max_units','24');

INSERT INTO departments (name) VALUES
  ('Computer Science'), ('Economics'), ('Mathematics'), ('Statistics'), ('Library and Information Studies');

INSERT INTO academic_sessions (session_name, semester, is_current) VALUES
  ('2026/2027', 'First', 1),
  ('2026/2027', 'Second', 0);

-- admin login: admin@dlc.test / Admin@12345
INSERT INTO users (full_name, email, password_hash, role)
VALUES ('DLC Administrator', 'admin@dlc.test', '$2y$10$r/MBq8bwF9TImcajQZMbgeKPCv6XIGe3QGuBWY7iLeoIIAwAyzslO', 'admin');
