-- Run each ALTER separately in phpMyAdmin's SQL tab.
-- If a column already exists, that one line will error "Duplicate column name" — just skip it and run the next line.

ALTER TABLE settings ADD COLUMN gbp_oauth_client_id VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_client_secret VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_refresh_token TEXT NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_access_token TEXT NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_token_expires DATETIME NULL;
ALTER TABLE settings ADD COLUMN gbp_account_id VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN gbp_location_id VARCHAR(150) NULL;

-- Email (SMTP) settings
ALTER TABLE settings ADD COLUMN smtp_host VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_port SMALLINT UNSIGNED NULL;
ALTER TABLE settings ADD COLUMN smtp_username VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_password VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN smtp_from_email VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_from_name VARCHAR(100) NULL;
ALTER TABLE settings ADD COLUMN notify_email VARCHAR(150) NULL;
