-- Combined schema update for everything added in this build cycle - run top to
-- bottom in phpMyAdmin's SQL tab on the live database. If any single ALTER line
-- errors with "Duplicate column name" (meaning it was already applied), just skip
-- that one line and continue with the rest - every line here is independent.

-- settings: manager name shown in staff-facing enquiry emails (superseded below by
-- notify_recipients, but still read as a fallback, so the column must exist)
ALTER TABLE settings ADD COLUMN notify_name VARCHAR(100) NULL;

-- settings: growable list of {name, email} notification recipients
ALTER TABLE settings ADD COLUMN notify_recipients JSON NULL;

-- page_content: admin-editable Terms & Conditions text for the booking form, plus
-- its validation message
ALTER TABLE page_content ADD COLUMN booking_terms_text TEXT NULL;
ALTER TABLE page_content ADD COLUMN fm_msg_terms VARCHAR(150) NOT NULL DEFAULT 'Please accept the Terms & Conditions to continue.';

-- page_content: admin-editable footer note under the Policies & Terms section
ALTER TABLE page_content ADD COLUMN policies_footer_note TEXT NULL;
