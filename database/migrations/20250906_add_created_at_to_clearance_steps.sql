-- Add timestamp to clearance_steps (so ordering by created_at is possible)
ALTER TABLE clearance_steps
  ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status;
