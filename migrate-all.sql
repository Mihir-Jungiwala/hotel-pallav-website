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

-- page_content: quick-check bar title + result messages
ALTER TABLE page_content ADD COLUMN quick_check_title VARCHAR(100) NOT NULL DEFAULT 'Check availability' AFTER hero_lead;
ALTER TABLE page_content ADD COLUMN qc_msg_pick_dates VARCHAR(200) NOT NULL DEFAULT 'Please pick both check-in and check-out dates first.' AFTER quick_check_title;
ALTER TABLE page_content ADD COLUMN qc_msg_available VARCHAR(200) NOT NULL DEFAULT 'Good news - we have rooms for your dates!' AFTER qc_msg_pick_dates;
ALTER TABLE page_content ADD COLUMN qc_msg_unavailable VARCHAR(300) NOT NULL DEFAULT 'Those exact rooms look full, but call us - dates shift often and we may still fit you in.' AFTER qc_msg_available;
ALTER TABLE page_content ADD COLUMN qc_msg_error VARCHAR(200) NOT NULL DEFAULT 'Could not check right now - please call us instead.' AFTER qc_msg_unavailable;
ALTER TABLE page_content ADD COLUMN fm_msg_name VARCHAR(150) NOT NULL DEFAULT 'Please enter your name.' AFTER qc_msg_error;
ALTER TABLE page_content ADD COLUMN fm_msg_phone VARCHAR(150) NOT NULL DEFAULT 'Please enter a valid 10-digit mobile number.' AFTER fm_msg_name;
ALTER TABLE page_content ADD COLUMN fm_msg_email VARCHAR(150) NOT NULL DEFAULT 'That email address does not look right. Leave it blank if you prefer.' AFTER fm_msg_phone;
ALTER TABLE page_content ADD COLUMN fm_msg_checkin VARCHAR(150) NOT NULL DEFAULT 'Please pick a check-in date.' AFTER fm_msg_email;
ALTER TABLE page_content ADD COLUMN fm_msg_checkout VARCHAR(150) NOT NULL DEFAULT 'Please pick a check-out date.' AFTER fm_msg_checkin;
ALTER TABLE page_content ADD COLUMN fm_msg_room VARCHAR(150) NOT NULL DEFAULT 'Please pick a room, or enter "not sure yet".' AFTER fm_msg_checkout;
ALTER TABLE page_content ADD COLUMN fm_msg_adults VARCHAR(150) NOT NULL DEFAULT 'Please enter the number of adults.' AFTER fm_msg_room;
ALTER TABLE page_content ADD COLUMN fm_msg_children VARCHAR(150) NOT NULL DEFAULT 'Please enter the number of children (0 if none).' AFTER fm_msg_adults;
ALTER TABLE page_content ADD COLUMN fm_msg_message VARCHAR(150) NOT NULL DEFAULT 'Please tell us anything we should know (or write "none").' AFTER fm_msg_children;

-- rooms: admin-editable badge pill (e.g. "Most Booked", "Premium")
ALTER TABLE rooms ADD COLUMN badge_label VARCHAR(40) NULL AFTER note;

-- settings.notify_email: now holds a list of admin notification addresses (one per
-- line), not just one - widen it from a single VARCHAR to TEXT.
ALTER TABLE settings MODIFY COLUMN notify_email TEXT NULL;

-- users.username: make login/uniqueness genuinely case-sensitive ("Mihir" and "mihir"
-- are different accounts) - the default collation was case-insensitive.
ALTER TABLE users MODIFY COLUMN username VARCHAR(50) NOT NULL COLLATE utf8mb4_bin;

-- enquiries: same room/dates/guests fields as bookings, so the admin edit form can
-- capture (or correct) a stay request instead of just contact details + a message.
ALTER TABLE enquiries
  ADD COLUMN room_id INT UNSIGNED NULL AFTER message,
  ADD COLUMN check_in DATE NULL AFTER room_id,
  ADD COLUMN check_out DATE NULL AFTER check_in,
  ADD COLUMN guests TINYINT UNSIGNED NULL AFTER check_out,
  ADD CONSTRAINT fk_enquiries_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL;

-- enquiries: cancellation reason, same as bookings.decision_note - required whenever
-- an enquiry is declined so the guest activity table can show why.
ALTER TABLE enquiries ADD COLUMN decision_note VARCHAR(255) NULL AFTER status;

-- Shared reference numbering: one global HP-YYYYMMDD-NNNNN sequence for both bookings
-- and enquiries (generate_reference() in includes/helpers.php), replacing bookings'
-- old random per-year reference and enquiries' lack of one entirely. On a fresh
-- install this is all that's needed - new rows get a reference on insert. On an
-- existing install with rows already in bookings/enquiries, also renumber those rows
-- (in created_at order, oldest first) so reference_sequence continues on from them
-- instead of starting back at 1 and colliding with bookings.reference's old values.
CREATE TABLE IF NOT EXISTS reference_sequence (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE enquiries ADD COLUMN reference VARCHAR(20) NULL AFTER id;

-- A "booking" was never a different kind of thing from an "enquiry" - it's just an
-- enquiry whose status is 'confirmed'. Having two tables for one concept was the
-- actual bug: two ID schemes, two edit forms, two sets of admin scripts that had to
-- be kept in sync by hand. This folds bookings into enquiries for good, so there is
-- exactly one table, one form, one set of admin scripts from here on.
ALTER TABLE enquiries ADD COLUMN approved_by INT UNSIGNED NULL AFTER decision_note;
ALTER TABLE enquiries ADD COLUMN decided_at DATETIME NULL AFTER approved_by;
ALTER TABLE enquiries ADD CONSTRAINT fk_enquiries_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;
INSERT INTO enquiries (reference, name, phone, email, message, room_id, check_in, check_out, guests, status, decision_note, approved_by, decided_at, ip_address, created_at, updated_at)
  SELECT reference, guest_name, guest_phone, guest_email, message, room_id, check_in, check_out, guests, status, decision_note, approved_by, decided_at, ip_address, created_at, updated_at
  FROM bookings;
ALTER TABLE enquiries DROP COLUMN is_read;
RENAME TABLE bookings TO bookings_archived;

-- Reference numbering now resets every accounting year (1 April - 31 March) instead
-- of counting forever - reference_sequence (one global counter) is replaced by
-- reference_counters (one row per accounting year). A deleted entry's number is
-- still never reused within its year - the counter only ever moves forward. Seed the
-- current accounting year's row from reference_sequence's last-issued value so the
-- very next reference continues on rather than colliding with one already issued.
CREATE TABLE IF NOT EXISTS reference_counters (
  fiscal_year SMALLINT UNSIGNED PRIMARY KEY,
  counter INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO reference_counters (fiscal_year, counter)
  SELECT IF(MONTH(CURDATE()) >= 4, YEAR(CURDATE()), YEAR(CURDATE()) - 1), COALESCE(MAX(id), 0)
  FROM reference_sequence;
