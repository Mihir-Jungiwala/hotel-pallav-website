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

$roomId = (int) ($data['room_id'] ?? 0);
$date = $data['date'] ?? '';
$roomsLeft = (int) ($data['rooms_left'] ?? -1);

if (!$roomId || $roomsLeft < 0 || $roomsLeft > 250 || !strtotime($date)) {
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

// Rooms available can never drop below what's already confirmed for that date - a
// guest who's been told "confirmed" can't be bumped by an inventory edit afterward -
// and can never exceed how many rooms the category actually has.
$sold = sold_for_date($roomId, $date);
if ($roomsLeft < $sold) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => "Can't go below {$sold} - {$sold} room" . ($sold === 1 ? '' : 's') . " already confirmed for this date."]);
    exit;
}
if ($roomsLeft > (int) $room['total_count']) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => "Can't go above {$room['total_count']} - that's the total number of {$room['name']} rooms."]);
    exit;
}

$existing = db_one('SELECT * FROM room_date_inventory WHERE room_id = ? AND date = ?', [$roomId, $date]);
if ($existing) {
    db_run('UPDATE room_date_inventory SET rooms_left = ? WHERE id = ?', [$roomsLeft, $existing['id']]);
} else {
    db_insert('INSERT INTO room_date_inventory (room_id, date, rooms_left, blocked, created_at, updated_at) VALUES (?,?,?,0,NOW(),NOW())', [$roomId, $date, $roomsLeft]);
}

log_activity('room.date_inventory_updated', "Set {$room['name']} inventory for {$date} to {$roomsLeft}", 'room', $roomId);

echo json_encode(['ok' => true]);
