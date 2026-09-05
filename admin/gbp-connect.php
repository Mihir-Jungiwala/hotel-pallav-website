<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/gbp.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

$action = $_GET['action'] ?? 'connect';

if ($action === 'disconnect') {
    gbp_disconnect();
    log_activity('gbp.disconnected', 'Disconnected Google Business Profile');
    flash('success', 'Google Business Profile disconnected. The site will use the 5-review Places API again.');
    redirect('admin/settings.php');
}

if ($action === 'refresh') {
    if (!gbp_is_connected()) {
        flash('error', 'Not connected yet.');
        redirect('admin/settings.php');
    }
    $result = gbp_fetch_all_reviews(true);
    if ($result['ok']) {
        log_activity('gbp.refreshed', 'Refreshed all Google reviews (' . count($result['reviews']) . ' total)');
        flash('success', 'Fetched ' . count($result['reviews']) . ' reviews from Google.');
    } else {
        flash('error', 'Could not refresh reviews: ' . $result['error']);
    }
    redirect('admin/settings.php');
}

// action === 'connect' - send the owner to Google's consent screen
$url = gbp_auth_url();
if (!$url) {
    flash('error', 'Save your OAuth Client ID and Secret first.');
    redirect('admin/settings.php');
}
header('Location: ' . $url);
exit;
