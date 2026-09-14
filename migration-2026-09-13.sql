-- Migration: Nearby Places feature + hotel map pin
-- Run this ONCE on the live database (phpMyAdmin -> SQL tab, or via mysql CLI).
-- Safe to run more than once - every statement is guarded with IF NOT EXISTS.

-- 1) New table: the "how far is it" strip shown above the homepage map.
CREATE TABLE IF NOT EXISTS nearby_places (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(80) NOT NULL,
  distance_label VARCHAR(40) NOT NULL,
  map_query VARCHAR(255) NULL,
  map_url VARCHAR(500) NULL,
  origin_lat DECIMAL(10,7) NULL,
  origin_lng DECIMAL(10,7) NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) The hotel's own precise map pin - set from Settings -> "Hotel's Google Maps
--    Link". Used by the homepage map and by Nearby Places' auto-distance calc.
--    Requires MySQL 8.0.29+ / MariaDB 10.0+ for "ADD COLUMN IF NOT EXISTS". If
--    your host is older than that, drop the "IF NOT EXISTS" from these two
--    lines - if the columns already exist you'll just get a harmless error.
ALTER TABLE settings ADD COLUMN IF NOT EXISTS map_lat DECIMAL(10,7) NULL AFTER instagram_link;
ALTER TABLE settings ADD COLUMN IF NOT EXISTS map_lng DECIMAL(10,7) NULL AFTER map_lat;
