ALTER TABLE rooms ADD COLUMN sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0;
-- Seed sort_order to match current id order so nothing jumps around on first load.
SET @rownum := 0;
UPDATE rooms SET sort_order = (@rownum := @rownum + 1) ORDER BY id;
