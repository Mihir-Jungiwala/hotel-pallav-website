-- Run once on the live database to add login/reset-request throttling.
CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  identifier VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY identifier_ip (identifier, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
