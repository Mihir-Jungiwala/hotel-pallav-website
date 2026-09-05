<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/settings.php'); }
verify_csrf();

$settings = get_settings();
$imgAllowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'ico'];

$required = ['opened_year', 'gm_phone', 'reception_phone', 'whatsapp', 'email', 'address', 'checkin_time', 'checkout_time'];
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

$smtpPassword = trim($_POST['smtp_password'] ?? '');
if ($smtpPassword === '') {
    // Blank means "leave unchanged" - the field is masked and re-rendered blank on every load.
    $smtpPassword = $settings['smtp_password'] ?? null;
}

/**
 * API keys, OAuth secrets and mail credentials are only rendered for roles that pass
 * can_view_secrets(), so for anyone else they simply aren't in $_POST. This is a
 * full-row UPDATE, so taking them from $_POST unconditionally would silently wipe
 * every secret the moment an Editor saved the page. Keep the stored value instead,
 * and ignore these keys even if they're injected into a crafted request.
 */
$secret = function (string $key) use ($settings) {
    if (!can_view_secrets()) {
        return $settings[$key] ?? null;
    }
    return trim($_POST[$key] ?? '') ?: null;
};

// One or more notification addresses, entered one per line or comma-separated -
// keep only the ones that actually look like an email, store one per line.
$notifyEmails = preg_split('/[\r\n,]+/', $_POST['notify_email'] ?? '');
$notifyEmails = array_filter(array_map('trim', $notifyEmails), fn ($e) => $e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL));
$notifyEmail = $notifyEmails ? implode("\n", array_unique($notifyEmails)) : null;

db_run(
    'UPDATE settings SET opened_year=?, gm_phone=?, reception_phone=?, whatsapp=?, reception_whatsapp=?, email=?, address=?, checkin_time=?, checkout_time=?, meta_title=?, meta_description=?, meta_keywords=?, logo_path=?, favicon_path=?, gbp_link=?, facebook_link=?, instagram_link=?, google_maps_api_key=?, google_place_id=?, google_min_review_rating=?, gbp_oauth_client_id=?, gbp_oauth_client_secret=?, smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_from_email=?, smtp_from_name=?, notify_email=? WHERE id=?',
    [
        (int) $_POST['opened_year'], trim($_POST['gm_phone']), trim($_POST['reception_phone']), trim($_POST['whatsapp']),
        ($_POST['reception_whatsapp'] ?? '') ?: null,
        trim($_POST['email']), trim($_POST['address']), trim($_POST['checkin_time']), trim($_POST['checkout_time']),
        ($_POST['meta_title'] ?? '') ?: null, ($_POST['meta_description'] ?? '') ?: null, ($_POST['meta_keywords'] ?? '') ?: null,
        $logoPath, $faviconPath, ($_POST['gbp_link'] ?? '') ?: null,
        ($_POST['facebook_link'] ?? '') ?: null, ($_POST['instagram_link'] ?? '') ?: null,
        $secret('google_maps_api_key'), ($_POST['google_place_id'] ?? '') ?: null,
        max(1, min(5, (int) ($_POST['google_min_review_rating'] ?? 3))),
        $secret('gbp_oauth_client_id'), $secret('gbp_oauth_client_secret'),
        $secret('smtp_host'),
        can_view_secrets() ? ((int) ($_POST['smtp_port'] ?? 587) ?: 587) : (int) ($settings['smtp_port'] ?? 587),
        $secret('smtp_username'),
        can_view_secrets() ? $smtpPassword : ($settings['smtp_password'] ?? null),
        $secret('smtp_from_email'),
        $secret('smtp_from_name'),
        $notifyEmail,
        $settings['id'],
    ]
);

log_activity('settings.updated', 'Updated site settings');
flash('success', 'Settings saved.');
redirect('admin/settings.php');
