-- database/migrations/2025_09_23_alter_student_profiles_fixed.sql

BEGIN;

-- === 1. Add new columns safely (if not already present) ===
ALTER TABLE student_profiles
    ADD COLUMN IF NOT EXISTS organization_id INT,
    ADD COLUMN IF NOT EXISTS department_id   INT,
    ADD COLUMN IF NOT EXISTS category_id     INT,
    ADD COLUMN IF NOT EXISTS program_id      INT,
    ADD COLUMN IF NOT EXISTS registration_number VARCHAR(100),
    ADD COLUMN IF NOT EXISTS school_institute     VARCHAR(150),
    ADD COLUMN IF NOT EXISTS residential_status   VARCHAR(50) CHECK (residential_status IN ('Residential','Non-Residential')),
    ADD COLUMN IF NOT EXISTS hall_name            VARCHAR(150),
    ADD COLUMN IF NOT EXISTS current_address      TEXT,
    ADD COLUMN IF NOT EXISTS completion_year      INT CHECK (completion_year >= 1900 AND completion_year <= EXTRACT(YEAR FROM CURRENT_DATE) + 10),
    ADD COLUMN IF NOT EXISTS graduation_date      DATE,
    ADD COLUMN IF NOT EXISTS photo_path           VARCHAR(255);

-- Keep created_at and updated_at if they already exist
ALTER TABLE student_profiles
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT NOW(),
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT NOW();

-- === 2. Add foreign key constraints (after columns exist) ===
DO $$
BEGIN
    -- Organization
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_student_profiles_org'
    ) THEN
        ALTER TABLE student_profiles
        ADD CONSTRAINT fk_student_profiles_org
        FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE RESTRICT;
    END IF;

    -- Department
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_student_profiles_dept'
    ) THEN
        ALTER TABLE student_profiles
        ADD CONSTRAINT fk_student_profiles_dept
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT;
    END IF;

    -- Category
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_student_profiles_cat'
    ) THEN
        ALTER TABLE student_profiles
        ADD CONSTRAINT fk_student_profiles_cat
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT;
    END IF;

    -- Program
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_student_profiles_prog'
    ) THEN
        ALTER TABLE student_profiles
        ADD CONSTRAINT fk_student_profiles_prog
        FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE RESTRICT;
    END IF;
END$$;

-- === 3. Add useful indexes ===
CREATE INDEX IF NOT EXISTS idx_student_profiles_org     ON student_profiles(organization_id);
CREATE INDEX IF NOT EXISTS idx_student_profiles_dept    ON student_profiles(department_id);
CREATE INDEX IF NOT EXISTS idx_student_profiles_cat     ON student_profiles(category_id);
CREATE INDEX IF NOT EXISTS idx_student_profiles_prog    ON student_profiles(program_id);

COMMIT;
