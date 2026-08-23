<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/content.php'); }
verify_csrf();

$content = get_page_content();

$required = ['hero_eyebrow', 'hero_title_line1', 'hero_title_emphasis', 'hero_lead', 'about_kicker', 'about_heading', 'enquire_heading', 'enquire_lead', 'footer_tagline'];
foreach ($required as $field) {
    if (trim($_POST[$field] ?? '') === '') {
        flash('error', 'Please fill in all required fields.');
        redirect('admin/content.php');
    }
}

$validIcons = array_keys(SERVICE_ICONS);
$services = [];
$serviceIcons = $_POST['service_icon'] ?? [];
$serviceTitles = $_POST['service_title'] ?? [];
$serviceDescs = $_POST['service_desc'] ?? [];
foreach ($serviceTitles as $i => $t) {
    $t = trim($t);
    if ($t === '') continue;
    $icon = in_array($serviceIcons[$i] ?? '', $validIcons, true) ? $serviceIcons[$i] : 'wifi';
    $services[] = ['icon' => $icon, 'title' => $t, 'desc' => trim($serviceDescs[$i] ?? '')];
}

$points = array_values(array_filter(array_map('trim', explode("\n", $_POST['enquire_points'] ?? ''))));

db_run(
    'UPDATE page_content SET hero_eyebrow=?, hero_title_line1=?, hero_title_emphasis=?, hero_lead=?, about_kicker=?, about_heading=?, about_p1=?, about_p2=?, about_p3=?, services=?, enquire_heading=?, enquire_lead=?, enquire_points=?, footer_tagline=?, footer_credit=? WHERE id=?',
    [
        trim($_POST['hero_eyebrow']), trim($_POST['hero_title_line1']), trim($_POST['hero_title_emphasis']), $_POST['hero_lead'],
        trim($_POST['about_kicker']), trim($_POST['about_heading']), $_POST['about_p1'] ?: null, $_POST['about_p2'] ?: null, $_POST['about_p3'] ?: null,
        json_encode($services),
        trim($_POST['enquire_heading']), $_POST['enquire_lead'], json_encode($points),
        $_POST['footer_tagline'], $_POST['footer_credit'] ?: null,
        $content['id'],
    ]
);

log_activity('content.updated', 'Updated homepage content');
flash('success', 'Page content saved.');
redirect('admin/content.php');
