CREATE TABLE IF NOT EXISTS workflows (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level ENUM('undergraduate','postgraduate') NOT NULL,
  department_id INT NOT NULL,
  step_order INT NOT NULL,
  CONSTRAINT fk_workflow_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;
