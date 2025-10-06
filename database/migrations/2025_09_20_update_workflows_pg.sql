-- ==========================================================
-- Migration: Update Workflows Table & Add Missing Columns
-- PostgreSQL 17+ Compatible
-- ==========================================================

BEGIN;

-- 1️⃣ Add missing timestamps to core tables
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='departments' AND column_name='created_at') THEN
        ALTER TABLE departments
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='categories' AND column_name='created_at') THEN
        ALTER TABLE categories
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='programs' AND column_name='created_at') THEN
        ALTER TABLE programs
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='directories' AND column_name='created_at') THEN
        ALTER TABLE directories
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='sections' AND column_name='created_at') THEN
        ALTER TABLE sections
            ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
    END IF;
END $$;

-- 2️⃣ Workflows table adjustments
ALTER TABLE workflows
    DROP COLUMN IF EXISTS role_slug,
    ADD COLUMN IF NOT EXISTS signatory_title VARCHAR(120) NOT NULL DEFAULT 'Signatory',
    ADD COLUMN IF NOT EXISTS section_id INT NULL,
    ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- 3️⃣ Add foreign key for section_id if missing
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.table_constraints
        WHERE table_name='workflows' AND constraint_name='fk_workflows_section'
    ) THEN
        ALTER TABLE workflows
            ADD CONSTRAINT fk_workflows_section FOREIGN KEY (section_id)
            REFERENCES sections(id) ON DELETE SET NULL;
    END IF;
END $$;

-- 4️⃣ Backfill updated_at where NULL
UPDATE workflows SET updated_at = CURRENT_TIMESTAMP WHERE updated_at IS NULL;

-- 5️⃣ Record migration into migrations_log
INSERT INTO migrations_log (batch, migration_name, action, details)
VALUES (
    (SELECT COALESCE(MAX(batch), 0) + 1 FROM migrations_log),
    '2025_09_20_update_workflows_pg.sql',
    'run',
    'Added signatory_title, section_id, created_at, updated_at to workflows + backfilled timestamps'
);

COMMIT;
