-- Core
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  reg_no VARCHAR(60) NULL,
  phone VARCHAR(40) NULL,
  status ENUM('active','disabled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  slug VARCHAR(80) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY(user_id, role_id),
  CONSTRAINT fk_ur_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ur_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(160) NOT NULL,
  slug VARCHAR(120) NOT NULL UNIQUE
);

-- A clearance request by a student (UG/PG) for a specific completion year/award
CREATE TABLE IF NOT EXISTS clearances (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  level ENUM('undergraduate','postgraduate') NOT NULL,
  program VARCHAR(160) NULL,
  completion_year INT NOT NULL,
  reg_no VARCHAR(60) NULL,
  status ENUM('pending','in_progress','approved','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cl_user FOREIGN KEY (user_id) REFERENCES users(id)
);

-- The steps that must be signed off for a clearance workflow (departmental signatories)
CREATE TABLE IF NOT EXISTS clearance_steps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clearance_id INT NOT NULL,
  department_id INT NOT NULL,
  step_order INT NOT NULL,
  assignee_user_id INT NULL, -- optional designated officer
  comment TEXT NULL,
  signed_by INT NULL,
  signed_at DATETIME NULL,
  status ENUM('pending','cleared','flagged') DEFAULT 'pending',
  CONSTRAINT fk_cs_cl FOREIGN KEY (clearance_id) REFERENCES clearances(id) ON DELETE CASCADE,
  CONSTRAINT fk_cs_dept FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Audit log
CREATE TABLE IF NOT EXISTS activity_log (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(120) NOT NULL,
  payload JSON NULL,
  ip VARCHAR(45) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Sessions (optional if using PHP default sessions)
CREATE TABLE IF NOT EXISTS sessions (
  id VARCHAR(128) PRIMARY KEY,
  user_id INT NULL,
  data BLOB,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Password resets
CREATE TABLE IF NOT EXISTS password_resets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token VARCHAR(120) NOT NULL UNIQUE,
  expires_at DATETIME NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
