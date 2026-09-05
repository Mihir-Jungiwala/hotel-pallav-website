<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
require_role(['master_admin', 'admin', 'editor']);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}
verify_csrf();

$order = $_POST['order'] ?? [];
if (!is_array($order) || !$order) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing order']);
    exit;
}

$ids = array_map('intval', $order);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$existing = db_all("SELECT id, room_id FROM rate_plans WHERE id IN ($placeholders)", $ids);
if (count($existing) !== count($ids) || count(array_unique(array_column($existing, 'room_id'))) !== 1) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown or mismatched rate plan id']);
    exit;
}

foreach ($ids as $i => $id) {
    db_run('UPDATE rate_plans SET sort_order = ? WHERE id = ?', [$i, $id]);
}

log_activity('rate_plan.reordered', 'Reordered tariff plans');
echo json_encode(['ok' => true]);
