ALTER TABLE users ADD COLUMN role ENUM('master_admin','admin','editor','viewer') NOT NULL DEFAULT 'admin';

-- Makes your oldest account the Master Admin. Run this once — if you'd rather
-- a different account be the master, change the WHERE id=... to that account's id first.
UPDATE users SET role = 'master_admin' WHERE id = (SELECT id FROM (SELECT id FROM users ORDER BY id LIMIT 1) t);
