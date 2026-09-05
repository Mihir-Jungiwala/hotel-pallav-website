<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/rooms.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$room = db_one('SELECT * FROM rooms WHERE id = ?', [$id]);

if (!$room) { flash('error', 'Room not found.'); redirect('admin/rooms.php'); }

$hasEnquiries = db_one('SELECT id FROM enquiries WHERE room_id = ? LIMIT 1', [$id]);
if ($hasEnquiries) {
    flash('error', "Can't delete {$room['name']} - it has existing enquiries/bookings. Mark it unavailable instead.");
    redirect('admin/rooms.php');
}

foreach (normalize_room_photos(json_decode_field($room['photos'])) as $photo) {
    $full = UPLOADS_PATH . '/rooms/' . basename($photo['path']);
    if (is_file($full)) @unlink($full);
}

db_run('DELETE FROM rooms WHERE id = ?', [$id]);
log_activity('room.deleted', "Deleted room category \"{$room['name']}\"");
flash('success', "{$room['name']} removed.");
redirect('admin/rooms.php');
