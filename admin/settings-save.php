<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/settings.php'); }
verify_csrf();

$settings = get_settings();
$imgAllowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];

$required = ['opened_year', 'gm_phone', 'reception_phone', 'whatsapp', 'email', 'address', 'checkin_time', 'checkout_time', 'google_rating'];
foreach ($required as $field) {
    if (trim($_POST[$field] ?? '') === '') {
        flash('error', 'Please fill in all required fields.');
        redirect('admin/settings.php');
    }
}

$logoPath = $settings['logo_path'];
if (!empty($_POST['remove_logo']) && $logoPath) {
    $f = UPLOADS_PATH . '/' . $logoPath;
    if (is_file($f)) unlink($f);
    $logoPath = null;
}
if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $imgAllowed, true)) {
        if ($logoPath) { $f = UPLOADS_PATH . '/' . $logoPath; if (is_file($f)) unlink($f); }
        if (!is_dir(UPLOADS_PATH . '/branding')) mkdir(UPLOADS_PATH . '/branding', 0755, true);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], UPLOADS_PATH . '/branding/' . $filename)) {
            $logoPath = 'branding/' . $filename;
        }
    }
}

$faviconPath = $settings['favicon_path'];
if (!empty($_POST['remove_favicon']) && $faviconPath) {
    $f = UPLOADS_PATH . '/' . $faviconPath;
    if (is_file($f)) unlink($f);
    $faviconPath = null;
}
if (!empty($_FILES['favicon']['name']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, $imgAllowed, true)) {
        if ($faviconPath) { $f = UPLOADS_PATH . '/' . $faviconPath; if (is_file($f)) unlink($f); }
        if (!is_dir(UPLOADS_PATH . '/branding')) mkdir(UPLOADS_PATH . '/branding', 0755, true);
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], UPLOADS_PATH . '/branding/' . $filename)) {
            $faviconPath = 'branding/' . $filename;
        }
    }
}

db_run(
    'UPDATE settings SET opened_year=?, gm_phone=?, reception_phone=?, whatsapp=?, email=?, address=?, checkin_time=?, checkout_time=?, show_prices=?, meta_title=?, meta_description=?, meta_keywords=?, logo_path=?, favicon_path=?, gbp_link=?, facebook_link=?, instagram_link=?, google_maps_api_key=?, google_place_id=?, google_min_review_rating=?, google_rating=?, google_review_count=? WHERE id=?',
    [
        (int) $_POST['opened_year'], trim($_POST['gm_phone']), trim($_POST['reception_phone']), trim($_POST['whatsapp']),
        trim($_POST['email']), trim($_POST['address']), trim($_POST['checkin_time']), trim($_POST['checkout_time']),
        !empty($_POST['show_prices']) ? 1 : 0,
        $_POST['meta_title'] ?: null, $_POST['meta_description'] ?: null, $_POST['meta_keywords'] ?: null,
        $logoPath, $faviconPath, $_POST['gbp_link'] ?: null,
        $_POST['facebook_link'] ?: null, $_POST['instagram_link'] ?: null,
        $_POST['google_maps_api_key'] ?: null, $_POST['google_place_id'] ?: null,
        max(1, min(5, (int) ($_POST['google_min_review_rating'] ?? 3))),
        trim($_POST['google_rating']), (int) ($_POST['google_review_count'] ?? 0),
        $settings['id'],
    ]
);

log_activity('settings.updated', 'Updated site settings');
flash('success', 'Settings saved.');
redirect('admin/settings.php');
