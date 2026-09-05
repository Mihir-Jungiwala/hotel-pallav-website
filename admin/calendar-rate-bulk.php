<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['_csrf'] ?? '', $data['_csrf'] ?? '')) {
    http_response_code(419);
    echo json_encode(['ok' => false, 'error' => 'Session expired']);
    exit;
}

$roomIds = $data['room_ids'] ?? [];
$planName = trim($data['plan_name'] ?? '');
$prices = $data['prices'] ?? [];
$startDate = $data['start_date'] ?? '';
$endDate = $data['end_date'] ?? '';

// $prices is an occupancy => price map, e.g. {"single": 2200, "double": 3000, "extra": 500} -
// each occupancy type gets its own price rather than one number forced onto all of them.
// "double"/"single" are date-scoped (plan_date_rates); "extra" is a flat per-plan
// surcharge (rate_plans.extra_person_price) - there's no per-date column for it.
$pricesValid = is_array($prices) && $prices && !array_diff(array_keys($prices), ['double', 'single', 'extra']);
if ($pricesValid) {
    foreach ($prices as $v) { if (!is_numeric($v) || (int) $v < 0) { $pricesValid = false; break; } }
}
if (!is_array($roomIds) || !$roomIds || $planName === '' || !$pricesValid || !strtotime($startDate) || !strtotime($endDate)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}
$prices = array_map('intval', $prices);
$dateOccPrices = array_intersect_key($prices, array_flip(['double', 'single']));

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

$roomIds = array_map('intval', $roomIds);
$placeholders = implode(',', array_fill(0, count($roomIds), '?'));
$rooms = db_all("SELECT id, name FROM rooms WHERE id IN ($placeholders)", $roomIds);
if (!$rooms) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No matching room categories']);
    exit;
}

$dates = [];
for ($cursor = $start; $cursor <= $end; $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'))) {
    $dates[] = $cursor;
}

$updatedPlans = 0;
$skippedRooms = [];
foreach ($rooms as $room) {
    // Plan names recur across room categories (e.g. every room has a "Standard Plan"),
    // so the chosen plan name is matched per room rather than always touching one fixed plan.
    $plan = db_one('SELECT * FROM rate_plans WHERE room_id = ? AND name = ? AND active = 1 LIMIT 1', [$room['id'], $planName]);
    if (!$plan) {
        $skippedRooms[] = $room['name'];
        continue;
    }
    if ($dateOccPrices) {
        foreach ($dates as $date) {
            // Only write a date whose price is actually changing - an unconditional
            // UPDATE would bump updated_at (and so the "recently changed" gold
            // highlight) on cells the admin didn't really touch.
            $existing = db_one('SELECT id, price_double, price_single FROM plan_date_rates WHERE rate_plan_id = ? AND date = ?', [$plan['id'], $date]);
            if ($existing) {
                $sets = [];
                $params = [];
                foreach ($dateOccPrices as $occ => $p) {
                    $currentVal = (int) ($occ === 'double' ? $existing['price_double'] : $existing['price_single']);
                    if ($currentVal !== $p) { $sets[] = "price_{$occ} = ?"; $params[] = $p; }
                }
                if ($sets) {
                    $params[] = $existing['id'];
                    db_run('UPDATE plan_date_rates SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
                }
            } else {
                $priceDouble = $dateOccPrices['double'] ?? $plan['price_double'];
                $priceSingle = $dateOccPrices['single'] ?? $plan['price_single'];
                $changed = (int) $priceDouble !== (int) $plan['price_double'] || (int) ($priceSingle ?? 0) !== (int) ($plan['price_single'] ?? 0);
                if ($changed) {
                    db_insert('INSERT INTO plan_date_rates (rate_plan_id, date, price_double, price_single, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$plan['id'], $date, $priceDouble, $priceSingle]);
                }
            }
        }
    }
    if (array_key_exists('extra', $prices)) {
        $currentExtra = $plan['extra_person_price'] !== null ? (int) $plan['extra_person_price'] : null;
        if ($currentExtra !== $prices['extra']) {
            db_run('UPDATE rate_plans SET extra_person_price = ? WHERE id = ?', [$prices['extra'], $plan['id']]);
        }
    }
    $updatedPlans++;
}

$roomNames = implode(', ', array_column($rooms, 'name'));
$occLabels = ['single' => '1 Guest', 'double' => '2 Guests', 'extra' => 'Extra Person'];
$priceLabels = [];
foreach (['single', 'double', 'extra'] as $occ) { if (isset($prices[$occ])) $priceLabels[] = $occLabels[$occ] . " ₹{$prices[$occ]}"; }
$priceLabel = implode(', ', $priceLabels);
log_activity('rate_plan.bulk_date_range_rate_updated', "Set {$planName} rate ({$priceLabel}) from {$start} to {$end} for {$roomNames}");

echo json_encode(['ok' => true, 'days' => count($dates), 'rooms' => $updatedPlans, 'skipped' => $skippedRooms]);
