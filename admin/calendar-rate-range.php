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
$occ = $data['occupancy'] ?? '';
$price = (int) ($data['price'] ?? -1);
$startDate = $data['start_date'] ?? '';
$endDate = $data['end_date'] ?? '';

if (!$planId || !in_array($occ, ['double', 'single'], true) || $price < 0 || !strtotime($startDate) || !strtotime($endDate)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$start = date('Y-m-d', strtotime($startDate));
$end = date('Y-m-d', strtotime($endDate));
if ($start > $end) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Start date must be before end date']);
    exit;
}
$dayCount = (int) ((strtotime($end) - strtotime($start)) / 86400) + 1;
if ($dayCount > 366) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Range cannot exceed 366 days']);
    exit;
}

$plan = db_one('SELECT * FROM rate_plans WHERE id = ?', [$planId]);
if (!$plan) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Plan not found']);
    exit;
}

$field = 'price_' . $occ;
$dates = [];
for ($cursor = $start; $cursor <= $end; $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'))) {
    $dates[] = $cursor;
}

foreach ($dates as $date) {
    $existing = db_one('SELECT id FROM plan_date_rates WHERE rate_plan_id = ? AND date = ?', [$planId, $date]);
    if ($existing) {
        db_run("UPDATE plan_date_rates SET $field = ? WHERE id = ?", [$price, $existing['id']]);
    } else {
        $priceDouble = $occ === 'double' ? $price : $plan['price_double'];
        $priceSingle = $occ === 'single' ? $price : $plan['price_single'];
        db_insert('INSERT INTO plan_date_rates (rate_plan_id, date, price_double, price_single, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$planId, $date, $priceDouble, $priceSingle]);
    }
}

log_activity('rate_plan.date_range_rate_updated', "Set {$plan['name']} ({$occ}) rate to ₹{$price} from {$start} to {$end}", 'rate_plan', $planId);

echo json_encode(['ok' => true, 'days' => count($dates)]);
