-- Full combined schema update for the live database — covers every migration from
-- this build cycle. Run top to bottom. If any single ALTER line errors with
-- "Duplicate column name" or "Duplicate key name" (meaning it was already applied),
-- just skip that one line and continue with the rest — everything else is independent.

-- users: role + username
ALTER TABLE users ADD COLUMN role ENUM('master_admin','admin','editor','viewer') NOT NULL DEFAULT 'admin';
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;
UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL;
UPDATE users u
JOIN (
  SELECT username, MIN(id) AS keep_id
  FROM users
  GROUP BY username
  HAVING COUNT(*) > 1
) dupes ON u.username = dupes.username AND u.id <> dupes.keep_id
SET u.username = CONCAT(u.username, u.id);
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL;
ALTER TABLE users ADD UNIQUE KEY username (username);

-- activity_log: user_name snapshot
ALTER TABLE activity_log ADD COLUMN user_name VARCHAR(100) NULL AFTER user_id;
UPDATE activity_log a JOIN users u ON u.id = a.user_id SET a.user_name = u.name WHERE a.user_name IS NULL;

-- gallery_photos: alt text
ALTER TABLE gallery_photos ADD COLUMN alt_text VARCHAR(255) NULL;

-- policy_cards: custom icon
ALTER TABLE policy_cards ADD COLUMN icon_path VARCHAR(255) NULL;

-- rooms: manual sort order
ALTER TABLE rooms ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0;
SET @rownum := 0;
UPDATE rooms SET sort_order = (@rownum := @rownum + 1) ORDER BY id;

-- enquiries: pending -> confirmed/declined lifecycle
ALTER TABLE enquiries ADD COLUMN status ENUM('new','pending','confirmed','declined') NOT NULL DEFAULT 'new' AFTER is_read;
UPDATE enquiries SET status = 'pending' WHERE is_read = 1;

-- settings: Google Business Profile OAuth + SMTP + reception WhatsApp
ALTER TABLE settings ADD COLUMN gbp_oauth_client_id VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_client_secret VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_refresh_token TEXT NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_access_token TEXT NULL;
ALTER TABLE settings ADD COLUMN gbp_oauth_token_expires DATETIME NULL;
ALTER TABLE settings ADD COLUMN gbp_account_id VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN gbp_location_id VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_host VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_port SMALLINT UNSIGNED NULL;
ALTER TABLE settings ADD COLUMN smtp_username VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_password VARCHAR(255) NULL;
ALTER TABLE settings ADD COLUMN smtp_from_email VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN smtp_from_name VARCHAR(100) NULL;
ALTER TABLE settings ADD COLUMN notify_email VARCHAR(150) NULL;
ALTER TABLE settings ADD COLUMN reception_whatsapp VARCHAR(20) NULL;

-- new tables (safe to re-run, no-op if they already exist)
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY identifier_ip (identifier, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS services (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  icon VARCHAR(30) NOT NULL DEFAULT 'front-desk',
  icon_path VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS email_templates (
  template_key VARCHAR(50) NOT NULL PRIMARY KEY,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
