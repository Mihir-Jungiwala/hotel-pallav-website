<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/services.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$svc = db_one('SELECT * FROM services WHERE id = ?', [$id]);
if ($svc) {
    if (!empty($svc['icon_path'])) {
        $f = UPLOADS_PATH . '/' . $svc['icon_path'];
        if (is_file($f)) unlink($f);
    }
    db_run('DELETE FROM services WHERE id = ?', [$id]);
    log_activity('service.deleted', "Deleted service \"{$svc['title']}\"", 'service', $id);
    flash('success', "\"{$svc['title']}\" removed.");
}
redirect('admin/services.php');
