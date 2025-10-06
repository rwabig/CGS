-- database/migrations/2025_09_28_alter_staff_profiles.sql
BEGIN;

ALTER TABLE staff_profiles
    ADD COLUMN IF NOT EXISTS directory_id INT REFERENCES directories(id) ON DELETE SET NULL,
    ADD COLUMN IF NOT EXISTS section_id   INT REFERENCES sections(id)   ON DELETE SET NULL;

COMMIT;
