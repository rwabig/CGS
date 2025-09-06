-- Migration: Add signatory assignment to departments
-- Date: 2025-09-06

ALTER TABLE departments
  ADD COLUMN signatory_user_id INT NULL AFTER slug,
  ADD CONSTRAINT fk_departments_signatory
    FOREIGN KEY (signatory_user_id) REFERENCES users(id)
    ON DELETE SET NULL;
