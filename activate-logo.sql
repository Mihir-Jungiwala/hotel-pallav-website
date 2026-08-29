-- Sets the common brand-mark SVG as the active site logo.
-- Run once in phpMyAdmin, after uploading uploads/branding/e955d62c7abb16564259e5853e4b8a8b.svg to the server.

UPDATE settings SET logo_path = 'branding/e955d62c7abb16564259e5853e4b8a8b.svg' WHERE id = 1;
