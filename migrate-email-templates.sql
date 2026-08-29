-- Run once on the live database to enable customizable guest-facing email templates.
CREATE TABLE IF NOT EXISTS email_templates (
  template_key VARCHAR(50) NOT NULL PRIMARY KEY,
  subject VARCHAR(200) NOT NULL,
  body TEXT NOT NULL,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
