-- ================================
-- SEED DATA (PostgreSQL)
-- ================================

-- Roles
INSERT INTO roles (slug, name) VALUES
('super_admin', 'Super Admin'),
('admin', 'Administrator'),
('signatory', 'Signatory'),
('student', 'Student')
ON CONFLICT DO NOTHING;

-- Default Super Admin
INSERT INTO users (email, password_hash, is_active, status)
VALUES ('rwabig@gmail.com', crypt('Admin@123', gen_salt('bf')), TRUE, 'active')
ON CONFLICT (email) DO NOTHING;

-- Link Super Admin to roles
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.email='rwabig@gmail.com' AND r.slug IN ('super_admin','admin')
ON CONFLICT DO NOTHING;

-- Insert default organizations
INSERT INTO organizations (code, name, description)
VALUES
('SSPSS', 'School of Spatial Planning and Social Sciences (SSPSS)',
 'The school has two departments: Urban and Regional Planning (URP) and Economics and Social Studies (ESS).'),
('SEST', 'School of Environmental Science and Technology (SEST)',
 'The school has departments of Environmental Engineering (EE) and Environmental Science and Management (ESM).')
ON CONFLICT DO NOTHING;

-- Insert sample department, category, program
INSERT INTO departments (organization_id, name)
SELECT id, 'Department of Urban Design'
FROM organizations WHERE code='SSPSS'
ON CONFLICT DO NOTHING;

INSERT INTO categories (department_id, name)
SELECT d.id, 'Undergraduate'
FROM departments d
ON CONFLICT DO NOTHING;

INSERT INTO programs (category_id, name)
SELECT c.id, 'Bachelor of Urban and Regional Planning'
FROM categories c
ON CONFLICT DO NOTHING;

-- Insert sample directory & section
INSERT INTO directories (organization_id, name)
SELECT id, 'Library'
FROM organizations
ON CONFLICT DO NOTHING;

INSERT INTO sections (directory_id, name)
SELECT d.id, 'Main Library'
FROM directories d
ON CONFLICT DO NOTHING;

-- Seed an audit log entry
INSERT INTO audit_log (action, details)
VALUES ('installation', 'System Bootstrap Completed: schema.sql + seed.sql executed, default super admin created.');
