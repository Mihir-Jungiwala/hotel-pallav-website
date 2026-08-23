<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/rooms.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$room = db_one('SELECT * FROM rooms WHERE id = ?', [$id]);

if (!$room) { flash('error', 'Room not found.'); redirect('admin/rooms.php'); }

$hasBookings = db_one('SELECT id FROM bookings WHERE room_id = ? LIMIT 1', [$id]);
if ($hasBookings) {
    flash('error', "Can't delete {$room['name']} — it has existing bookings. Mark it unavailable instead.");
    redirect('admin/rooms.php');
}

foreach (json_decode_field($room['photos']) as $path) {
    $full = UPLOADS_PATH . '/rooms/' . basename($path);
    if (is_file($full)) @unlink($full);
}

db_run('DELETE FROM rooms WHERE id = ?', [$id]);
log_activity('room.deleted', "Deleted room category \"{$room['name']}\"");
flash('success', "{$room['name']} removed.");
redirect('admin/rooms.php');
