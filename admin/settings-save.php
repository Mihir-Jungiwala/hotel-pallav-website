<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/analytics.php';
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

// Google Analytics 4 + Search Console. Checked before anything is written (uploads
// included) so an invalid ID saves nothing. The columns come from migrate-analytics.sql;
// get_settings() is SELECT *, so a missing key means it has not been run yet - the rest
// of the form still saves, unless someone typed an ID that would otherwise be lost.
$analyticsReady = array_key_exists('ga4_measurement_id', $settings) && array_key_exists('gsc_verification', $settings)
    && array_key_exists('ga4_property_id', $settings) && array_key_exists('gsc_property', $settings);
$ga4Id = analytics_normalize_ga4($_POST['ga4_measurement_id'] ?? '');
$gscCode = analytics_normalize_gsc($_POST['gsc_verification'] ?? '');
$ga4Property = analytics_normalize_ga4_property($_POST['ga4_property_id'] ?? '');
$gscProperty = analytics_normalize_gsc_property($_POST['gsc_property'] ?? '');
if (!$analyticsReady && ($ga4Id !== '' || $gscCode !== '' || $ga4Property !== '' || $gscProperty !== '')) {
    flash('error', 'Run migrate-analytics.sql in phpMyAdmin first, then save the Google Analytics / Search Console IDs again. Nothing was saved.');
    redirect('admin/settings.php');
}
if ($ga4Id === false) {
    flash('error', "That GA4 Measurement ID doesn't look right - it starts with G- followed by letters and numbers (Google Analytics > Admin > Data streams). Nothing was saved.");
    redirect('admin/settings.php');
}
if ($gscCode === false) {
    flash('error', "That Search Console code doesn't look right - paste only the content value from the HTML tag (or the whole tag). Nothing was saved.");
    redirect('admin/settings.php');
}
if ($ga4Property === false) {
    flash('error', "That GA4 property number doesn't look right - it is the number (like 516209206) under your property's name in Google Analytics. Nothing was saved.");
    redirect('admin/settings.php');
}
if ($gscProperty === false) {
    flash('error', "That Search Console property doesn't look right - type your domain (like hotelpallav.in) or the https:// address Search Console shows. Nothing was saved.");
    redirect('admin/settings.php');
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
        $dest = UPLOADS_PATH . '/branding/' . $filename;
        if (move_uploaded_file($_FILES['logo']['tmp_name'], $dest)) {
            if ($ext === 'svg') file_put_contents($dest, sanitize_svg_content(file_get_contents($dest)));
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
        $dest = UPLOADS_PATH . '/branding/' . $filename;
        if (move_uploaded_file($_FILES['favicon']['tmp_name'], $dest)) {
            if ($ext === 'svg') file_put_contents($dest, sanitize_svg_content(file_get_contents($dest)));
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
    // A field that is not on the form (the Google Business Profile card was removed from
    // Settings) keeps its saved value instead of being wiped.
    if (!can_view_secrets() || !array_key_exists($key, $_POST)) {
        return $settings[$key] ?? null;
    }
    return trim($_POST[$key] ?? '') ?: null;
};

// Each notification recipient is a name + email pair from the growable list in
// Settings - keep only rows with a real email address, name is optional (falls back
// to "Team" in the greeting if left blank).
$notifyNames = $_POST['notify_name'] ?? [];
$notifyEmailsRaw = $_POST['notify_email'] ?? [];
$notifyRecipients = [];
$seenEmails = [];
foreach ((array) $notifyEmailsRaw as $i => $email) {
    $email = trim((string) $email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
    $emailLower = strtolower($email);
    if (isset($seenEmails[$emailLower])) continue;
    $seenEmails[$emailLower] = true;
    $notifyRecipients[] = ['name' => trim((string) ($notifyNames[$i] ?? '')), 'email' => $email];
}
$notifyRecipientsJson = $notifyRecipients ? json_encode($notifyRecipients) : null;

// The hotel's own precise pin - resolved once here from a pasted Maps link, not
// re-resolved on every homepage view. A blank field, or a link that fails to
// resolve, leaves the existing lat/lng untouched rather than wiping them out.
$hotelMapUrl = trim($_POST['hotel_map_url'] ?? '');
$mapLat = $settings['map_lat'] ?? null;
$mapLng = $settings['map_lng'] ?? null;
if ($hotelMapUrl !== '') {
    if (!filter_var($hotelMapUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $hotelMapUrl)) {
        flash('error', "That Google Maps link doesn't look valid - copy it fresh from the Share button and try again.");
        redirect('admin/settings.php');
    }
    $coords = resolve_maps_coordinates($hotelMapUrl);
    if ($coords) {
        $mapLat = $coords['lat'];
        $mapLng = $coords['lng'];
    } else {
        flash('error', "Couldn't work out a location from that link - double check it's a Google Maps share link and try again. Your other changes were not saved.");
        redirect('admin/settings.php');
    }
}

// Only part of the UPDATE once migrate-analytics.sql has added the columns.
$analyticsSql = $analyticsReady ? ', ga4_measurement_id=?, gsc_verification=?, ga4_property_id=?, gsc_property=?' : '';
$analyticsParams = $analyticsReady ? [
    $ga4Id !== '' ? $ga4Id : null, $gscCode !== '' ? $gscCode : null,
    $ga4Property !== '' ? $ga4Property : null, $gscProperty !== '' ? $gscProperty : null,
] : [];

try {
    db_run(
        'UPDATE settings SET opened_year=?, gm_phone=?, reception_phone=?, whatsapp=?, reception_whatsapp=?, email=?, address=?, checkin_time=?, checkout_time=?, meta_title=?, meta_description=?, meta_keywords=?, logo_path=?, favicon_path=?, gbp_link=?, facebook_link=?, instagram_link=?, map_lat=?, map_lng=?, google_maps_api_key=?, google_place_id=?, google_min_review_rating=?, gbp_oauth_client_id=?, gbp_oauth_client_secret=?, smtp_host=?, smtp_port=?, smtp_username=?, smtp_password=?, smtp_from_email=?, smtp_from_name=?, notify_recipients=?' . $analyticsSql . ' WHERE id=?',
        array_merge([
            (int) $_POST['opened_year'], trim($_POST['gm_phone']), trim($_POST['reception_phone']), trim($_POST['whatsapp']),
            ($_POST['reception_whatsapp'] ?? '') ?: null,
            trim($_POST['email']), trim($_POST['address']), trim($_POST['checkin_time']), trim($_POST['checkout_time']),
            ($_POST['meta_title'] ?? '') ?: null, ($_POST['meta_description'] ?? '') ?: null, ($_POST['meta_keywords'] ?? '') ?: null,
            $logoPath, $faviconPath, ($_POST['gbp_link'] ?? '') ?: null,
            ($_POST['facebook_link'] ?? '') ?: null, ($_POST['instagram_link'] ?? '') ?: null,
            $mapLat, $mapLng,
            $secret('google_maps_api_key'), ($_POST['google_place_id'] ?? '') ?: null,
            max(1, min(5, (int) ($_POST['google_min_review_rating'] ?? 3))),
            $secret('gbp_oauth_client_id'), $secret('gbp_oauth_client_secret'),
            $secret('smtp_host'),
            can_view_secrets() ? ((int) ($_POST['smtp_port'] ?? 587) ?: 587) : (int) ($settings['smtp_port'] ?? 587),
            $secret('smtp_username'),
            can_view_secrets() ? $smtpPassword : ($settings['smtp_password'] ?? null),
            $secret('smtp_from_email'),
            $secret('smtp_from_name'),
            $notifyRecipientsJson,
        ], $analyticsParams, [
            $settings['id'],
        ])
    );
} catch (\Throwable $e) {
    // A raw fatal error here shows the visitor a blank 500 page with no explanation -
    // most commonly because the database is missing a column this code expects (a
    // migration that hasn't been run yet on this environment). Log the real cause for
    // whoever can fix it, but tell the person who just clicked Save something they can
    // actually act on instead of a blank white screen.
    error_log('Settings save failed: ' . $e->getMessage());
    flash('error', 'Settings could not be saved due to a server error. If this keeps happening, a recent update may need a database migration to be run - check error.log for the exact cause.');
    redirect('admin/settings.php');
}

log_activity('settings.updated', 'Updated site settings');
flash('success', 'Settings saved.');
redirect('admin/settings.php');
