<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$roomId = (int) ($_POST['room_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? 'EP');
$isDefault = !empty($_POST['is_default']) ? 1 : 0;
$extra = $_POST['extra_person_price'] !== '' ? (int) $_POST['extra_person_price'] : null;

$guests = $_POST['occupancy_guests'] ?? [];
$prices = $_POST['occupancy_price'] ?? [];
$ladder = [];
foreach ($guests as $i => $g) {
    if (!isset($prices[$i]) || $prices[$i] === '') continue;
    $ladder[] = ['guests' => (int) $g, 'price' => (int) $prices[$i]];
}
usort($ladder, fn($a, $b) => $a['guests'] <=> $b['guests']);

if (!$roomId || $name === '' || !$ladder) {
    flash('error', 'Please fill in the plan name and at least one occupancy price.');
    redirect('admin/pricing.php');
}

$priceDouble = null;
$priceSingle = null;
foreach ($ladder as $t) {
    if ($t['guests'] === 2) $priceDouble = $t['price'];
    if ($t['guests'] === 1) $priceSingle = $t['price'];
}
$priceDouble = $priceDouble ?? $ladder[0]['price'];

$sortOrder = (int) (db_one('SELECT MAX(sort_order) m FROM rate_plans WHERE room_id = ?', [$roomId])['m'] ?? 0) + 1;

$id = db_insert(
    'INSERT INTO rate_plans (room_id, name, code, price_double, price_single, occupancy_prices, extra_person_price, sort_order, is_default, active) VALUES (?,?,?,?,?,?,?,?,?,1)',
    [$roomId, $name, $code, $priceDouble, $priceSingle, json_encode($ladder), $extra, $sortOrder, $isDefault]
);

if ($isDefault) {
    db_run('UPDATE rate_plans SET is_default = 0 WHERE room_id = ? AND id != ?', [$roomId, $id]);
}

$room = db_one('SELECT name FROM rooms WHERE id = ?', [$roomId]);
log_activity('rate_plan.created', "Added tariff plan \"{$name}\" for " . ($room['name'] ?? ''), 'rate_plan', $id);
flash('success', "\"{$name}\" added.");
redirect('admin/pricing.php');
