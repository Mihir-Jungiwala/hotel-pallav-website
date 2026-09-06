-- Run in phpMyAdmin's SQL tab on the live site after deploying this change.
ALTER TABLE page_content ADD COLUMN booking_terms_text TEXT NULL;
ALTER TABLE page_content ADD COLUMN fm_msg_terms VARCHAR(150) NOT NULL DEFAULT 'Please accept the Terms & Conditions to continue.';
