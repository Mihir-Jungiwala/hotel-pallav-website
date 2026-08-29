-- Registers the photos already sitting in uploads/gallery/ (uploaded previously but
-- never added to the database) as gallery entries, with a name + alt text each.
-- Safe to run once; skips any path already registered.

INSERT INTO gallery_photos (path, caption, alt_text, sort_order, created_at, updated_at)
SELECT * FROM (SELECT
  'gallery/hotel-pallav-rajkot-building-exterior-day.jpg' AS path,
  'Hotel Building — Exterior' AS caption,
  'Hotel Pallav Rajkot building exterior, daytime view' AS alt_text,
  10 AS sort_order, NOW() AS created_at, NOW() AS updated_at
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-1.jpg', 'Deluxe Room', 'Deluxe room interior at Hotel Pallav Rajkot', 20, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-2.jpg', 'Deluxe Room — Bed', 'Deluxe room bed setup, Hotel Pallav Rajkot', 21, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-3.jpg', 'Deluxe Room — Seating', 'Deluxe room seating area, Hotel Pallav Rajkot', 22, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-4.jpg', 'Deluxe Room — View', 'Deluxe room view, Hotel Pallav Rajkot', 23, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-5.jpg', 'Deluxe Room — Detail', 'Deluxe room detail shot, Hotel Pallav Rajkot', 24, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-6.jpg', 'Deluxe Room — Layout', 'Deluxe room layout, Hotel Pallav Rajkot', 25, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-accent.jpg', 'Deluxe Room — Accent Wall', 'Deluxe room accent wall, Hotel Pallav Rajkot', 26, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-tv-fridge.jpg', 'Deluxe Room — TV & Fridge', 'Deluxe room TV and mini fridge, Hotel Pallav Rajkot', 27, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-tv-unit.jpg', 'Deluxe Room — TV Unit', 'Deluxe room TV unit, Hotel Pallav Rajkot', 28, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-deluxe-room-wardrobe-safe.jpg', 'Deluxe Room — Wardrobe & Safe', 'Deluxe room wardrobe and safe, Hotel Pallav Rajkot', 29, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-super-deluxe-room-1.jpg', 'Super Deluxe Room', 'Super Deluxe room interior, Hotel Pallav Rajkot', 30, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-super-deluxe-room-2.jpg', 'Super Deluxe Room — Detail', 'Super Deluxe room detail shot, Hotel Pallav Rajkot', 31, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-compact-room-1.jpg', 'Compact Room', 'Compact room interior, Hotel Pallav Rajkot', 40, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-rajkot-guest-room-corridor-1.jpg', 'Guest Room Corridor', 'Guest room corridor, Hotel Pallav Rajkot', 50, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-rajkot-guest-room-corridor-2.jpg', 'Guest Room Corridor', 'Guest room corridor view, Hotel Pallav Rajkot', 51, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-rajkot-guest-room-corridor-3.jpg', 'Guest Room Corridor', 'Guest room corridor, Hotel Pallav Rajkot', 52, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-rajkot-guest-room-corridor-4.jpg', 'Guest Room Corridor', 'Guest room corridor, Hotel Pallav Rajkot', 53, NOW(), NOW()
UNION ALL SELECT 'gallery/hotel-pallav-room-accent-pillows.jpg', 'Room Accent Pillows', 'Room accent pillow styling, Hotel Pallav Rajkot', 60, NOW(), NOW()
) AS seed
WHERE seed.path NOT IN (SELECT path FROM gallery_photos);
