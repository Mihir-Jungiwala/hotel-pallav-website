-- Run in phpMyAdmin's SQL tab on the live site after deploying this change.
ALTER TABLE page_content ADD COLUMN fm_msg_success VARCHAR(250) NOT NULL DEFAULT 'Thank you! Your enquiry reference is {{reference}}. We will call you shortly to confirm.';
