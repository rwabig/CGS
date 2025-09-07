-- 20230907_add_is_active_to_users.sql
ALTER TABLE users
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER reg_no;
