-- Hotel Pallav — full plain-PHP schema (mirrors the Laravel build's feature set)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE COLLATE utf8mb4_bin,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('master_admin','admin','editor','viewer') NOT NULL DEFAULT 'admin',
  remember_token VARCHAR(100) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS password_resets (
  email VARCHAR(150) NOT NULL,
  token VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY identifier_ip (identifier, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  opened_year SMALLINT UNSIGNED NOT NULL DEFAULT 2002,
  gm_phone VARCHAR(20) NOT NULL DEFAULT '+919825735404',
  reception_phone VARCHAR(20) NOT NULL DEFAULT '+917043535404',
  whatsapp VARCHAR(20) NOT NULL DEFAULT '919825735404',
  reception_whatsapp VARCHAR(20) NULL,
  email VARCHAR(150) NOT NULL DEFAULT 'hotelpallavrajkot@gmail.com',
  address VARCHAR(255) NOT NULL DEFAULT '',
  checkin_time VARCHAR(20) NOT NULL DEFAULT '12:00 PM',
  checkout_time VARCHAR(20) NOT NULL DEFAULT '11:00 AM',
  show_prices TINYINT(1) NOT NULL DEFAULT 0,
  meta_title VARCHAR(255) NULL,
  meta_description TEXT NULL,
  meta_keywords VARCHAR(255) NULL,
  logo_path VARCHAR(255) NULL,
  favicon_path VARCHAR(255) NULL,
  gbp_link VARCHAR(255) NULL,
  facebook_link VARCHAR(255) NULL,
  instagram_link VARCHAR(255) NULL,
  google_maps_api_key VARCHAR(100) NULL,
  google_place_id VARCHAR(150) NULL,
  google_min_review_rating TINYINT UNSIGNED NOT NULL DEFAULT 3,
  google_rating VARCHAR(4) NOT NULL DEFAULT '4.1',
  google_review_count INT UNSIGNED NOT NULL DEFAULT 938,
  gbp_oauth_client_id VARCHAR(255) NULL,
  gbp_oauth_client_secret VARCHAR(255) NULL,
  gbp_oauth_refresh_token TEXT NULL,
  gbp_oauth_access_token TEXT NULL,
  gbp_oauth_token_expires DATETIME NULL,
  gbp_account_id VARCHAR(150) NULL,
  gbp_location_id VARCHAR(150) NULL,
  smtp_host VARCHAR(150) NULL,
  smtp_port SMALLINT UNSIGNED NULL,
  smtp_username VARCHAR(150) NULL,
  smtp_password VARCHAR(255) NULL,
  smtp_from_email VARCHAR(150) NULL,
  smtp_from_name VARCHAR(100) NULL,
  notify_email TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS page_content (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  hero_eyebrow VARCHAR(100) NOT NULL DEFAULT 'Of Warm Hospitality',
  hero_title_line1 VARCHAR(100) NOT NULL DEFAULT 'Where every stay',
  hero_title_emphasis VARCHAR(100) NOT NULL DEFAULT 'feels like coming home',
  hero_lead TEXT NULL,
  quick_check_title VARCHAR(100) NOT NULL DEFAULT 'Check availability',
  qc_msg_pick_dates VARCHAR(200) NOT NULL DEFAULT 'Please pick both check-in and check-out dates first.',
  qc_msg_available VARCHAR(200) NOT NULL DEFAULT 'Good news - we have rooms for your dates!',
  qc_msg_unavailable VARCHAR(300) NOT NULL DEFAULT 'Those exact rooms look full, but call us - dates shift often and we may still fit you in.',
  qc_msg_error VARCHAR(200) NOT NULL DEFAULT 'Could not check right now - please call us instead.',
  fm_msg_name VARCHAR(150) NOT NULL DEFAULT 'Please enter your name.',
  fm_msg_phone VARCHAR(150) NOT NULL DEFAULT 'Please enter a valid 10-digit mobile number.',
  fm_msg_email VARCHAR(150) NOT NULL DEFAULT 'That email address does not look right. Leave it blank if you prefer.',
  fm_msg_checkin VARCHAR(150) NOT NULL DEFAULT 'Please pick a check-in date.',
  fm_msg_checkout VARCHAR(150) NOT NULL DEFAULT 'Please pick a check-out date.',
  fm_msg_room VARCHAR(150) NOT NULL DEFAULT 'Please pick a room, or enter "not sure yet".',
  fm_msg_adults VARCHAR(150) NOT NULL DEFAULT 'Please enter the number of adults.',
  fm_msg_children VARCHAR(150) NOT NULL DEFAULT 'Please enter the number of children (0 if none).',
  fm_msg_message VARCHAR(150) NOT NULL DEFAULT 'Please tell us anything we should know (or write "none").',
  about_kicker VARCHAR(50) NOT NULL DEFAULT 'Our Story',
  about_heading VARCHAR(150) NOT NULL DEFAULT 'A family hotel that never stopped caring',
  about_p1 TEXT NULL,
  about_p2 TEXT NULL,
  about_p3 TEXT NULL,
  services JSON NULL,
  enquire_heading VARCHAR(150) NOT NULL DEFAULT 'Send us your dates. We will do the rest.',
  enquire_lead TEXT NULL,
  enquire_points JSON NULL,
  footer_tagline TEXT NULL,
  footer_credit TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS policy_cards (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(60) NOT NULL,
  policy_lines JSON NULL,
  icon_path VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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

CREATE TABLE IF NOT EXISTS gallery_photos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  path VARCHAR(255) NOT NULL,
  caption VARCHAR(150) NULL,
  alt_text VARCHAR(255) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rooms (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  size VARCHAR(50) NULL,
  bed_type VARCHAR(50) NULL,
  max_guests TINYINT UNSIGNED NOT NULL DEFAULT 2,
  price INT UNSIGNED NULL,
  price_with_breakfast INT UNSIGNED NULL,
  show_price TINYINT(1) NOT NULL DEFAULT 0,
  total_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
  rooms_left TINYINT UNSIGNED NOT NULL DEFAULT 1,
  available TINYINT(1) NOT NULL DEFAULT 1,
  note VARCHAR(255) NULL,
  badge_label VARCHAR(40) NULL,
  photos JSON NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id INT UNSIGNED NOT NULL,
  name VARCHAR(60) NOT NULL,
  code VARCHAR(10) NOT NULL DEFAULT 'EP',
  description VARCHAR(255) NULL,
  price_double INT UNSIGNED NOT NULL DEFAULT 0,
  price_single INT UNSIGNED NULL,
  occupancy_prices JSON NULL,
  extra_person_price INT UNSIGNED NULL,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS plan_date_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  rate_plan_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  price_double INT UNSIGNED NOT NULL,
  price_single INT UNSIGNED NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY plan_date (rate_plan_id, date),
  FOREIGN KEY (rate_plan_id) REFERENCES rate_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS room_date_inventory (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  room_id INT UNSIGNED NOT NULL,
  date DATE NOT NULL,
  rooms_left TINYINT UNSIGNED NOT NULL,
  blocked TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY room_date (room_id, date),
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One counter per accounting year (1 April - 31 March) behind generate_reference()
-- (includes/helpers.php) - every enquiry draws the next HP-YYYYMMDD-NNNNN serial from
-- its accounting year's row here. A deleted entry's number is never reused (the
-- counter only ever moves forward); a new accounting year starts its own row at 1.
CREATE TABLE IF NOT EXISTS reference_counters (
  fiscal_year SMALLINT UNSIGNED PRIMARY KEY,
  counter INT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- A "booking" is not a different kind of record - it's just an enquiry whose status
-- is 'confirmed'. Every guest request (from the site's Booking Enquiry form or
-- entered manually by an admin) lives here as one row from the moment it's
-- submitted through pending -> confirmed/declined.
CREATE TABLE IF NOT EXISTS enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NULL,
  email VARCHAR(150) NULL,
  message TEXT NULL,
  room_id INT UNSIGNED NULL,
  check_in DATE NULL,
  check_out DATE NULL,
  guests TINYINT UNSIGNED NULL,
  status ENUM('new','pending','confirmed','declined') NOT NULL DEFAULT 'new',
  decision_note VARCHAR(255) NULL,
  approved_by INT UNSIGNED NULL,
  decided_at DATETIME NULL,
  ip_address VARCHAR(45) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
  FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  user_name VARCHAR(100) NULL,
  action VARCHAR(100) NOT NULL,
  subject_type VARCHAR(100) NULL,
  subject_id INT UNSIGNED NULL,
  description VARCHAR(255) NOT NULL,
  meta JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
