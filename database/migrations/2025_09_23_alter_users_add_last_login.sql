BEGIN;

-- Add last_login if it does not exist
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name = 'users' AND column_name = 'last_login'
    ) THEN
        ALTER TABLE users
        ADD COLUMN last_login TIMESTAMPTZ DEFAULT NULL;
    END IF;
END$$;

COMMIT;
