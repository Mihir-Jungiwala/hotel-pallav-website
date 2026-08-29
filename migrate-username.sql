-- Run once on the live database to add usernames (login now uses username, not email).
ALTER TABLE users ADD COLUMN username VARCHAR(50) NULL AFTER name;

-- Backfill existing accounts with a username derived from their email (everything before the @).
UPDATE users SET username = SUBSTRING_INDEX(email, '@', 1) WHERE username IS NULL;

-- De-duplicate any collisions from the backfill by appending the user id.
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
