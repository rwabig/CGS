-- database/migrations/2025_09_24_alter_audit_log.sql

BEGIN;

-- Create audit_log table if it doesn’t exist
CREATE TABLE IF NOT EXISTS audit_log (
    id SERIAL PRIMARY KEY,
    user_id INT REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(150) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),        -- IPv4 or IPv6
    user_agent TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Ensure ip_address column exists
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name='audit_log' AND column_name='ip_address'
    ) THEN
        ALTER TABLE audit_log ADD COLUMN ip_address VARCHAR(45);
    END IF;
END $$;

-- Ensure user_agent column exists
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_name='audit_log' AND column_name='user_agent'
    ) THEN
        ALTER TABLE audit_log ADD COLUMN user_agent TEXT;
    END IF;
END $$;

-- Indexes for performance
CREATE INDEX IF NOT EXISTS idx_audit_log_user ON audit_log(user_id);
CREATE INDEX IF NOT EXISTS idx_audit_log_action ON audit_log(action);
CREATE INDEX IF NOT EXISTS idx_audit_log_created_at ON audit_log(created_at);

COMMIT;
