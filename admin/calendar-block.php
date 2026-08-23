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

$roomId = (int) ($data['room_id'] ?? 0);
$date = $data['date'] ?? '';

if (!$roomId || !strtotime($date)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid input']);
    exit;
}

$room = db_one('SELECT * FROM rooms WHERE id = ?', [$roomId]);
if (!$room) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Room not found']);
    exit;
}

$existing = db_one('SELECT * FROM room_date_inventory WHERE room_id = ? AND date = ?', [$roomId, $date]);
if ($existing) {
    $blocked = $existing['blocked'] ? 0 : 1;
    db_run('UPDATE room_date_inventory SET blocked = ? WHERE id = ?', [$blocked, $existing['id']]);
} else {
    $blocked = 1;
    db_insert('INSERT INTO room_date_inventory (room_id, date, rooms_left, blocked, created_at, updated_at) VALUES (?,?,?,?,NOW(),NOW())', [$roomId, $date, $room['rooms_left'], $blocked]);
}

log_activity('room.date_block_toggled', ($blocked ? 'Blocked' : 'Unblocked') . " {$room['name']} for {$date}", 'room', $roomId);

echo json_encode(['ok' => true, 'blocked' => (bool) $blocked]);
