<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/gbp.php';
require_admin();

if (!empty($_GET['error'])) {
    flash('error', 'Google sign-in was cancelled or denied: ' . e($_GET['error']));
    redirect('admin/settings.php');
}

$code = $_GET['code'] ?? '';
if ($code === '') {
    flash('error', 'No authorization code returned by Google.');
    redirect('admin/settings.php');
}

$exchange = gbp_exchange_code($code);
if (!$exchange['ok']) {
    flash('error', 'Could not complete Google sign-in: ' . $exchange['error']);
    redirect('admin/settings.php');
}

$resolved = gbp_resolve_account_and_location();
if (!$resolved['ok']) {
    // Token is saved, but we couldn't confirm the business — most often
    // this means the My Business API access request hasn't been approved
    // for this Cloud project yet.
    flash('error', 'Connected to Google, but could not read your Business Profile: ' . $resolved['error']);
    redirect('admin/settings.php');
}

log_activity('gbp.connected', 'Connected Google Business Profile: ' . ($resolved['title'] ?? ''));
flash('success', 'Connected to Google Business Profile (' . e($resolved['title'] ?? 'your listing') . '). Fetching all reviews now…');
redirect('admin/gbp-connect.php?action=refresh');
