ALTER TABLE activity_log ADD COLUMN user_name VARCHAR(100) NULL AFTER user_id;

-- Backfill existing rows from the current users table so past history isn't blank.
UPDATE activity_log a JOIN users u ON u.id = a.user_id SET a.user_name = u.name WHERE a.user_name IS NULL;
