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

if ($title === '') {
    flash('error', 'Place name is required.');
    redirect('admin/nearby-places.php');
}
if ($distanceLabel === '') {
    flash('error', 'Distance is required.');
    redirect('admin/nearby-places.php');
}

$place = $id ? db_one('SELECT * FROM nearby_places WHERE id = ?', [$id]) : null;

if ($id && $place) {
    db_run('UPDATE nearby_places SET title = ?, distance_label = ?, map_query = ? WHERE id = ?', [$title, $distanceLabel, $mapQuery, $id]);
    log_activity('nearby_place.updated', "Updated nearby place \"{$title}\"", 'nearby_place', $id);
    flash('success', "\"{$title}\" updated.");
} elseif (!$id) {
    $maxSort = (int) (db_one('SELECT MAX(sort_order) m FROM nearby_places')['m'] ?? 0);
    $newId = db_insert('INSERT INTO nearby_places (title, distance_label, map_query, sort_order, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$title, $distanceLabel, $mapQuery, $maxSort + 1]);
    log_activity('nearby_place.created', "Added nearby place \"{$title}\"", 'nearby_place', $newId);
    flash('success', "\"{$title}\" added.");
}

redirect('admin/nearby-places.php');
