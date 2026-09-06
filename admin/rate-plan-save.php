<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('admin/pricing.php'); }
verify_csrf();

$id = (int) ($_POST['id'] ?? 0);
$roomId = (int) ($_POST['room_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? 'EP');
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

$room = db_one('SELECT name FROM rooms WHERE id = ?', [$roomId]);

if ($id) {
    // Editing an existing plan - must still belong to the room posted (a stale form
    // pointing at a plan that moved/was deleted since the page loaded is just ignored,
    // same as any other not-found update in this codebase).
    $existing = db_one('SELECT id FROM rate_plans WHERE id = ? AND room_id = ?', [$id, $roomId]);
    if (!$existing) {
        flash('error', 'That tariff plan no longer exists.');
        redirect('admin/pricing.php');
    }
    db_run(
        'UPDATE rate_plans SET name=?, code=?, price_double=?, price_single=?, occupancy_prices=?, extra_person_price=? WHERE id=?',
        [$name, $code, $priceDouble, $priceSingle, json_encode($ladder), $extra, $id]
    );
    log_activity('rate_plan.updated', "Updated tariff plan \"{$name}\" for " . ($room['name'] ?? ''), 'rate_plan', $id);
    flash('success', "\"{$name}\" updated.");
    redirect('admin/pricing.php');
}

$sortOrder = (int) (db_one('SELECT MAX(sort_order) m FROM rate_plans WHERE room_id = ?', [$roomId])['m'] ?? 0) + 1;

$newId = db_insert(
    'INSERT INTO rate_plans (room_id, name, code, price_double, price_single, occupancy_prices, extra_person_price, sort_order, active) VALUES (?,?,?,?,?,?,?,?,1)',
    [$roomId, $name, $code, $priceDouble, $priceSingle, json_encode($ladder), $extra, $sortOrder]
);

log_activity('rate_plan.created', "Added tariff plan \"{$name}\" for " . ($room['name'] ?? ''), 'rate_plan', $newId);
flash('success', "\"{$name}\" added.");
redirect('admin/pricing.php');
