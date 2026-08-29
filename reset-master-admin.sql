-- Deletes ALL existing admin users and creates a single Master Admin account.
-- Run once in phpMyAdmin's SQL tab, then delete this file from the server.
--
-- Login username: Mihir
-- Login password: Momd@d2001
--
-- WARNING: this permanently deletes every existing user account. Activity log
-- entries are unaffected (they keep a snapshot of the user's name separately).

DELETE FROM users;

INSERT INTO users (name, username, email, password, role, created_at, updated_at)
VALUES (
  'Mihir',
  'Mihir',
  'jungimihir1947@gmail.com',
  '$2y$12$ZrIDql0d/LcwFFA4zR4.U.2.sKdcwF7nCEaEo4eFPbzPMD497s.ei',
  'master_admin',
  NOW(),
  NOW()
);
