<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/nearby-places.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$title = mb_substr(trim($_POST['title'] ?? ''), 0, 80);
$distanceLabel = mb_substr(trim($_POST['distance_label'] ?? ''), 0, 40);
$mapQuery = mb_substr(trim($_POST['map_query'] ?? ''), 0, 255) ?: null;
$mapUrl = mb_substr(trim($_POST['map_url'] ?? ''), 0, 500) ?: null;

if ($title === '') {
    flash('error', 'Place name is required.');
    redirect('admin/nearby-places.php');
}
// Only accept a real http(s) URL - a bad paste (or anything worse) shouldn't end
// up as a live homepage link, and this is one of the two fields here that becomes
// an href (the other, map_query, only ever becomes a urlencode()'d query param).
if ($mapUrl !== null && (!filter_var($mapUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $mapUrl))) {
    flash('error', 'That Google Maps link doesn\'t look valid - copy it fresh from the Share button and try again.');
    redirect('admin/nearby-places.php');
}

// Resolved once here rather than on every homepage pageview: a Maps link gets
// turned into a plain lat/lng pair now, stored, and that's what the public page
// reads - no outbound request to Google on every visitor's page load.
$originLat = null;
$originLng = null;
if ($mapUrl !== null) {
    $coords = resolve_maps_coordinates($mapUrl);
    if ($coords) {
        $originLat = $coords['lat'];
        $originLng = $coords['lng'];
    }
}

// With coordinates resolved and no manually-typed distance, work the distance out
// ourselves: straight-line distance from the hotel's own pin (Settings -> "Hotel's
// Google Maps Link"). Needs that pin set first - without it there's nothing to
// measure from, so the admin still has to type a distance by hand until they set it.
if ($distanceLabel === '' && $originLat !== null && $originLng !== null) {
    $settings = get_settings();
    if (!empty($settings['map_lat']) && !empty($settings['map_lng'])) {
        $km = haversine_km((float) $settings['map_lat'], (float) $settings['map_lng'], $originLat, $originLng);
        $distanceLabel = '~' . format_km_label($km);
    }
}

if ($distanceLabel === '') {
    flash('error', 'Distance is required - either type it in, or paste a Google Maps link (for the place itself, not directions) and set the hotel\'s own location in Settings first so it can be worked out automatically.');
    redirect('admin/nearby-places.php');
}

$place = $id ? db_one('SELECT * FROM nearby_places WHERE id = ?', [$id]) : null;

if ($id && $place) {
    db_run('UPDATE nearby_places SET title = ?, distance_label = ?, map_query = ?, map_url = ?, origin_lat = ?, origin_lng = ? WHERE id = ?', [$title, $distanceLabel, $mapQuery, $mapUrl, $originLat, $originLng, $id]);
    log_activity('nearby_place.updated', "Updated nearby place \"{$title}\"", 'nearby_place', $id);
    flash('success', "\"{$title}\" updated.");
} elseif (!$id) {
    $maxSort = (int) (db_one('SELECT MAX(sort_order) m FROM nearby_places')['m'] ?? 0);
    $newId = db_insert('INSERT INTO nearby_places (title, distance_label, map_query, map_url, origin_lat, origin_lng, sort_order, created_at, updated_at) VALUES (?,?,?,?,?,?,?,NOW(),NOW())', [$title, $distanceLabel, $mapQuery, $mapUrl, $originLat, $originLng, $maxSort + 1]);
    log_activity('nearby_place.created', "Added nearby place \"{$title}\"", 'nearby_place', $newId);
    flash('success', "\"{$title}\" added.");
}

redirect('admin/nearby-places.php');
