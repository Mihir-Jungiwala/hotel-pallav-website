<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/services.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$title = mb_substr(trim($_POST['title'] ?? ''), 0, 80);
$description = mb_substr(trim($_POST['description'] ?? ''), 0, 255) ?: null;

if ($title === '') {
    flash('error', 'Service title is required.');
    redirect('admin/services.php');
}

$svc = $id ? db_one('SELECT * FROM services WHERE id = ?', [$id]) : null;

$imgAllowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];
$iconPath = $svc['icon_path'] ?? null;
if (!empty($_POST['remove_icon']) && $iconPath) {
    $f = UPLOADS_PATH . '/' . $iconPath;
    if (is_file($f)) unlink($f);
    $iconPath = null;
}
if (!empty($_FILES['icon_file']['name']) && $_FILES['icon_file']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['icon_file']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $imgAllowed, true)) {
        if ($iconPath) { $f = UPLOADS_PATH . '/' . $iconPath; if (is_file($f)) unlink($f); }
        if (!is_dir(UPLOADS_PATH . '/services')) mkdir(UPLOADS_PATH . '/services', 0755, true);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($_FILES['icon_file']['tmp_name'], UPLOADS_PATH . '/services/' . $filename)) {
            $iconPath = 'services/' . $filename;
        }
    }
}

if ($id && $svc) {
    db_run('UPDATE services SET title = ?, description = ?, icon_path = ? WHERE id = ?', [$title, $description, $iconPath, $id]);
    log_activity('service.updated', "Updated service \"{$title}\"", 'service', $id);
    flash('success', "\"{$title}\" updated.");
} elseif (!$id) {
    $maxSort = (int) (db_one('SELECT MAX(sort_order) m FROM services')['m'] ?? 0);
    $newId = db_insert('INSERT INTO services (title, description, icon_path, sort_order, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$title, $description, $iconPath, $maxSort + 1]);
    log_activity('service.created', "Added service \"{$title}\"", 'service', $newId);
    flash('success', "\"{$title}\" added.");
}

redirect('admin/services.php');
