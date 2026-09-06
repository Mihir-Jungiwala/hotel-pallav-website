-- Run in phpMyAdmin's SQL tab on the live site after deploying this change.
ALTER TABLE settings ADD COLUMN notify_name VARCHAR(100) NULL;
