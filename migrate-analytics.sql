-- Google Analytics 4 + Google Search Console (Admin > Settings > "Google Analytics & Search Console").
-- Run each ALTER separately in phpMyAdmin's SQL tab.
-- If a column already exists, that one line will error "Duplicate column name" - just skip it and run the next line.
-- All four columns stay NULL until they are filled in Admin > Settings; NULL means that part is off.
-- ga4_property_id and gsc_property only build the dashboard's links to the Google report pages.

ALTER TABLE settings ADD COLUMN ga4_measurement_id VARCHAR(20) NULL;
ALTER TABLE settings ADD COLUMN gsc_verification VARCHAR(120) NULL;
ALTER TABLE settings ADD COLUMN ga4_property_id VARCHAR(20) NULL;
ALTER TABLE settings ADD COLUMN gsc_property VARCHAR(255) NULL;
