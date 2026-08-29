-- Run once on the live database: gives enquiries the same pending -> confirmed/declined
-- lifecycle as bookings, instead of just a read/unread flag.
ALTER TABLE enquiries ADD COLUMN status ENUM('new','pending','confirmed','declined') NOT NULL DEFAULT 'new' AFTER is_read;

-- Backfill: anything already marked read is treated as having entered the pending queue.
UPDATE enquiries SET status = 'pending' WHERE is_read = 1;
