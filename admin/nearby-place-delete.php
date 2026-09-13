<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/nearby-places.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$place = db_one('SELECT * FROM nearby_places WHERE id = ?', [$id]);
if ($place) {
    db_run('DELETE FROM nearby_places WHERE id = ?', [$id]);
    log_activity('nearby_place.deleted', "Deleted nearby place \"{$place['title']}\"", 'nearby_place', $id);
    flash('success', "\"{$place['title']}\" removed.");
}
redirect('admin/nearby-places.php');
