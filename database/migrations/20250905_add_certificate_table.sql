-- Migration: Add certificate table for printable clearance certificates
-- Date: 2025-09-05

CREATE TABLE IF NOT EXISTS certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clearance_id INT NOT NULL,
  issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  file_path VARCHAR(255) NULL,
  verification_code VARCHAR(64) NOT NULL UNIQUE,
  CONSTRAINT fk_cert_clearance FOREIGN KEY (clearance_id) REFERENCES clearances(id) ON DELETE CASCADE
) ENGINE=InnoDB;
