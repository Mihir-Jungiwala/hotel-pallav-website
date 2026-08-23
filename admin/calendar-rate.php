<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['_csrf'] ?? '', $data['_csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session expired']);
    exit;
}

$planId = (int) ($data['rate_plan_id'] ?? 0);
$date = $data['date'] ?? '';
$occ = $data['occupancy'] ?? '';
$price = (int) ($data['price'] ?? -1);

if (!$planId || !in_array($occ, ['double', 'single'], true) || $price < 0 || !strtotime($date)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$plan = db_one('SELECT * FROM rate_plans WHERE id = ?', [$planId]);
if (!$plan) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Plan not found']);
    exit;
}

$field = 'price_' . $occ;
$existing = db_one('SELECT * FROM plan_date_rates WHERE rate_plan_id = ? AND date = ?', [$planId, $date]);

if ($existing) {
    db_run("UPDATE plan_date_rates SET $field = ? WHERE id = ?", [$price, $existing['id']]);
} else {
    $priceDouble = $occ === 'double' ? $price : $plan['price_double'];
    $priceSingle = $occ === 'single' ? $price : $plan['price_single'];
    db_insert('INSERT INTO plan_date_rates (rate_plan_id, date, price_double, price_single, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$planId, $date, $priceDouble, $priceSingle]);
}

log_activity('rate_plan.date_rate_updated', "Set {$plan['name']} rate for {$date} to ₹{$price} ({$occ})", 'rate_plan', $planId);

echo json_encode(['ok' => true]);
