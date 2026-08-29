-- Creates a new admin login. Run once in phpMyAdmin's SQL tab, then delete this file.
-- Login email: hotelpallavrajkot@gmail.com
-- Login password: see the message in the chat (not stored in this file — only the bcrypt hash is).

INSERT INTO users (name, email, password, created_at, updated_at)
VALUES ('Admin', 'hotelpallavrajkot@gmail.com', '$2y$12$hsMzDgqXbBVVC49Z/7yULO1mlkrf2kA5xPSyGKCZiLSnJCKphJvQS', NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), updated_at = NOW();
