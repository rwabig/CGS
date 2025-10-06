-- database/migrations/2025_09_30_create_signatory_assignments.sql
BEGIN;

CREATE TABLE IF NOT EXISTS signatory_assignments (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    department_id INT REFERENCES departments(id) ON DELETE SET NULL,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    directory_id INT REFERENCES directories(id) ON DELETE SET NULL,
    section_id INT REFERENCES sections(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, department_id, category_id, directory_id, section_id)
);

CREATE INDEX IF NOT EXISTS idx_signatory_assignments_user ON signatory_assignments(user_id);
CREATE INDEX IF NOT EXISTS idx_signatory_assignments_dept ON signatory_assignments(department_id);
CREATE INDEX IF NOT EXISTS idx_signatory_assignments_cat  ON signatory_assignments(category_id);

COMMIT;
