-- Run in phpMyAdmin's SQL tab on the live site after deploying this change.
ALTER TABLE enquiries ADD COLUMN children TINYINT UNSIGNED NULL AFTER guests;
