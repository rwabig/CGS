-- ==========================================================
-- CGS SCHEMA (PostgreSQL) - FINAL CLEAN INSTALL
-- ==========================================================

DROP TABLE IF EXISTS migrations_log, audit_log, clearance_signatories, clearance_certificates,
clearance_steps, clearance_requests, workflows, programs, categories, departments,
organizations, sections, directories, user_roles, roles, student_profiles, staff_profiles,
admin_profiles, super_admin_profiles, users CASCADE;

-- ==========================================================
-- USERS & ROLES
-- ==========================================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(160) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    status VARCHAR(20) CHECK (status IN ('active','disabled','hold')) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    slug VARCHAR(60) UNIQUE NOT NULL
);

CREATE TABLE user_roles (
    user_id INT REFERENCES users(id) ON DELETE CASCADE,
    role_id INT REFERENCES roles(id) ON DELETE CASCADE,
    PRIMARY KEY (user_id, role_id)
);

-- ==========================================================
-- ORGANIZATIONAL STRUCTURE
-- ==========================================================
CREATE TABLE organizations (
    id SERIAL PRIMARY KEY,
    code VARCHAR(60) UNIQUE NOT NULL,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE departments (
    id SERIAL PRIMARY KEY,
    organization_id INT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    department_id INT NOT NULL REFERENCES departments(id) ON DELETE CASCADE,
    name VARCHAR(60) CHECK (name IN ('Undergraduate','Postgraduate','Diploma','Certificate')) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE programs (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE directories (
    id SERIAL PRIMARY KEY,
    organization_id INT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sections (
    id SERIAL PRIMARY KEY,
    directory_id INT NOT NULL REFERENCES directories(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- PROFILES (Super Admin, Admin, Staff, Students)
-- ==========================================================
CREATE TABLE super_admin_profiles (
    id SERIAL PRIMARY KEY,
    user_id INT UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(120),
    phone VARCHAR(30),
    office_location VARCHAR(120),
    cheque_number VARCHAR(50),
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE admin_profiles (
    id SERIAL PRIMARY KEY,
    user_id INT UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(120),
    phone VARCHAR(30),
    position_title VARCHAR(100),
    organization_id INT REFERENCES organizations(id) ON DELETE SET NULL,
    department_id INT REFERENCES departments(id) ON DELETE SET NULL,
    cheque_number VARCHAR(50),
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE staff_profiles (
    id SERIAL PRIMARY KEY,
    user_id INT UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(120),
    phone VARCHAR(30),
    position_title VARCHAR(100),
    organization_id INT REFERENCES organizations(id) ON DELETE SET NULL,
    department_id INT REFERENCES departments(id) ON DELETE SET NULL,
    cheque_number VARCHAR(50),
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE student_profiles (
    id SERIAL PRIMARY KEY,
    user_id INT UNIQUE REFERENCES users(id) ON DELETE CASCADE,
    full_name VARCHAR(120),
    reg_number VARCHAR(50),
    phone VARCHAR(30),
    program_id INT REFERENCES programs(id) ON DELETE SET NULL,
    department_id INT REFERENCES departments(id) ON DELETE SET NULL,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    graduation_year INT,
    residential_status VARCHAR(30),
    hall_name VARCHAR(100),
    current_address TEXT,
    photo VARCHAR(255) DEFAULT 'default-student.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- CLEARANCE PROCESS
-- ==========================================================
CREATE TABLE clearance_requests (
    id SERIAL PRIMARY KEY,
    student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(30) CHECK (status IN ('not_started','in_progress','partially_signed','fully_approved','certificate_issued')) DEFAULT 'not_started'
);

CREATE TABLE clearance_steps (
    id SERIAL PRIMARY KEY,
    clearance_request_id INT NOT NULL REFERENCES clearance_requests(id) ON DELETE CASCADE,
    step_order INT NOT NULL,
    status VARCHAR(20) CHECK (status IN ('pending','approved','rejected')) DEFAULT 'pending',
    officer_id INT REFERENCES users(id) ON DELETE SET NULL,
    comments TEXT,
    signed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clearance_certificates (
    id SERIAL PRIMARY KEY,
    student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    university_name VARCHAR(120) DEFAULT 'Ardhi University',
    university_logo VARCHAR(255) DEFAULT 'aru-logo.png',
    title VARCHAR(255) DEFAULT 'Clearance Certificate Form for Graduating Student',
    description TEXT DEFAULT 'Ensure safe return of all university/property equipment, books etc. entrusted during the period of studies. Clearance must be done before collection of the result slip, academic certificate and transcripts.',
    student_photo VARCHAR(255) DEFAULT 'default-student.png',
    completion_year INT,
    graduation_date DATE,
    issued_at TIMESTAMP NULL,
    qr_code TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE clearance_signatories (
    id SERIAL PRIMARY KEY,
    certificate_id INT NOT NULL REFERENCES clearance_certificates(id) ON DELETE CASCADE,
    step_order INT NOT NULL,
    signatory_name VARCHAR(120),
    signatory_title VARCHAR(120),
    status VARCHAR(20) CHECK (status IN ('pending','approved','rejected')) DEFAULT 'pending',
    comments TEXT,
    signed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- WORKFLOWS
-- ==========================================================
CREATE TABLE workflows (
    id SERIAL PRIMARY KEY,
    organization_id INT NOT NULL REFERENCES organizations(id) ON DELETE CASCADE,
    department_id INT REFERENCES departments(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    directory_id INT REFERENCES directories(id) ON DELETE SET NULL,
    section_id INT REFERENCES sections(id) ON DELETE SET NULL,
    step_order INT NOT NULL,
    signatory_title VARCHAR(120) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==========================================================
-- AUDIT & MIGRATIONS LOGS
-- ==========================================================
CREATE TABLE audit_log (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_audit_log_action ON audit_log(action);
CREATE INDEX idx_audit_log_created_at ON audit_log(created_at);

CREATE TABLE migrations_log (
    id SERIAL PRIMARY KEY,
    batch INT NOT NULL,
    migration_name VARCHAR(255) NOT NULL,
    action VARCHAR(20) CHECK (action IN ('run','rollback')) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    details TEXT
);
