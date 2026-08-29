<?php
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
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
$existing = db_all("SELECT id FROM gallery_photos WHERE id IN ($placeholders)", $ids);
if (count($existing) !== count($ids)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown photo id']);
    exit;
}

foreach ($ids as $i => $id) {
    db_run('UPDATE gallery_photos SET sort_order = ? WHERE id = ?', [$i, $id]);
}

log_activity('gallery.reordered', 'Reordered gallery photos');
echo json_encode(['ok' => true]);
