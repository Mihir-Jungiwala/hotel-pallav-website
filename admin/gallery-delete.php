<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/gallery.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$photo = db_one('SELECT * FROM gallery_photos WHERE id = ?', [$id]);
if ($photo) {
    $file = UPLOADS_PATH . '/' . $photo['path'];
    if (is_file($file)) unlink($file);
    db_run('DELETE FROM gallery_photos WHERE id = ?', [$id]);
    log_activity('gallery.deleted', 'Deleted a gallery photo', 'gallery_photo', $id);
    flash('success', 'Photo deleted.');
}
redirect('admin/gallery.php');
