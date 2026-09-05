<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/content.php'); }
verify_csrf();

$content = get_page_content();

// These seven fields keep their HTML (they're edited as rich text and printed
// unescaped on the homepage), so each one goes through the allow-list sanitizer -
// otherwise anyone who can reach this page could store a script that runs for every
// visitor. Everything else on the form is escaped at output and needs no treatment.
$richFields = ['hero_lead', 'about_p1', 'about_p2', 'about_p3', 'enquire_lead', 'footer_tagline', 'footer_credit'];
$clean = [];
foreach ($richFields as $field) {
    $clean[$field] = sanitize_rich_text($_POST[$field] ?? '');
}
$rich = fn (string $field) => $clean[$field];

// Required fields are checked against their sanitized value, so a field holding
// nothing but markup the sanitizer strips is caught here rather than saved blank.
$required = ['hero_title_line1', 'hero_title_emphasis', 'hero_lead', 'quick_check_title', 'qc_msg_pick_dates', 'qc_msg_available', 'qc_msg_unavailable', 'qc_msg_error', 'fm_msg_name', 'fm_msg_phone', 'fm_msg_email', 'fm_msg_checkin', 'fm_msg_checkout', 'fm_msg_room', 'fm_msg_adults', 'fm_msg_children', 'fm_msg_message', 'about_kicker', 'about_heading', 'enquire_heading', 'enquire_lead', 'footer_tagline'];
foreach ($required as $field) {
    $value = array_key_exists($field, $clean) ? strip_tags($clean[$field]) : ($_POST[$field] ?? '');
    if (trim($value) === '') {
        flash('error', 'Please fill in all required fields.');
        redirect('admin/content.php');
    }
}

$points = array_values(array_filter(array_map('trim', explode("\n", $_POST['enquire_points'] ?? ''))));

db_run(
    'UPDATE page_content SET hero_title_line1=?, hero_title_emphasis=?, hero_lead=?, quick_check_title=?, qc_msg_pick_dates=?, qc_msg_available=?, qc_msg_unavailable=?, qc_msg_error=?, fm_msg_name=?, fm_msg_phone=?, fm_msg_email=?, fm_msg_checkin=?, fm_msg_checkout=?, fm_msg_room=?, fm_msg_adults=?, fm_msg_children=?, fm_msg_message=?, about_kicker=?, about_heading=?, about_p1=?, about_p2=?, about_p3=?, enquire_heading=?, enquire_lead=?, enquire_points=?, footer_tagline=?, footer_credit=? WHERE id=?',
    [
        trim($_POST['hero_title_line1']), trim($_POST['hero_title_emphasis']), $rich('hero_lead'), trim($_POST['quick_check_title']),
        trim($_POST['qc_msg_pick_dates']), trim($_POST['qc_msg_available']), trim($_POST['qc_msg_unavailable']), trim($_POST['qc_msg_error']),
        trim($_POST['fm_msg_name']), trim($_POST['fm_msg_phone']), trim($_POST['fm_msg_email']), trim($_POST['fm_msg_checkin']),
        trim($_POST['fm_msg_checkout']), trim($_POST['fm_msg_room']), trim($_POST['fm_msg_adults']), trim($_POST['fm_msg_children']), trim($_POST['fm_msg_message']),
        trim($_POST['about_kicker']), trim($_POST['about_heading']),
        $rich('about_p1') ?: null, $rich('about_p2') ?: null, $rich('about_p3') ?: null,
        trim($_POST['enquire_heading']), $rich('enquire_lead'), json_encode($points),
        $rich('footer_tagline'), $rich('footer_credit') ?: null,
        $content['id'],
    ]
);

log_activity('content.updated', 'Updated homepage content');
flash('success', 'Page content saved.');
redirect('admin/content.php');
